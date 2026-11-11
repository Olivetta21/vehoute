<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Administrativo/usuarios/f_usuarios.php';

class UsuariosDoSistemaTest extends TestCase {
    function test_get_usuarios() {
        $pdo = getDataBase();
        $result = getUsuarios($pdo);

        $this->assertEquals(true, $result["success"]);
        $this->assertIsArray($result["usuarios"]);

        $usuario_2 = null;
        foreach ($result["usuarios"] as $usuario) {
            if ($usuario["id"] == -2) {
                $usuario_2 = $usuario;
                break;
            }
        }
        $this->assertEquals([
                "id"=>-2,"adm"=>false,"nome"=>"Usuario Unit Test2","ativo"=>true,
                "email"=>"usuariounittest2@com.com.com","telefone"=>null,"legal_ident_id"=>2,"tipo_ident"=>2,
                "identidade"=>"000.000.000-00","descricao_ident"=>"CPF","qnt_posse_rastr"=>1,"ouvinte_qnt_rastr"=>0
            ],
            $usuario_2
        );
    }

    function test_get_usuarios_por_nome() {
        $pdo = getDataBase();
        $result = getUsuarios($pdo, 'Usuario Unit Test');

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(2, count($result['usuarios']));
    }

    function test_toggle_ativo_adm_e_adicionar_usuario() {
        $pdo = getDataBase();

        $toggle_ativo = toggleUsuarioAtivo($pdo, -2);
        $this->assertTrue($toggle_ativo['success']);
        $this->assertFalse($toggle_ativo['usuario']['ativo']);

        $toggle_ativo_volta = toggleUsuarioAtivo($pdo, -2);
        $this->assertTrue($toggle_ativo_volta['success']);
        $this->assertTrue($toggle_ativo_volta['usuario']['ativo']);

        $toggle_adm = toggleUsuarioAdm($pdo, -2);
        $this->assertTrue($toggle_adm['success']);
        $this->assertTrue($toggle_adm['usuario']['adm']);

        $toggle_adm_volta = toggleUsuarioAdm($pdo, -2);
        $this->assertTrue($toggle_adm_volta['success']);
        $this->assertFalse($toggle_adm_volta['usuario']['adm']);

        $novo_nome = 'Teste Admin';
        $novo_email = 'teste.admin.' . uniqid() . '@example.com.brasil';
        $novo_identidade = '999.999.999-99';

        $adicionado = adicionarUsuario($pdo, $novo_nome, $novo_email, $novo_identidade, 2, true);
        $this->assertTrue($adicionado['success']);
        $this->assertArrayHasKey('credenciais', $adicionado);
        $this->assertArrayHasKey('login', $adicionado['credenciais']);
        $this->assertArrayHasKey('senha', $adicionado['credenciais']);

        $usuario_id = $adicionado['usuario']['id'];
        $legal_ident_id = $adicionado['usuario']['legal_ident_id'];

        $stmt = $pdo->prepare('delete from administrador where id = :id');
        $stmt->execute(['id' => $usuario_id]);

        $stmt = $pdo->prepare('delete from usuario where id = :id');
        $stmt->execute(['id' => $usuario_id]);

        $stmt = $pdo->prepare('delete from legal_ident where id = :id');
        $stmt->execute(['id' => $legal_ident_id]);

        $this->assertGreaterThanOrEqual(1, $stmt->rowCount());
    }
}