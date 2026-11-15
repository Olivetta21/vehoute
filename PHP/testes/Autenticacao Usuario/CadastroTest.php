<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Login_Cadastro/f_cadastro.php';

class CadastroTest extends TestCase {

    function test_pegar_banco_de_dados() {
        $pdo = getDataBase();
        $this->assertNull($pdo->errorCode());
    }

    function test_enviar_email_otp() {
        $mail = "test@" . uniqid() . ".com.brasiltest";
        $this->assertIsArray(enviarEmailOTP("Teste da Silva", $mail, "otpteste"));
    }

    function test_gerar_otp_e_enviar_email_e_validar_otp() {
        $pdo = getDataBase();
        $this->assertNull($pdo->errorCode());

        $mail = "test@" . uniqid() . ".com.brasiltest";
        $result = gerarOTPeEnviarEmail("Teste da Silva", $mail);
        $stmt = $pdo->prepare("select otp from usuario_cadastrando where email = ?");
        $stmt->execute([$mail]);
        $otp = $stmt->fetchColumn();
        
        $this->assertEquals(["success"=>true, "otp"=>$otp], $result);

        $validar_result = verificarOTP($otp);
        $this->assertEquals(
            ["success"=>true, "dados"=>["nome"=> "Teste da Silva", "email"=> $mail, "otp"=> $otp]],
            $validar_result
        );

        $stmt = $pdo->prepare("delete from usuario_cadastrando where email = ?");
        $stmt->execute([$mail]);

        $this->assertEquals(1, $stmt->rowCount());
    }

    function test_fazer_cadastro_processo_completo_success() {
        $pdo = getDataBase();
        $this->assertNull($pdo->errorCode());
        
        $mail = "test@" . uniqid() . ".com.brasiltest";
        $result = gerarOTPeEnviarEmail("Teste da Silva", $mail);
        $stmt = $pdo->prepare("select otp from usuario_cadastrando where email = ?");
        $stmt->execute([$mail]);
        $otp = $stmt->fetchColumn();
        $this->assertEquals(["success"=>true, "otp"=>$otp], $result);

        $validar_otp_result = verificarOTP($otp);
        $this->assertEquals(
            ["success"=>true, "dados"=>["nome"=> "Teste da Silva", "email"=> $mail, "otp"=> $otp]],
            $validar_otp_result
        );

        $finalisar_result = finalizarCadastro(
            "Teste da Silva",
            $mail,
            "11999999999",
            "Testelogin",
            "senha123A#",
            $otp,
            'identidade123',
            1
        );

        $this->assertEquals(["success"=>true], $finalisar_result);
        
        $stmt = $pdo->prepare("select id from usuario where nome like 'Teste%' and email like '%@%brasiltest' and login like 'Teste%login' and telefone = '11999999999'");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertGreaterThan(0 , $stmt->rowCount());

        $placeholders = implode(",", array_fill(0, count($users), "?"));

        $stmt = $pdo->prepare("DELETE FROM vinc_perm_usuario WHERE usuario_id IN ($placeholders)");
        $stmt->execute($users);
        
        $this->assertGreaterThan(0 , $stmt->rowCount());

        $stmt = $pdo->prepare("delete from usuario where id in ($placeholders)");
        $stmt->execute($users);

        $this->assertGreaterThan(0 , $stmt->rowCount());
    }

    function test_get_simple_legal_ident_tipo() {
        $result = getSimpleLegalIdentTipoCadastro();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }


}