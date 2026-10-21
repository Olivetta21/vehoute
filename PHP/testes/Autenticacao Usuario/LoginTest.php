<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Login_Cadastro/f_login.php';

class LoginTest extends TestCase {

    function test_fazer_login_sucesso() {
        $login = 'donoexemplo';
        $senha = '@Ivan123';
        $result = fazerLogin($login, $senha);
        $access_token = $result['usuario']['access_token'];
        $this->assertNotNull($access_token);
        $permissoes = [1, 3, 5];
        foreach ($result['usuario']['permissoes'] as $permissao) {
            $this->assertContains($permissao, $permissoes);
        }
        $result['usuario']['permissoes'] = $permissoes;
        $this->assertEquals(
            ["success"=> true, "usuario"=>["id"=>1,"nome"=>"Ivan Luiz","login"=>"donoexemplo","access_token"=>$access_token,"permissoes"=>$permissoes]],
            $result
        );
    }

    function test_fazer_login_falha() {
        $result = fazerLogin("donoexemplo","senhaerrada");
        $this->assertEquals(
            ["error"=>"Usuario não encontrado:login=donoexemplo"],
            $result
        );
    }

    function test_verifica_access_token() {
        $access_token = "tk1";
        $result = testAccessToken($access_token);
        $permissoes = [1, 3, 5];
        foreach ($result['usuario']['permissoes'] as $permissao) {
            $this->assertContains($permissao, $permissoes);
        }
        $result['usuario']['permissoes'] = $permissoes;
        $this->assertEquals(
            ["success"=> true, "usuario"=>["id"=>1,"nome"=>"Ivan Luiz","login"=>"donoexemplo","access_token"=>"tk1","permissoes"=>$permissoes]],
            $result
        );   
    }

    function test_verifica_access_token_falha() {
        $access_token = "tk2";
        $result = testAccessToken($access_token);
        
        $this->assertEquals(
            ["error"=> "Access_token inválido:access_token=tk2"],
            $result
        );
    }

}