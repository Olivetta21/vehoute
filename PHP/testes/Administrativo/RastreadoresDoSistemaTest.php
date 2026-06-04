<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Administrativo/rastreadores/f_rastreadores.php';

class RastreadoresDoSistemaTest extends TestCase {
    function test_get_rastreadores() {
        $pdo = getDataBase();
        $result = getRastreadores($pdo, 'token_publico123');

        $this->assertEquals(true, $result["success"]);
        $this->assertIsArray($result["rastreadores"]);

        $rastreador_1 = null;
        foreach ($result["rastreadores"] as $rastreador) {
            if ($rastreador["id"] == 1) {
                $rastreador_1 = $rastreador;
                break;
            }
        }
        $this->assertEquals([
                "id"=>1,"hardware"=>"Rastreador Exemplo","token"=>"token123","token_publico"=>"token_publico123",
                "obs"=>'Observações sobre o rastreador',"status"=>55,"ativo"=>true,"u_id"=>1,"nome"=>"Ivan Luiz","qnto"=>3
            ],
            $rastreador_1
        );
    }

    function test_toggle_ativo_rastreador() {
        $pdo = getDataBase();

        $toggle_ativo = toggleRastreadorAtivo($pdo, 1);
        $this->assertTrue($toggle_ativo['success']);
        $this->assertFalse($toggle_ativo['rastreador']['ativo']);

        $toggle_ativo_volta = toggleRastreadorAtivo($pdo, 1);
        $this->assertTrue($toggle_ativo_volta['success']);
        $this->assertTrue($toggle_ativo_volta['rastreador']['ativo']);
    }

    function test_adicionar_rastreador() {
        $pdo = getDataBase();

        $novo_hardware = 'Rastreador Teste ' . uniqid();
        $novo_token = "token_teste_" . uniqid();
        $novo_token_publico = "token_publico_teste_" . uniqid();
        $nova_senha = "senha_teste_" . uniqid();
        $nova_obs = "Observações sobre o rastreador teste";
        $novo_status = 55;
        $novo_dono_id = 1;

        $result_add = adicionarRastreador($pdo, $novo_hardware, $novo_token, $novo_token_publico, $nova_senha, $nova_obs, $novo_status, $novo_dono_id);
        $this->assertTrue($result_add['success']);
        
        $result = getRastreadores($pdo, $novo_token_publico);

        $this->assertEquals(true, $result["success"]);
        $this->assertIsArray($result["rastreadores"]);

        $rastreador_novo = null;
        foreach ($result["rastreadores"] as $rastreador) {
            if ($rastreador["id"] == $result_add['rastreador']['id']) {
                $rastreador_novo = $rastreador;
                break;
            }
        }
        $this->assertEquals([
                "id"=>$result_add['rastreador']['id'],"hardware"=>$novo_hardware,"token"=>$novo_token,"token_publico"=>$novo_token_publico,
                "obs"=>$nova_obs,"status"=>$novo_status,"ativo"=>true,"u_id"=>$novo_dono_id,"nome"=>"Ivan Luiz","qnto"=>0
            ],
            $rastreador_novo
        );

        $stmt = $pdo->prepare("delete from rastreador where token ilike :token");
        $stmt->execute(["token" => 'token_teste_%']);

        $this->assertGreaterThanOrEqual(1, $stmt->rowCount());
    }

}