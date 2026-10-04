<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/rastreadores/lococulta/f_lococultadosrastreadores.php';

class LocOcultasDosRastreadoresTest extends TestCase {
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
        $stmt = $pdo->prepare('delete from vinc_loc_oculta_usuario_rastreador where usuario_rastreador_id = :id');
        $stmt->execute(['id' => $ur_id]);
        $stmt = $pdo->prepare('delete from usuario_rastreador where id = :id');
        $stmt->execute(['id' => $ur_id]);
    }

    private function criarLocOcultaTemporaria($pdo, $rastreador_id, $identificacao) {
        $stmt = $pdo->prepare('insert into intervalo_loc_oculta (rastreador_id, id_inicial, id_final, identificacao) values (:rastreador_id, 1, 2, :identificacao) returning id');
        $stmt->execute([
            'rastreador_id' => $rastreador_id,
            'identificacao' => $identificacao
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function removerLocOculta($pdo, $lococulta_id) {
        $stmt = $pdo->prepare('delete from vinc_loc_oculta_usuario_rastreador where intervalo_loc_oculta_id = :id');
        $stmt->execute(['id' => $lococulta_id]);
        $stmt = $pdo->prepare('delete from intervalo_loc_oculta where id = :id');
        $stmt->execute(['id' => $lococulta_id]);
    }

    private function contarLocOculta($pdo, $lococulta_id) {
        $stmt = $pdo->prepare('select count(*) from intervalo_loc_oculta where id = :id');
        $stmt->execute(['id' => $lococulta_id]);
        return (int) $stmt->fetchColumn();
    }

    function test_validar_usuario_pode_ver_loc_oculta_do_rastreador() {
        $pdo = getDataBase();

        $this->assertTrue(validarUsuarioPodeVerLocOcultaDoRastreador($pdo, 376, 24));
        $this->assertFalse(validarUsuarioPodeVerLocOcultaDoRastreador($pdo, 377, 24));
    }

    function test_get_loc_oculta_do_rastreador() {
        $pdo = getDataBase();
        $credenciais_dono = ['pdo' => $pdo, 'id' => 376];
        $identificacao = 'Loc temporaria ' . uniqid();
        $lococulta_id = $this->criarLocOcultaTemporaria($pdo, 24, $identificacao);

        try {
            $result = getLocOcultaDoRastreador($credenciais_dono, 24, 'temporaria');

            $this->assertTrue($result['success']);
            $this->assertCount(1, $result['localizacoes_ocultas']);
            $this->assertSame($identificacao, $result['localizacoes_ocultas'][0]['identificacao']);
            $this->assertArrayHasKey('ouvintes', $result['localizacoes_ocultas'][0]);
            $this->assertCount(0, $result['localizacoes_ocultas'][0]['ouvintes']);

            $this->assertEquals([
                'id' => $lococulta_id,
                'rastreador_id' => 24,
                'identificacao' => $identificacao,
                'id_inicial' => 1,
                'id_final' => 2,
                'data_inicial' => null,
                'data_final' => null,
                'novos_ouvintes' => true,
                'ouvintes' => []
            ], $result['localizacoes_ocultas'][0]);

            $result_sem_resultados = getLocOcultaDoRastreador($credenciais_dono, 24, 'nao-encontrada');
            $this->assertSame('Nenhuma localização oculta encontrada para o rastreador:24', $result_sem_resultados['error']);

            $result_sem_permissao = getLocOcultaDoRastreador(['pdo' => $pdo, 'id' => 377], 24, null);
            $this->assertSame('Usuário não tem permissão para ver as localizações ocultas do rastreador:377 - 24', $result_sem_permissao['error']);
        } finally {
            $this->removerLocOculta($pdo, $lococulta_id);
        }
    }

    function test_create_loc_oculta_do_rastreador() {
        $pdo = getDataBase();
        $credenciais_dono = ['pdo' => $pdo, 'id' => 376];
        $identificacao = 'Loc criada ' . uniqid();
        $result = createLocOcultaDoRastreador($credenciais_dono, 24, 10, 20, null, null, $identificacao);

        $this->assertTrue($result['success']);
        $this->assertSame($identificacao, $result['localizacao_oculta']['identificacao']);
        $this->assertSame('24', (string) $result['localizacao_oculta']['rastreador_id']);
        $this->assertSame([], $result['localizacao_oculta']['ouvintes']);

        try {
            $result_sem_permissao = createLocOcultaDoRastreador(['pdo' => $pdo, 'id' => 377], 24, 1, 2, null, null, 'Nao permitida');
            $this->assertSame('Usuário não tem permissão para criar localizações ocultas do rastreador:377 - 24', $result_sem_permissao['error']);
        } finally {
            $this->removerLocOculta($pdo, (int) $result['localizacao_oculta']['id']);
        }

        $result_id_invalido = createLocOcultaDoRastreador($credenciais_dono, 0, 1, 2, null, null, 'Id invalido');
        $this->assertSame('Id de rastreador inválido:0', $result_id_invalido['error']);
    }

    function test_update_loc_oculta_do_rastreador() {
        $pdo = getDataBase();
        $credenciais_dono = ['pdo' => $pdo, 'id' => 376];
        $lococulta_id = $this->criarLocOcultaTemporaria($pdo, 24, 'Loc antes ' . uniqid());

        try {
            $result = updateLocOcultaDoRastreador($credenciais_dono, 24, $lococulta_id, [
                'id_inicial' => 30,
                'id_final' => 40,
                'identificacao' => 'Loc atualizada'
            ]);

            $this->assertTrue($result['success']);
            $this->assertSame('30', (string) $result['localizacao_oculta']['id_inicial']);
            $this->assertSame('40', (string) $result['localizacao_oculta']['id_final']);
            $this->assertSame('Loc atualizada', $result['localizacao_oculta']['identificacao']);

            $result_caracteristica_invalida = updateLocOcultaDoRastreador($credenciais_dono, 24, $lococulta_id, ['campo_invalido' => 'valor']);
            $this->assertSame('Característica inválida:campo_invalido', $result_caracteristica_invalida['error']);

            $result_data_invalida = updateLocOcultaDoRastreador($credenciais_dono, 24, $lococulta_id, ['data_inicial' => '2024-06-01 00:00:00.123', 'data_final' => '2024-05-01 00:00:00.123']);
            $this->assertStringContainsString(
                "a nova linha da relação \"intervalo_loc_oculta\" viola a restrição de verificação \"intervalo_loc_oculta_check_data_nao_usa_milissegundos\"",
                $result_data_invalida['error']
            );

            $result_sem_permissao = updateLocOcultaDoRastreador(['pdo' => $pdo, 'id' => 377], 24, $lococulta_id, ['identificacao' => 'Nao permitida']);
            $this->assertSame('Usuário não tem permissão para atualizar localizações ocultas do rastreador:377 - 24', $result_sem_permissao['error']);
        } finally {
            $this->removerLocOculta($pdo, $lococulta_id);
        }

        $result_id_invalido = updateLocOcultaDoRastreador($credenciais_dono, 24, 0, []);
        $this->assertSame('Id de localização oculta inválido:0', $result_id_invalido['error']);
    }

    function test_delete_loc_oculta_do_rastreador() {
        $pdo = getDataBase();
        $credenciais_dono = ['pdo' => $pdo, 'id' => 376];
        $lococulta_id = $this->criarLocOcultaTemporaria($pdo, 24, 'Loc para deletar ' . uniqid());

        try {
            $result_sem_permissao = deleteLocOcultaDoRastreador(['pdo' => $pdo, 'id' => 377], 24, [$lococulta_id]);
            $this->assertSame('Usuário não tem permissão para deletar localizações ocultas do rastreador:377 - 24', $result_sem_permissao['error']);

            $result = deleteLocOcultaDoRastreador($credenciais_dono, 24, [$lococulta_id]);
            $this->assertTrue($result['success']);
            $this->assertSame(0, $this->contarLocOculta($pdo, $lococulta_id));
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($this->contarLocOculta($pdo, $lococulta_id) > 0) {
                $this->removerLocOculta($pdo, $lococulta_id);
            }
        }

        $result_array_invalido = deleteLocOcultaDoRastreador($credenciais_dono, 24, null);
        $this->assertSame('Array de ids de localizações ocultas inválido:', $result_array_invalido['error']);
    }

    function test_get_ouvintes_disponiveis() {
        $pdo = getDataBase();
        $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Ouvinte temporario loc oculta');
        $temp_usuario2 = $this->criarUsuarioTemporario($pdo, 'Ouvinte temporario loc oculta 2');
        $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 24, 'Ouvinte loc oculta', 2);
        $temp_ur_id2 = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario2['id'], 24, 'Ouvinte loc oculta 2', 2);

        try {
            $result = getOuvintesDisponiveis(['pdo' => $pdo, 'id' => 376], 24);

            $this->assertTrue($result['success']);
            $ouvintes = array_values(array_filter($result['ouvintes'], function ($ouvinte) use ($temp_ur_id) {
                return (int) $ouvinte['ur_id'] === $temp_ur_id;
            }));
            $this->assertCount(1, $ouvintes);
            $this->assertSame((string) $temp_usuario['id'], (string) $ouvintes[0]['u_id']);

            //ordena por ordem de ur_id para garantir que a comparação seja consistente
            usort($result['ouvintes'], function ($a, $b) {
                return $a['ur_id'] <=> $b['ur_id'];
            });

            $this->assertEquals([
                [
                    'ur_id' => 32,
                    'u_nome' => 'UsuarioFor UnitTest',
                    'u_id' => 376
                ],
                [
                    'ur_id' => 33,
                    'u_nome' => 'UsuarioFor UnitTestB',
                    'u_id' => 377
                ],
                [
                    'ur_id' => $temp_ur_id,
                    'u_nome' => $temp_usuario['nome'],
                    'u_id' => $temp_usuario['id']
                ],
                [
                    'ur_id' => $temp_ur_id2,
                    'u_nome' => $temp_usuario2['nome'],
                    'u_id' => $temp_usuario2['id']
                ]
            ], $result['ouvintes']);

            $result_sem_permissao = getOuvintesDisponiveis(['pdo' => $pdo, 'id' => 377], 24);
            $this->assertSame('Usuário não tem permissão para ver ouvintes do rastreador:377 - 24', $result_sem_permissao['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            $this->removerUsuarioRastreador($pdo, $temp_ur_id2);
            $this->removerUsuario($pdo, $temp_usuario['id']);
            $this->removerUsuario($pdo, $temp_usuario2['id']);
        }
    }

    function test_set_ouvintes_loc_oculta() {
        $pdo = getDataBase();
        $temp_usuario = $this->criarUsuarioTemporario($pdo, 'Ouvinte para loc oculta');
        $temp_ur_id = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 24, 'Ouvinte para set', 2);
        $identificacao = 'Loc para ouvintes ' . uniqid();
        $lococulta_id = $this->criarLocOcultaTemporaria($pdo, 24, $identificacao);

        try {
            $result = setOuvintesLocOculta(['pdo' => $pdo, 'id' => 376], [$lococulta_id], [$temp_ur_id], 24);

            $this->assertTrue($result['success']);
            $this->assertCount(1, $result['ouvintes']);
            $this->assertSame((string) $temp_ur_id, (string) $result['ouvintes'][0]['ur_id']);
            $this->assertSame((string) $lococulta_id, (string) $result['ouvintes'][0]['ilo_id']);

            $this->assertEquals([
                'ilo_id' => $lococulta_id,
                'ur_id' => $temp_ur_id,
                'u_nome' => $temp_usuario['nome'],
                'u_id' => $temp_usuario['id']
            ], $result['ouvintes'][0]);

            $result_loc = getLocOcultaDoRastreador(['pdo' => $pdo, 'id' => 376], 24, null);
            
            $this->assertEquals([
                'id' => $lococulta_id,
                'rastreador_id' => 24,
                'identificacao' => $identificacao,
                'id_inicial' => 1,
                'id_final' => 2,
                'data_inicial' => null,
                'data_final' => null,
                'novos_ouvintes' => true,
                'ouvintes' => [
                    [
                        'ilo_id' => $lococulta_id,
                        'ur_id' => $temp_ur_id,
                        'u_nome' => $temp_usuario['nome'],
                        'u_id' => $temp_usuario['id']
                    ]
                ]
            ], $result_loc['localizacoes_ocultas'][0]);

            $result_sem_ouvintes = setOuvintesLocOculta(['pdo' => $pdo, 'id' => 376], [$lococulta_id], [], 24);
            $this->assertTrue($result_sem_ouvintes['success']);
            $this->assertCount(0, $result_sem_ouvintes['ouvintes']);

            
            $result_loc = getLocOcultaDoRastreador(['pdo' => $pdo, 'id' => 376], 24, null);
            
            $this->assertEquals([
                'id' => $lococulta_id,
                'rastreador_id' => 24,
                'identificacao' => $identificacao,
                'id_inicial' => 1,
                'id_final' => 2,
                'data_inicial' => null,
                'data_final' => null,
                'novos_ouvintes' => true,
                'ouvintes' => []
            ], $result_loc['localizacoes_ocultas'][0]);

            $result_sem_permissao = setOuvintesLocOculta(['pdo' => $pdo, 'id' => 377], [$lococulta_id], [$temp_ur_id], 24);
            $this->assertSame('Usuário não tem permissão para setar ouvintes das localizações ocultas do rastreador:377 - 24', $result_sem_permissao['error']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->removerLocOculta($pdo, $lococulta_id);
            $this->removerUsuarioRastreador($pdo, $temp_ur_id);
            $this->removerUsuario($pdo, $temp_usuario['id']);
        }

        $result_array_invalido = setOuvintesLocOculta(['pdo' => $pdo, 'id' => 376], null, [], 24);
        $this->assertSame('Array de ids de localizações ocultas inválido:', $result_array_invalido['error']);
    }
}