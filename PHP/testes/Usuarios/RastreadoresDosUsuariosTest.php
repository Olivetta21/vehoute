<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../backend/include_me.php';
require_once __DIR__ . '/../../backend/Usuario/rastreadores/f_rastreadoresusuarios.php';

class RastreadoresDosUsuariosTest extends TestCase {
	private function criarRastreadorTemporario($pdo, $prefixo, $dono_id) {
		$sufixo = uniqid();
		$hardware = $prefixo . ' ' . $sufixo;
		$token = 'token_' . $sufixo;
		$token_publico = 'tokenPublico_' . $sufixo;
		$senha = 'senha_' . $sufixo;
		$obs = 'Rastreador temporário ' . $prefixo;

		$stmt = $pdo->prepare('insert into rastreador (hardware, token, token_publico, senha, obs, status, ativo, dono_id) values (:hardware, :token, :token_publico, :senha, :obs, :status, true, :dono_id) returning id');
		$stmt->execute([
			'hardware' => $hardware,
			'token' => $token,
			'token_publico' => $token_publico,
			'senha' => $senha,
			'obs' => $obs,
			'status' => 2,
			'dono_id' => $dono_id
		]);

		return ['id' => (int) $stmt->fetchColumn(), 'hardware' => $hardware, 'token' => $token, 'token_publico' => $token_publico, 'senha' => $senha];
	}

	private function removerRastreador($pdo, $rastreador_id) {
		$stmt = $pdo->prepare('delete from rastreador where id = :id');
		$stmt->execute(['id' => $rastreador_id]);
	}

	private function criarUsuarioTemporario($pdo, $prefixo) {
		$sufixo = uniqid();
		$stmt = $pdo->prepare('insert into usuario (nome, login, senha, legal_ident_id, ativo, email, telefone) values (:nome, :login, :senha, :legal_ident_id, true, :email, null) returning id');
		$stmt->execute([
			'nome' => $prefixo . ' ' . $sufixo,
			'login' => 'login_' . $sufixo,
			'senha' => 'Aa1#' . $sufixo,
			'legal_ident_id' => 70,
			'email' => 'temp_' . $sufixo . '@gmail.com'
		]);
		return ['id' => (int) $stmt->fetchColumn(), 'nome' => $prefixo . ' ' . $sufixo];
	}

	private function removerUsuario($pdo, $usuario_id) {
		$stmt = $pdo->prepare('delete from usuario where id = :id');
		$stmt->execute(['id' => $usuario_id]);
	}

	private function criarUsuarioRastreadorTemporario($pdo, $usuario_id, $rastreador_id, $nome, $status) {
		$stmt = $pdo->prepare('insert into usuario_rastreador (usuario_id, rastreador_id, nome, status, ativo, loc_temporeal, loc_salvos) values (:usuario_id, :rastreador_id, :nome, :status, true, true, true) returning id');
		$stmt->execute(['usuario_id' => $usuario_id, 'rastreador_id' => $rastreador_id, 'nome' => $nome, 'status' => $status]);
		return (int) $stmt->fetchColumn();
	}

	private function removerUsuarioRastreador($pdo, $ur_id) {
		$stmt = $pdo->prepare('delete from usuario_rastreador where id = :id');
		$stmt->execute(['id' => $ur_id]);
	}

	private function buscarStatusUsuarioRastreador($pdo, $ur_id) {
		$stmt = $pdo->prepare('select status from usuario_rastreador where id = :id');
		$stmt->execute(['id' => $ur_id]);
		return $stmt->fetchColumn();
	}

	private function buscarStatusRastreador($pdo, $rastreador_id) {
		$stmt = $pdo->prepare('select status from rastreador where id = :id');
		$stmt->execute(['id' => $rastreador_id]);
		return $stmt->fetchColumn();
	}

	private function contarUsuarioRastreador($pdo, $ur_id) {
		$stmt = $pdo->prepare('select count(*) from usuario_rastreador where id = :id');
		$stmt->execute(['id' => $ur_id]);
		return (int) $stmt->fetchColumn();
	}

