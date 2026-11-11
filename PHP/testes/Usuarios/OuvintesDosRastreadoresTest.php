<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/rastreadores/ouvintes/f_ouvintesdosrastreadores.php';

class OuvintesDosRastreadoresTest extends TestCase {
    private $owner;
    private $other;
    private $tracker_id;
    private $listener_ur_id;
    private $owner_ur_id;

    protected function setUp(): void {
        $pdo = getDataBase();
        $this->owner = $this->criarUsuarioTemporario($pdo, 'Unit Owner');
        $this->other = $this->criarUsuarioTemporario($pdo, 'Unit Other');

        $stmt = $pdo->prepare('insert into rastreador (hardware, token, token_publico, senha, obs, status, ativo, dono_id) values (:hardware, :token, :token_publico, :senha, :obs, 1, true, :dono_id) returning id');
        $stmt->execute([
            'hardware' => 'Unit Tracker ' . uniqid(),
            'token' => 'unit_tracker_token_' . uniqid(),
            'token_publico' => 'unit_tracker_public_' . uniqid(),
            'senha' => 'unit_tracker_password',
            'obs' => 'Unit test tracker',
            'dono_id' => $this->owner['id']
        ]);
        $this->tracker_id = (int) $stmt->fetchColumn();
        $this->owner_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $this->owner['id'], $this->tracker_id, 'Unit owner', 2);
        $this->listener_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $this->other['id'], $this->tracker_id, 'Unit listener', 4);
    }

    protected function tearDown(): void {
        $pdo = getDataBase();
        $pdo->prepare('delete from usuario_rastreador where rastreador_id = :rastreador_id')->execute(['rastreador_id' => $this->tracker_id]);
        $pdo->prepare('delete from rastreador where id = :id')->execute(['id' => $this->tracker_id]);
        $pdo->prepare('delete from usuario where id in (:owner, :other)')->execute([
            'owner' => $this->owner['id'],
            'other' => $this->other['id']
        ]);
    }

    private function criarUsuarioTemporario($pdo, $prefixo) {
        $sufixo = uniqid();
        $nome = $prefixo . ' ' . $sufixo;
        $login = 'login_' . $sufixo;
        $email = 'temp_' . $sufixo . '@gmail.com';

        $stmt = $pdo->prepare('insert into usuario (nome, login, senha, legal_ident_id, ativo, email, telefone) values (:nome, :login, :senha, :legal_ident_id, true, :email, null) returning id');
        $stmt->execute([
            'nome' => $nome,
            'login' => $login,
            'senha' => 'Aa1#' . $sufixo,
            'legal_ident_id' => 1,
            'email' => $email
        ]);

        return [
            'id' => (int) $stmt->fetchColumn(),
            'nome' => $nome,
            'login' => $login,
            'email' => $email
        ];
    }

    private function removerUsuario($pdo, $usuario_id) {
        $stmt = $pdo->prepare('delete from usuario where id = :id');
        $stmt->execute(['id' => $usuario_id]);
    }

    private function criarUsuarioRastreadorTemporario($pdo, $usuario_id, $rastreador_id, $nome, $status) {
        $stmt = $pdo->prepare('insert into usuario_rastreador (usuario_id, rastreador_id, nome, status, ativo, loc_temporeal, loc_salvos) values (:usuario_id, :rastreador_id, :nome, :status, true, true, true) returning id');
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'rastreador_id' => $rastreador_id,
            'nome' => $nome,
            'status' => $status
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function removerUsuarioRastreador($pdo, $ur_id) {
        $pdo->beginTransaction();
        deleteUsuarioRastreador($pdo, $ur_id);
        $pdo->commit();
    }

    private function buscarStatusUsuarioRastreador($pdo, $ur_id) {
        $stmt = $pdo->prepare('select status from usuario_rastreador where id = :id');
        $stmt->execute(['id' => $ur_id]);
        return $stmt->fetchColumn();
    }

    private function buscarStatusRastreador($pdo, $rastreador_id) {
        $stmt = $pdo->prepare('select status from rastreador where id = :id');
        $stmt->execute(['id' => $rastreador_id]);
        return $stmt->fetchColumn();
    }

    private function contarUsuarioRastreador($pdo, $ur_id) {
        $stmt = $pdo->prepare('select count(*) from usuario_rastreador where id = :id');
        $stmt->execute(['id' => $ur_id]);
        return (int) $stmt->fetchColumn();
    }

    function test_validar_usuario_pode_ver_ouvintes_do_rastreador() {
        $pdo = getDataBase();

        $this->assertTrue(validarUsuarioPodeVerOuvintesDoRastreador($pdo, $this->owner['id'], $this->tracker_id));
        $this->assertFalse(validarUsuarioPodeVerOuvintesDoRastreador($pdo, $this->other['id'], $this->tracker_id));
    }

    function test_get_ouvintes_do_rastreador() {
        $pdo = getDataBase();
        $credenciais = ["pdo" => $pdo, "id" => $this->owner['id']];

        $result = getOuvintesDoRastreador($credenciais, $this->tracker_id, null);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ouvintes', $result);
        $this->assertNotEmpty($result['ouvintes']);
        $listener = array_values(array_filter($result['ouvintes'], function ($ouvinte) {
            return (int) $ouvinte['ur_id'] === $this->listener_ur_id;
        }));
        $this->assertCount(1, $listener);
        $this->assertSame($this->other['email'], $listener[0]['u_email']);
        $this->assertSame($this->other['id'], (int) $listener[0]['u_id']);

        $result_negativo = getOuvintesDoRastreador(["pdo" => $pdo, "id" => $this->other['id']], $this->tracker_id, 'Unit listener');
        $this->assertSame('Usuário não tem permissão para ver os ouvintes do rastreador:' . $this->other['id'] . ' - ' . $this->tracker_id, $result_negativo['error']);
    }

    function test_dono_envia_proposta_de_transferencia_de_posse() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $ur_status_original = $this->buscarStatusUsuarioRastreador($pdo, $this->owner_ur_id);
        $rastreador_status_original = $this->buscarStatusRastreador($pdo, $this->tracker_id);

        try {
            $result = donoEnviaPropostaDeTransferenciaDePosse($credenciais_dono, $this->owner_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame('5', (string) $this->buscarStatusUsuarioRastreador($pdo, $this->owner_ur_id));
            $this->assertSame('3', (string) $this->buscarStatusRastreador($pdo, $this->tracker_id));

            $stmt = $pdo->prepare('update usuario_rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $ur_status_original, 'id' => $this->owner_ur_id]);
            $stmt = $pdo->prepare('update rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $rastreador_status_original, 'id' => $this->tracker_id]);

            $result_negativo = donoEnviaPropostaDeTransferenciaDePosse($credenciais_nao_dono, $this->owner_ur_id);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere para enviar proposta de transferência de posse:' . $this->owner_ur_id . ' - ' . $this->other['id'], $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $stmt = $pdo->prepare('update usuario_rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $ur_status_original, 'id' => $this->owner_ur_id]);
            $stmt = $pdo->prepare('update rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $rastreador_status_original, 'id' => $this->tracker_id]);
        }
    }

    function test_dono_cancela_transferencia_de_posse() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $ur_status_original = $this->buscarStatusUsuarioRastreador($pdo, $this->owner_ur_id);
        $rastreador_status_original = $this->buscarStatusRastreador($pdo, $this->tracker_id);

        try {
            $this->assertTrue(donoEnviaPropostaDeTransferenciaDePosse($credenciais_dono, $this->owner_ur_id)['success']);
            $this->assertSame('5', (string) $this->buscarStatusUsuarioRastreador($pdo, $this->owner_ur_id));
            $this->assertSame('3', (string) $this->buscarStatusRastreador($pdo, $this->tracker_id));

            $result = donoCancelaTransferenciaDePosse($credenciais_dono, $this->owner_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame('2', (string) $this->buscarStatusUsuarioRastreador($pdo, $this->owner_ur_id));
            $this->assertSame('1', (string) $this->buscarStatusRastreador($pdo, $this->tracker_id));

            $result_negativo = donoCancelaTransferenciaDePosse($credenciais_nao_dono, $this->owner_ur_id);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere para cancelar proposta de transferência de posse:' . $this->owner_ur_id . ' - ' . $this->other['id'], $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $stmt = $pdo->prepare('update usuario_rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $ur_status_original, 'id' => $this->owner_ur_id]);
            $stmt = $pdo->prepare('update rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $rastreador_status_original, 'id' => $this->tracker_id]);
        }
    }

    function test_dono_envia_proposta_de_ouvinte_a_um_usuario() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Ouvinte');
            $nome_proposta = 'Proposta ' . uniqid();

            $result = donoEnviaPropostaDeOuvinteAUmUsuario($credenciais_dono, $this->tracker_id, $nome_proposta, $temp_usuario['id']);
            $this->assertTrue($result['success']);

            $stmt = $pdo->prepare('select id, usuario_id, rastreador_id, nome, status, ativo, loc_temporeal, loc_salvos from usuario_rastreador where usuario_id = :usuario_id and rastreador_id = :rastreador_id and nome = :nome order by id desc limit 1');
            $stmt->execute([
                'usuario_id' => $temp_usuario['id'],
                'rastreador_id' => $this->tracker_id,
                'nome' => $nome_proposta
            ]);
            $temp_ur = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertNotFalse($temp_ur);
            $temp_ur_id = (int) $temp_ur['id'];
            $this->assertSame((string) $temp_usuario['id'], (string) $temp_ur['usuario_id']);
            $this->assertSame((string) $this->tracker_id, (string) $temp_ur['rastreador_id']);
            $this->assertSame($nome_proposta, $temp_ur['nome']);
            $this->assertSame('4', (string) $temp_ur['status']);

            $result_negativo = donoEnviaPropostaDeOuvinteAUmUsuario($credenciais_nao_dono, $this->tracker_id, 'Proposta Negativa ' . uniqid(), $temp_usuario['id']);
            $this->assertSame('Rastreador não encontrado ou usuário autenticado não é o dono para enviar proposta de ouvinte:' . $this->tracker_id . ' - ' . $this->other['id'], $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($temp_ur_id !== null) {
                $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            }
            if ($temp_usuario !== null) {
                $this->removerUsuario($pdo, $temp_usuario['id']);
            }
        }
    }

    function test_validar_dono_correto_for_accept_decline() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Status 3');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], $this->tracker_id, 'Convite temporario', 3);

            $this->assertTrue(validarDonoCorretoForAcceptDecline($credenciais_dono, $temp_ur_id));
            $this->assertFalse(validarDonoCorretoForAcceptDecline($credenciais_nao_dono, $temp_ur_id));
        } finally {
            if ($temp_ur_id !== null) {
                $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            }
            if ($temp_usuario !== null) {
                $this->removerUsuario($pdo, $temp_usuario['id']);
            }
        }
    }

    function test_dono_aceita_novo_ouvinte() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Aceite');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], $this->tracker_id, 'Convite para aceitar', 3);

            $result = donoAceitaNovoOuvinte($credenciais_dono, $temp_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame('2', (string) $this->buscarStatusUsuarioRastreador($pdo, $temp_ur_id));

            $result_negativo = donoAceitaNovoOuvinte($credenciais_nao_dono, $temp_ur_id);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte:' . $this->other['id'] . ' - ' . $temp_ur_id, $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($temp_ur_id !== null) {
                $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            }
            if ($temp_usuario !== null) {
                $this->removerUsuario($pdo, $temp_usuario['id']);
            }
        }
    }

    function test_dono_recusa_novo_ouvinte() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Recusa');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], $this->tracker_id, 'Convite para recusar', 3);

            $result = donoRecusaNovoOuvinte($credenciais_dono, $temp_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame(0, $this->contarUsuarioRastreador($pdo, $temp_ur_id));

            $result_negativo = donoRecusaNovoOuvinte($credenciais_nao_dono, $temp_ur_id);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte:' . $this->other['id'] . ' - ' . $temp_ur_id, $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($temp_ur_id !== null && $this->contarUsuarioRastreador($pdo, $temp_ur_id) > 0) {
                $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            }
            if ($temp_usuario !== null) {
                $this->removerUsuario($pdo, $temp_usuario['id']);
            }
        }
    }

    function test_validar_dono_correto_for_delete() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];

        $this->assertTrue(validarDonoCorretoForManipulate($credenciais_dono, $this->owner_ur_id));
        $this->assertFalse(validarDonoCorretoForManipulate($credenciais_nao_dono, $this->owner_ur_id));
    }

    function test_dono_deleta_ouvinte() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => $this->owner['id']];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => $this->other['id']];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Delete');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], $this->tracker_id, 'Convite para deletar', 4);

            $result = donoDeletaOuvinte($credenciais_dono, $temp_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame(0, $this->contarUsuarioRastreador($pdo, $temp_ur_id));

            $result_negativo = donoDeletaOuvinte($credenciais_nao_dono, $temp_ur_id);
            $this->assertSame('Rastreador de usuário não encontrado ou usuário autenticado não é o dono para deletar ouvinte:' . $this->other['id'] . ' - ' . $temp_ur_id, $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($temp_ur_id !== null && $this->contarUsuarioRastreador($pdo, $temp_ur_id) > 0) {
                $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            }
            if ($temp_usuario !== null) {
                $this->removerUsuario($pdo, $temp_usuario['id']);
            }
        }
    }
}
