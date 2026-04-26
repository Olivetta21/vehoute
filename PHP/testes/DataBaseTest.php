<?php
use PHPUnit\Framework\TestCase;
require __DIR__ . '/../backend/include_me.php';

class DataBaseTest extends TestCase {
    public function test_pegar_usuario_por_token() {
        $_POST['access_token'] = json_encode('tk1');
        $usuario = getCredentials();
        $this->assertEquals(['id'=>1, 'nome'=>'Ivan Luiz'], array_intersect_key($usuario, ['id'=>1, 'nome'=>1]));
        $this->assertInstanceOf(PDO::class, $usuario['pdo']);
    }
}