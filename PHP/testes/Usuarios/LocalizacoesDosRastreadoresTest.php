<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/rastreadores/localizacoes/f_localizacoesdosrastreadores.php';

class LocalizacoesDosRastreadoresTest extends TestCase {
    function test_get_localizacoes_do_rastreador_unitario() {
        $result = getLocalizacoesDoRastreador(['pdo' => getDataBase(), 'id' => -1], -1);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['localizacoes']);
        if ($result['localizacoes']) {
            $this->assertSame(-23.55052, $result['localizacoes'][0]['l_lat']);
            $this->assertSame(-46.633308, $result['localizacoes'][0]['l_lng']);
            $this->assertArrayHasKey('l_data', $result['localizacoes'][0]);
        } else {
            $this->assertArrayHasKey('message', $result);
        }
    }

    function test_get_localizacoes_sem_resultados() {
        $result = getLocalizacoesDoRastreador(['pdo' => getDataBase(), 'id' => -1], -999999);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['localizacoes']);
        $this->assertArrayHasKey('message', $result);
    }
}