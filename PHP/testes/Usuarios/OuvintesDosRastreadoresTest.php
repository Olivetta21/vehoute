<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/rastreadores/ouvintes/f_ouvintesdosrastreadores.php';

class OuvintesDosRastreadoresTest extends TestCase {
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
            'legal_ident_id' => 70,
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
        $stmt = $pdo->prepare('delete from usuario_rastreador where id = :id');
        $stmt->execute(['id' => $ur_id]);
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

        $this->assertTrue(validarUsuarioPodeVerOuvintesDoRastreador($pdo, 376, 24));
        $this->assertFalse(validarUsuarioPodeVerOuvintesDoRastreador($pdo, 377, 24));
    }

    function test_get_ouvintes_do_rastreador() {
        $pdo = getDataBase();
        $credenciais = ["pdo" => $pdo, "id" => 376];

        $result = getOuvintesDoRastreador($credenciais, 24, 'UsuarioForUnitTestb@gmail.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ouvintes', $result);
        $this->assertCount(1, $result['ouvintes']);
        $this->assertEquals([
            'email' => 'UsuarioForUnitTestB@gmail.com',
            'id' => 33,
            'loc_salvos' => true,
            'loc_temporeal' => true,
            'rastreador_id' => 24,
            'telefone' => null,
            'u_nome' => 'UsuarioFor UnitTestB',
            'ur_status' => 4,
            'usuario_id' => 377
        ], $result['ouvintes'][0]);

        $result_negativo = getOuvintesDoRastreador(["pdo" => $pdo, "id" => 377], 24, 'UsuarioForUnitTestb@gmail.com');
        $this->assertSame('Usuário não tem permissão para ver os ouvintes do rastreador:377 - 24', $result_negativo['error']);
    }

    function test_dono_envia_proposta_de_transferencia_de_posse() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $ur_status_original = $this->buscarStatusUsuarioRastreador($pdo, 32);
        $rastreador_status_original = $this->buscarStatusRastreador($pdo, 24);

        try {
            $result = donoEnviaPropostaDeTransferenciaDePosse($credenciais_dono, 32);
            $this->assertTrue($result['success']);
            $this->assertSame('5', (string) $this->buscarStatusUsuarioRastreador($pdo, 32));
            $this->assertSame('3', (string) $this->buscarStatusRastreador($pdo, 24));

            $stmt = $pdo->prepare('update usuario_rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $ur_status_original, 'id' => 32]);
            $stmt = $pdo->prepare('update rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $rastreador_status_original, 'id' => 24]);

            $result_negativo = donoEnviaPropostaDeTransferenciaDePosse($credenciais_nao_dono, 32);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere para enviar proposta de transferência de posse:32 - 377', $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $stmt = $pdo->prepare('update usuario_rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $ur_status_original, 'id' => 32]);
            $stmt = $pdo->prepare('update rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $rastreador_status_original, 'id' => 24]);
        }
    }

    function test_dono_cancela_transferencia_de_posse() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $ur_status_original = $this->buscarStatusUsuarioRastreador($pdo, 32);
        $rastreador_status_original = $this->buscarStatusRastreador($pdo, 24);

        try {
            $this->assertTrue(donoEnviaPropostaDeTransferenciaDePosse($credenciais_dono, 32)['success']);
            $this->assertSame('5', (string) $this->buscarStatusUsuarioRastreador($pdo, 32));
            $this->assertSame('3', (string) $this->buscarStatusRastreador($pdo, 24));

            $result = donoCancelaTransferenciaDePosse($credenciais_dono, 32);
            $this->assertTrue($result['success']);
            $this->assertSame('2', (string) $this->buscarStatusUsuarioRastreador($pdo, 32));
            $this->assertSame('1', (string) $this->buscarStatusRastreador($pdo, 24));

            $result_negativo = donoCancelaTransferenciaDePosse($credenciais_nao_dono, 32);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere para cancelar proposta de transferência de posse:32 - 377', $result_negativo['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $stmt = $pdo->prepare('update usuario_rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $ur_status_original, 'id' => 32]);
            $stmt = $pdo->prepare('update rastreador set status = :status where id = :id');
            $stmt->execute(['status' => $rastreador_status_original, 'id' => 24]);
        }
    }

    function test_dono_envia_proposta_de_ouvinte_a_um_usuario() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Ouvinte');
            $nome_proposta = 'Proposta ' . uniqid();

            $result = donoEnviaPropostaDeOuvinteAUmUsuario($credenciais_dono, 24, $nome_proposta, $temp_usuario['id']);
            $this->assertTrue($result['success']);

            $stmt = $pdo->prepare('select id, usuario_id, rastreador_id, nome, status, ativo, loc_temporeal, loc_salvos from usuario_rastreador where usuario_id = :usuario_id and rastreador_id = :rastreador_id and nome = :nome order by id desc limit 1');
            $stmt->execute([
                'usuario_id' => $temp_usuario['id'],
                'rastreador_id' => 24,
                'nome' => $nome_proposta
            ]);
            $temp_ur = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertNotFalse($temp_ur);
            $temp_ur_id = (int) $temp_ur['id'];
            $this->assertSame((string) $temp_usuario['id'], (string) $temp_ur['usuario_id']);
            $this->assertSame('24', (string) $temp_ur['rastreador_id']);
            $this->assertSame($nome_proposta, $temp_ur['nome']);
            $this->assertSame('4', (string) $temp_ur['status']);

            $result_negativo = donoEnviaPropostaDeOuvinteAUmUsuario($credenciais_nao_dono, 24, 'Proposta Negativa ' . uniqid(), $temp_usuario['id']);
            $this->assertSame('Rastreador não encontrado ou usuário autenticado não é o dono para enviar proposta de ouvinte:24 - 377', $result_negativo['error']);
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
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Status 3');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 24, 'Convite temporario', 3);

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
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Aceite');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 24, 'Convite para aceitar', 3);

            $result = donoAceitaNovoOuvinte($credenciais_dono, $temp_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame('2', (string) $this->buscarStatusUsuarioRastreador($pdo, $temp_ur_id));

            $result_negativo = donoAceitaNovoOuvinte($credenciais_nao_dono, $temp_ur_id);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte:377 - ' . $temp_ur_id, $result_negativo['error']);
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
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Recusa');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 24, 'Convite para recusar', 3);

            $result = donoRecusaNovoOuvinte($credenciais_dono, $temp_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame(0, $this->contarUsuarioRastreador($pdo, $temp_ur_id));

            $result_negativo = donoRecusaNovoOuvinte($credenciais_nao_dono, 33);
            $this->assertSame('Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte:377 - 33', $result_negativo['error']);
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
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];

        $this->assertTrue(validarDonoCorretoForDelete($credenciais_dono, 32));
        $this->assertFalse(validarDonoCorretoForDelete($credenciais_nao_dono, 32));
    }

    function test_dono_deleta_ouvinte() {
        $pdo = getDataBase();
        $credenciais_dono = ["pdo" => $pdo, "id" => 376];
        $credenciais_nao_dono = ["pdo" => $pdo, "id" => 377];
        $temp_usuario = null;
        $temp_ur_id = null;

        try {
            $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Usuario Temporario Delete');
            $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 24, 'Convite para deletar', 4);

            $result = donoDeletaOuvinte($credenciais_dono, $temp_ur_id);
            $this->assertTrue($result['success']);
            $this->assertSame(0, $this->contarUsuarioRastreador($pdo, $temp_ur_id));

            $result_negativo = donoDeletaOuvinte($credenciais_nao_dono, 32);
            $this->assertSame('Rastreador de usuário não encontrado ou usuário autenticado não é o dono para deletar ouvinte:377 - 32', $result_negativo['error']);
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

