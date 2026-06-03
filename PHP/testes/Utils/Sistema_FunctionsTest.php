<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';

class Sistema_FunctionsTest extends TestCase {

    function test_errorMessage() {
        $this->assertEquals(
            "titulo:conteudo",
            errorMessage("titulo", "conteudo")
        );
    }

    function test_validarEntradasUsuarios() {
        $this->assertTrue(validarSenha("@Senha123") === 1);
        $this->assertTrue(validarSenha("@Senha123😁") === 1);
        $this->assertFalse(validarSenha("@Senhaxyz") === 1);
        $this->assertFalse(validarSenha("@senha123") === 1);
        $this->assertFalse(validarSenha("Senha123") === 1);
        $this->assertFalse(validarSenha("@senhaxyz") === 1);
        $this->assertFalse(validarSenha("@Sen123") === 1);

        $this->assertTrue(validarNomeUsuario("Ivan Luiz") === 1);
        $this->assertFalse(validarNomeUsuario("Ivan Luiz😁") === 1);
        $this->assertFalse(validarNomeUsuario("Ivan") === 1);
        $this->assertFalse(validarNomeUsuario("Ivan 123") === 1);
        $this->assertFalse(validarNomeUsuario("Ivan L12uiz") === 1);

        $this->assertTrue(validarTelefone("+5511999999999") === 1);
        $this->assertTrue(validarTelefone("5511999999999") === 1);
        $this->assertTrue(validarTelefone("11999999999") === 1);
        $this->assertTrue(validarTelefone("999999999") === 1);
        $this->assertFalse(validarTelefone("5511s99999999") === 1);
        $this->assertFalse(validarTelefone("5511😁99999999") === 1);
        $this->assertFalse(validarTelefone("099999999") === 1);

        $this->assertTrue(validarEmail("ivan.luiz@example.com") === 1);
        $this->assertFalse(validarEmail("ivan.luizexample.com") === 1);
        $this->assertFalse(validarEmail("ivan.luiz😁@example.com") === 1);
        $this->assertFalse(validarEmail("ivan.luiz@.com") === 1);
        $this->assertFalse(validarEmail("ivan.luiz @gmail.com") === 1);

        $this->assertTrue(validarLoginUsuario("ivan.luiz") === 1);
        $this->assertFalse(validarLoginUsuario("ivan.luiz😁") === 1);

        $this->assertTrue(validarOtp("AAAAAAAA") === 1);
        $this->assertFalse(validarOtp("aA") === 1);
        $this->assertFalse(validarOtp("AAAAAAAÇ") === 1);
        $this->assertFalse(validarOtp("AAAAAAAAA") === 1);
        $this->assertFalse(validarOtp("AAAA AAAA") === 1);
        $this->assertFalse(validarOtp("AAA AAAA") === 1);
        $this->assertFalse(validarOtp("AAAAAAA") === 1);
    }

    function test_requestPost() {
        $this->assertEquals( null, getRequestValue("unit_test", "is_string") );
        $_POST['unit_test'] = json_encode("test");
        $this->assertEquals( null, getRequestValue("unit_test", "is_int") );
        $this->assertEquals( "test", getRequestValue("unit_test", "is_string") );
        $this->assertEquals( "test", getRequestValue("unit_test") );
    }

    function test_ramdonString() {
        $this->assertEquals( 256, strlen(getRandomHex()) );
        $this->assertEquals( 256, strlen(getRandomHex(256)) );
        $this->assertEquals( 128, strlen(getRandomHex(128)) );
    }
}