	function test_get_rastreadores_do_usuario() {
		$pdo = getDataBase();
		$credenciais = ["pdo" => $pdo, "id" => 2];
		$result = getRastreadoresDoUsuario($credenciais, 'Alpha');
		$this->assertTrue($result['success']);
		$this->assertIsArray($result['rastreadores']);
		$result_vazio = getRastreadoresDoUsuario($credenciais, 'NonExistentXYZ');
		$this->assertTrue($result_vazio['success']);
		$this->assertCount(0, $result_vazio['rastreadores']);
	}

	function test_check_rastreador_do_usuario() {
		$pdo = getDataBase();
		$credenciais = ["pdo" => $pdo, "id" => 2];
		$result = checkRastreadorDoUsuario($credenciais, 'tokenPublicoAlpha123', '123');
		$this->assertIsArray($result);
		$result_negativo = checkRastreadorDoUsuario($credenciais, 'tokenInvalido', 'senhaInvalida');
		$this->assertArrayHasKey('error', $result_negativo);
	}

	function test_usuario_adiciona_um_rastreador() {
		$pdo = getDataBase();
		$credenciais = ["pdo" => $pdo, "id" => 2];
		$result = usuarioAdicionaUmRastreador($credenciais, 3, 3, 'tokenPublicoBeta456', 'senhaBeta456', 2, 'Teste ' . uniqid());
		if ($result !== null && isset($result['usuario_rastreador'])) {
			$stmt = $pdo->prepare('delete from usuario_rastreador where id = :id');
			$stmt->execute(['id' => $result['usuario_rastreador']['id']]);
		}
		$result_negativo = usuarioAdicionaUmRastreador($credenciais, 999999, 3, 'token_fake', 'senha_fake', 2, 'Teste');
		$this->assertThat($result_negativo, $this->logicalOr($this->isNull(), $this->arrayHasKey('error')));
	}

