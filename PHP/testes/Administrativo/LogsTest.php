<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Administrativo/logseauditoria/f_logs.php';

class LogsTest extends TestCase {
    function test_get_logs_com_filtros() {
        $pdo = getDataBase();
        $stmt = $pdo->prepare("insert into errors_log (titulo, mensagem, u_access_token, ip, url) values (:titulo, :mensagem, 'tk1', :ip, '/unit-test') returning id");
        $stmt->execute([
            'titulo' => 'Titulo Unit Test',
            'mensagem' => 'Mensagem Unit Test',
            'ip' => '192.0.2.1'
        ]);
        $log_id = $stmt->fetchColumn();

        try {
            $result = getLogsDoSistema(['pdo' => $pdo, 'id' => -1], 'Titulo Unit', 'Mensagem Unit', '192.0.2');

            $this->assertTrue($result['success']);
            $this->assertCount(1, $result['logs']);
            $this->assertSame('Titulo Unit Test', $result['logs'][0]['titulo']);
            $this->assertSame('Usuario Unit Test1', $result['logs'][0]['u_nome']);
            $this->assertSame('/unit-test', $result['logs'][0]['url']);
        } finally {
            $pdo->prepare('delete from errors_log where id = :id')->execute(['id' => $log_id]);
        }
    }

    function test_get_simple_titulos() {
        $pdo = getDataBase();
        $stmt = $pdo->prepare("insert into errors_log (titulo, mensagem, u_access_token, ip) values (:titulo, 'teste', 'tk1', '192.0.2.2') returning id");
        $stmt->execute(['titulo' => 'Titulo Unit Test']);
        $log_id = $stmt->fetchColumn();

        try {
            $result = getSimpleTitulos(['pdo' => $pdo, 'id' => -1], 'unit');

            $this->assertTrue($result['success']);
            $this->assertContains('titulo unit test', $result['titulos']);
        } finally {
            $pdo->prepare('delete from errors_log where id = :id')->execute(['id' => $log_id]);
        }
    }
}