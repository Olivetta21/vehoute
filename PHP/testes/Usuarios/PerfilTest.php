<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/perfil/f_perfil.php';

class PerfilTest extends TestCase {
    function test_get_perfil_usuario_unitario() {
        $result = getPerfilUsuario(['pdo' => getDataBase(), 'id' => -1]);

        $this->assertTrue($result['success']);
        $this->assertSame(-1, (int) $result['perfil']['id']);
        $this->assertSame('Usuario Unit Test1', $result['perfil']['nome']);
        $this->assertArrayHasKey('total_rastreadores', $result['perfil']);
        $this->assertArrayHasKey('total_ouvinte', $result['perfil']);
    }

    function test_update_perfil_e_validacoes() {
        $pdo = getDataBase();
        $credenciais = ['pdo' => $pdo, 'id' => -1];

        $result = updatePerfilUsuario($credenciais, ['nome' => 'Usuario Atualizado']);
        $this->assertTrue($result['success']);
        $this->assertSame('Usuario Atualizado', $result['perfil']['nome']);

        $invalido = updatePerfilUsuario($credenciais, ['email' => 'email-invalido']);
        $this->assertArrayHasKey('error', $invalido);

        $pdo->prepare('update usuario set nome = :nome where id = -1')
            ->execute(['nome' => 'Usuario Unit Test1']);
    }

    function test_get_e_validar_identidade_legal() {
        $credenciais = ['pdo' => getDataBase(), 'id' => -1];
        $tipos = getSimpleLegalIdentTipo($credenciais);
        $this->assertTrue($tipos['success']);
        $this->assertNotEmpty($tipos['legal_ident_tipo']);

        $this->assertTrue(validarLegalIdent('qualquer identidade', '.+'));
        $this->assertFalse(validarLegalIdent('', '.+'));
    }
}