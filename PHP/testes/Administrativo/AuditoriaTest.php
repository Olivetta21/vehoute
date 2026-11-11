<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Administrativo/logseauditoria/f_auditoria.php';

class AuditoriaTest extends TestCase {
    function test_get_auditoria_com_filtros() {
        $pdo = getDataBase();
        $stmt = $pdo->prepare("insert into audit_log (usuario_id, tabela, acao, antes, depois, sucesso) values (-1, :tabela, :acao, :antes, :depois, true) returning id");
        $stmt->execute([
            'tabela' => 'unit_test_auditoria_' . uniqid(),
            'acao' => 'acao_unit_test',
            'antes' => '{"origem":"teste"}',
            'depois' => '{"resultado":"teste"}'
        ]);
        $audit_id = $stmt->fetchColumn();

        try {
            $result = getAuditoriaDoSistema(['pdo' => $pdo, 'id' => -1], 'unit_test_auditoria', 'resultado', null, null, true);

            $this->assertTrue($result['success']);
            $this->assertCount(1, $result['auditorias']);
            $this->assertSame('acao_unit_test', $result['auditorias'][0]['acao']);
            $this->assertSame('Usuario Unit Test1', $result['auditorias'][0]['nome']);
        } finally {
            $pdo->prepare('delete from audit_log where id = :id')->execute(['id' => $audit_id]);
        }
    }

    function test_get_simple_tabelas_auditoria() {
        $result = getSimpleTabelasAuditoria(['pdo' => getDataBase(), 'id' => -1], 'audit');

        $this->assertTrue($result['success']);
        $this->assertContains('audit_log', $result['tabelas']);
    }
}