	function test_delete_usuario_rastreador() {
		$pdo = getDataBase();
		$temp_usuario = $this->criarUsuarioTemporario($pdo, 'Delete');
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 3, 'Del', 2);
		try {
			$pdo->beginTransaction();
			$result = deleteUsuarioRastreador($pdo, $temp_ur);
			$this->assertTrue($result['success']);
			$pdo->commit();
		} finally {
			if ($pdo->inTransaction()) { $pdo->rollBack(); }
			$this->removerUsuario($pdo, $temp_usuario['id']);
		}
	}

	function test_validar_usuario_correto_for_accept_decline() {
		$pdo = getDataBase();
		$temp_usuario = $this->criarUsuarioTemporario($pdo, 'Accept');
		$credenciais = ["pdo" => $pdo, "id" => $temp_usuario['id']];
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 3, 'Prop', 4);
		try {
			$this->assertTrue(validarUsuarioCorretoForAcceptDecline($credenciais, $temp_ur));
			$this->assertFalse(validarUsuarioCorretoForAcceptDecline(["pdo" => $pdo, "id" => 1], $temp_ur));
		} finally {
			$this->removerUsuarioRastreador($pdo, $temp_ur);
			$this->removerUsuario($pdo, $temp_usuario['id']);
		}
	}

	function test_usuario_aceita_proposta_de_ouvinte() {
		$pdo = getDataBase();
		$temp_usuario = $this->criarUsuarioTemporario($pdo, 'Aceita');
		$credenciais = ["pdo" => $pdo, "id" => $temp_usuario['id']];
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 3, 'Prop', 4);
		try {
			$result = usuarioAceitaPropostaDeOuvinte($credenciais, $temp_ur);
			$this->assertTrue($result['success']);
			$this->assertSame('2', (string) $this->buscarStatusUsuarioRastreador($pdo, $temp_ur));
		} finally {
			if ($pdo->inTransaction()) { $pdo->rollBack(); }
			$this->removerUsuarioRastreador($pdo, $temp_ur);
			$this->removerUsuario($pdo, $temp_usuario['id']);
		}
	}

	function test_usuario_recusa_proposta_de_ouvinte() {
		$pdo = getDataBase();
		$temp_usuario = $this->criarUsuarioTemporario($pdo, 'Recusa');
		$credenciais = ["pdo" => $pdo, "id" => $temp_usuario['id']];
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 3, 'Prop', 4);
		try {
			$result = usuarioRecusaPropostaDeOuvinte($credenciais, $temp_ur);
			$this->assertTrue($result['success']);
			$this->assertSame(0, $this->contarUsuarioRastreador($pdo, $temp_ur));
		} finally {
			if ($pdo->inTransaction()) { $pdo->rollBack(); }
			$this->removerUsuario($pdo, $temp_usuario['id']);
		}
	}

	function test_validar_usuario_correto_for_transferencia() {
		$pdo = getDataBase();
		$temp_rastreador = $this->criarRastreadorTemporario($pdo, 'Transfer', 3);
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, 2, $temp_rastreador['id'], 'Trans', 5);
		$stmt = $pdo->prepare('update rastreador set status = 3 where id = :id');
		$stmt->execute(['id' => $temp_rastreador['id']]);
		try {
			$rastreador_id = validarUsuarioCorretoForTransferencia(["pdo" => $pdo, "id" => 2], $temp_ur);
			$this->assertEquals($temp_rastreador['id'], $rastreador_id);
		} finally {
			$this->removerUsuarioRastreador($pdo, $temp_ur);
			$this->removerRastreador($pdo, $temp_rastreador['id']);
		}
	}

	function test_accept_transferencia_de_posse() {
		$pdo = getDataBase();
		$temp_rastreador = $this->criarRastreadorTemporario($pdo, 'Accept', 3);
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, 2, $temp_rastreador['id'], 'Acc', 5);
		$stmt = $pdo->prepare('update rastreador set status = 3 where id = :id');
		$stmt->execute(['id' => $temp_rastreador['id']]);
		try {
			$result = acceptTransferenciaDePosse(["pdo" => $pdo, "id" => 2], $temp_ur);
			$this->assertTrue($result['success']);
			$this->assertSame('1', (string) $this->buscarStatusUsuarioRastreador($pdo, $temp_ur));
		} finally {
			if ($pdo->inTransaction()) { $pdo->rollBack(); }
			$this->removerUsuarioRastreador($pdo, $temp_ur);
			$this->removerRastreador($pdo, $temp_rastreador['id']);
		}
	}

	function test_decline_transferencia_de_posse() {
		$pdo = getDataBase();
		$temp_rastreador = $this->criarRastreadorTemporario($pdo, 'Decline', 3);
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, 2, $temp_rastreador['id'], 'Dec', 5);
		$stmt = $pdo->prepare('update rastreador set status = 3 where id = :id');
		$stmt->execute(['id' => $temp_rastreador['id']]);
		try {
			$result = declineTransferenciaDePosse(["pdo" => $pdo, "id" => 2], $temp_ur);
			$this->assertTrue($result['success']);
			$this->assertSame('2', (string) $this->buscarStatusUsuarioRastreador($pdo, $temp_ur));
		} finally {
			if ($pdo->inTransaction()) { $pdo->rollBack(); }
			$this->removerUsuarioRastreador($pdo, $temp_ur);
			$this->removerRastreador($pdo, $temp_rastreador['id']);
		}
	}

	function test_validar_ouvinte_correto_for_delete_ur() {
		$pdo = getDataBase();
		$temp_usuario = $this->criarUsuarioTemporario($pdo, 'Ouvinte');
		$credenciais = ["pdo" => $pdo, "id" => $temp_usuario['id']];
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 3, 'Ouv', 2);
		try {
			$this->assertTrue(validarOuvinteCorretoForDeleteUR($credenciais, $temp_ur));
			$this->assertFalse(validarOuvinteCorretoForDeleteUR(["pdo" => $pdo, "id" => 1], $temp_ur));
		} finally {
			$this->removerUsuarioRastreador($pdo, $temp_ur);
			$this->removerUsuario($pdo, $temp_usuario['id']);
		}
	}

	function test_excluir_rastreador_do_ouvinte() {
		$pdo = getDataBase();
		$temp_usuario = $this->criarUsuarioTemporario($pdo, 'Excluir');
		$credenciais = ["pdo" => $pdo, "id" => $temp_usuario['id']];
		$temp_ur = $this->criarUsuarioRastreadorTemporario($pdo, $temp_usuario['id'], 3, 'Exc', 2);
		try {
			$result = excluirRastreadorDoOuvinte($credenciais, $temp_ur);
			$this->assertTrue($result['success']);
			$this->assertSame(0, $this->contarUsuarioRastreador($pdo, $temp_ur));
		} finally {
			if ($pdo->inTransaction()) { $pdo->rollBack(); }
			$this->removerUsuario($pdo, $temp_usuario['id']);
		}
	}
}
