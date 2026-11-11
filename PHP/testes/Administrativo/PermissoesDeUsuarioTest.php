<?php
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PermissoesDeUsuarioTest extends TestCase {
    private function carregarFuncoes() {
        require_once __DIR__ . '/../../backend/include_me.php';
        require_once __DIR__ . '/../../backend/Administrativo/permissoes/f_permissoes_usuario.php';
    }

    function test_get_permissoes_por_entidade_e_filtro() {
        $this->carregarFuncoes();
        $result = getPermissoesUsuario(['pdo' => getDataBase(), 'id' => -1], 'entidade', 'Unit Test Group1');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['agrupamento']);
        $this->assertSame(-1, (int) $result['agrupamento'][0]['id']);
        $this->assertFalse($result['agrupamento'][0]['is_user']);
        $this->assertNotEmpty($result['agrupamento'][0]['permissoes']);
    }

    function test_crud_de_grupo_e_vinculo_de_permissao() {
        $this->carregarFuncoes();
        $pdo = getDataBase();
        $credenciais = ['pdo' => $pdo, 'id' => -1];
        $nome = 'Grupo Usuario Unit ' . uniqid();

        $grupo = addGrupo($credenciais, $nome);
        $this->assertTrue($grupo['success']);
        $grupo_id = (int) $grupo['grupo']['id'];

        try {
            $this->assertTrue(addUsuarioNoGrupo($credenciais, -1, $grupo_id)['success']);
            $this->assertTrue(addVinculoPermissao($credenciais, $grupo_id, false, false, 1)['success']);
            $this->assertTrue(removerUsuarioDoGrupo($credenciais, -1, $grupo_id)['success']);
        } finally {
            $this->assertTrue(deleteGrupo($credenciais, $grupo_id)['success']);
        }
    }
}