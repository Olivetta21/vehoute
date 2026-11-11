<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/configuracao/f_configuracao.php';

class ConfiguracaoTest extends TestCase {
    function test_get_configuracao_usuario() {
        $result = getConfiguracaoUsuario(['pdo' => getDataBase(), 'id' => -1]);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['configs']);
        foreach ($result['configs'] as $classe) {
            $this->assertArrayHasKey('classe_id', $classe);
            $this->assertArrayHasKey('configs', $classe);
        }
    }

    function test_update_configuracao_usuario_e_rejeita_id_inexistente() {
        $pdo = getDataBase();
        $credenciais = ['pdo' => $pdo, 'id' => -1];
        $config_id = $pdo->query('select id from configuracao order by id limit 1')->fetchColumn();

        $this->assertNotFalse($config_id, "Não foi possível obter um config_id para teste");

        $inexistente = updateConfiguracaoUsuario($credenciais, -999999, 'valor');
        $this->assertArrayHasKey('error', $inexistente);
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $config = $pdo->prepare('select var_type, padrao from configuracao where id = :id');
        $config->execute(['id' => $config_id]);
        $config = $config->fetch(PDO::FETCH_ASSOC);
        $valor = $config['padrao'] ?? ($config['var_type'] === 'boolean' ? 'true' : 'unit-test');

        $result = updateConfiguracaoUsuario($credenciais, $config_id, $valor);
        $this->assertTrue($result['success']);
        $this->assertSame((string) $config_id, (string) $result['config']['config_id']);

        $pdo->prepare('delete from vinculo_configuracao where config_id = :config_id and usuario_id = -1')
            ->execute(['config_id' => $config_id]);
    }
}