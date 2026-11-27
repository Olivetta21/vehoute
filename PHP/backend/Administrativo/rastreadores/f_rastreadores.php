<?php

function getRastreadores($pdo, $filtro = null) {
	$Audit = (new Auditoria())->add('rastreador', 'getRastreadores', null, ["filtro" => $filtro], false);
	$filtro = normalizarFiltroTexto($filtro);

	$sql = "SELECT * from vw_rastreadores_do_sistema";
	$params = [];

	if ($filtro !== null) {
		$sql .= " where hardware ilike :filtro or nome ilike :filtro or token_publico ilike :filtro or cast(id as text) ilike :filtro";
		$params["filtro"] = '%' . $filtro . '%';
	}

	$sql .= " order by hardware nulls last, id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);

	$rastreadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$Audit->depois(["qnt_rastreadores" => count($rastreadores)]);
	$Audit->sucesso(true);
	return ["success" => true, "rastreadores" => $rastreadores];
}

function getSimpleRastreadores($pdo, $filtro = null) {
	$Audit = (new Auditoria())->add('rastreador', 'getSimpleRastreadores', null, ["filtro" => $filtro], false);
	$filtro = normalizarFiltroTexto($filtro);

	$sql = "SELECT id, hardware as nome from rastreador where ativo = true";
	$params = [];

	if ($filtro !== null) {
		$sql .= " and hardware ilike :filtro";
		$params["filtro"] = '%' . $filtro . '%';
	}

	$sql .= " order by hardware nulls last, id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);

	$rastreadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$Audit->depois(["qnt_rastreadores" => count($rastreadores)]);
	$Audit->sucesso(true);
	return ["success" => true, "rastreadores" => $rastreadores];
}

function toggleRastreadorAtivo($pdo, $rastreador_id) {
	$Audit = (new Auditoria())->add('rastreador', 'toggleRastreadorAtivo', ["id" => $rastreador_id], null, false);
	if (!validarIdPositivo($rastreador_id)) {
		return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
	}

	try {
		$pdo->beginTransaction();

		$stmt = $pdo->prepare("update rastreador set ativo = not ativo where id = :id returning id, ativo");
		$stmt->execute(["id" => $rastreador_id]);

		if ($stmt->rowCount() !== 1) {
			$pdo->rollBack();
			return ["error" => errorMessage("Rastreador não encontrado", $rastreador_id)];
		}

		$rastreador = $stmt->fetch(PDO::FETCH_ASSOC);
		$pdo->commit();

		$Audit->depois($rastreador);
		$Audit->sucesso(true);
		return ["success" => true, "rastreador" => $rastreador];
	} catch (Exception $e) {
		if ($pdo?->inTransaction()) {
			$pdo->rollBack();
		}

		return ["error" => errorMessage("Error in toggleRastreadorAtivo", $e->getMessage())];
	}
}

function adicionarRastreador($pdo, $hardware, $token, $token_publico, $senha, $obs, $status, $dono_id) {
	$Audit = (new Auditoria())->add('rastreador', 'adicionarRastreador', null, ["hardware" => $hardware, "token_publico" => $token_publico, "dono_id" => $dono_id], false);
	$hardware = normalizarFiltroTexto($hardware);
	$token = normalizarFiltroTexto($token);
	$token_publico = normalizarFiltroTexto($token_publico);
	$senha = normalizarFiltroTexto($senha);
	$obs = normalizarFiltroTexto($obs);

	if ($hardware === null) {
		return ["error" => errorMessage("Hardware inválido", $hardware)];
	}

	if ($token === null) {
		return ["error" => errorMessage("Token inválido", $token)];
	}

	if ($token_publico === null) {
		return ["error" => errorMessage("Token público inválido", $token_publico)];
	}

	if (!validarIdPositivo($status)) {
		return ["error" => errorMessage("Status inválido", $status)];
	}

	if (!validarIdPositivo($dono_id)) {
		return ["error" => errorMessage("Dono inválido", $dono_id)];
	}

	try {
		$pdo->beginTransaction();

		$stmt = $pdo->prepare("select 1 from usuario where id = :id");
		$stmt->execute(["id" => $dono_id]);

		if ($stmt->fetchColumn() === false) {
			$pdo->rollBack();
			return ["error" => errorMessage("Usuário dono não encontrado", $dono_id)];
		}

		$stmt = $pdo->prepare("select 1 from rastreador where token = :token or token_publico = :token_publico");
		$stmt->execute(["token" => $token, "token_publico" => $token_publico]);
		if ($stmt->fetchColumn() !== false) {
			$pdo->rollBack();
			return ["error" => errorMessage("Token ou token público já cadastrado", $token_publico)];
		}

		$stmt = $pdo->prepare(
			"insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id)
			 values (:hardware, :token, :token_publico, crypt(:senha, gen_salt('bf')), :obs, :status, :dono_id)
			 returning id, hardware, token, token_publico, obs, status, ativo, dono_id"
		);
		$stmt->execute([
			"hardware" => $hardware,
			"token" => $token,
			"token_publico" => $token_publico,
			"senha" => $senha,
			"obs" => $obs,
			"status" => $status,
			"dono_id" => $dono_id
		]);

		$rastreador = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$rastreador) {
			$pdo->rollBack();
			return ["error" => errorMessage("Falha ao cadastrar rastreador", $hardware)];
		}

		$pdo->commit();

		$Audit->depois(["rastreador" => $rastreador]);
		$Audit->sucesso(true);
		return ["success" => true, "rastreador" => $rastreador];
	} catch (Exception $e) {
		if ($pdo?->inTransaction()) {
			$pdo->rollBack();
		}

		return ["error" => errorMessage("Error in adicionarRastreador", $e->getMessage())];
	}
}

/**
 * Edição administrativa. Campos ausentes não mudam; senha vazia mantém a atual.
 * Troca de dono: cancela transferência pendente, promove ou cria o vínculo do novo dono e,
 * opcionalmente, remove o vínculo do dono anterior (por padrão ele continua como ouvinte).
 */
function editarRastreador($pdo, $rastreador_id, $dados) {
	$Audit = (new Auditoria())->add('rastreador|usuario_rastreador', 'editarRastreador', ["id" => $rastreador_id], is_array($dados) ? $dados : null, false);
	if (!validarIdPositivo($rastreador_id) || !is_array($dados)) {
		return ["error" => errorMessage("Dados inválidos", $rastreador_id)];
	}

	try {
		$pdo->beginTransaction();
		$falha = function ($mensagem) use ($pdo) {
			$pdo->rollBack();
			return ["error" => $mensagem];
		};

		$stmt = $pdo->prepare("select id, hardware, token, token_publico, obs, status, dono_id from rastreador where id = :id for update");
		$stmt->execute(["id" => $rastreador_id]);
		$atual = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$atual) return $falha(errorMessage("Rastreador não encontrado", $rastreador_id));
		$Audit->antes(["rastreador" => $atual]);

		$sets = [];
		$params = ["id" => $rastreador_id];
		foreach (['hardware' => 'Hardware', 'token' => 'Token', 'token_publico' => 'Token público'] as $campo => $rotulo) {
			if (!array_key_exists($campo, $dados)) continue;
			$valor = normalizarFiltroTexto($dados[$campo]);
			if ($valor === null || mb_strlen($valor) > 100) return $falha(errorMessage("$rotulo inválido", $campo));
			$sets[$campo] = "$campo = :$campo";
			$params[$campo] = $valor;
		}
		foreach (['token', 'token_publico'] as $campo) {
			if (!isset($params[$campo])) continue;
			$stmt = $pdo->prepare("select 1 from rastreador where $campo = :v and id <> :id");
			$stmt->execute(["v" => $params[$campo], "id" => $rastreador_id]);
			if ($stmt->fetchColumn() !== false) return $falha("Token ou token público já cadastrado");
		}
		if (array_key_exists('obs', $dados)) {
			$obs = normalizarFiltroTexto($dados['obs']);
			if ($obs !== null && mb_strlen($obs) > 200) return $falha("Observação com mais de 200 caracteres");
			$sets['obs'] = "obs = :obs";
			$params['obs'] = $obs;
		}
		$senha = (string) ($dados['senha'] ?? '');
		if ($senha !== '') {
			if (!validarSenha($senha)) return $falha("A senha deve ter no mínimo 8 caracteres, uma letra maiúscula e minúscula, um número e caractere especial");
			$sets['senha'] = "senha = crypt(:senha, gen_salt('bf'))";
			$params['senha'] = $senha;
		}

		$status = (int) $atual['status'];
		$novo_status = isset($dados['status']) ? (int) $dados['status'] : $status;
		if ($novo_status !== $status && !in_array($novo_status, [1, 2, 4], true)) return $falha(errorMessage("Status inválido", $novo_status));

		$dono_anterior = $atual['dono_id'];
		$novo_dono = isset($dados['dono_id']) && $dados['dono_id'] !== '' ? (int) $dados['dono_id'] : null;
		$trocou_dono = $novo_dono !== null && $novo_dono !== $dono_anterior;

		// Sair do estado "em transferência" (por troca de dono ou de status) encerra a proposta pendente
		if ($status === 3 && ($trocou_dono || $novo_status !== 3)) {
			$stmt = $pdo->prepare("update usuario_rastreador set status = 2 where rastreador_id = :id and status = 5");
			$stmt->execute(["id" => $rastreador_id]);
			if ($novo_status === 3) $novo_status = 1;
		}
		if ($novo_status !== $status) {
			$sets['status'] = "status = :status";
			$params['status'] = $novo_status;
		}

		if ($trocou_dono) {
			$stmt = $pdo->prepare("select 1 from usuario where id = :id and ativo = true");
			$stmt->execute(["id" => $novo_dono]);
			if ($stmt->fetchColumn() === false) return $falha(errorMessage("Usuário dono não encontrado ou inativo", $novo_dono));
			$sets['dono_id'] = "dono_id = :dono_id";
			$params['dono_id'] = $novo_dono;

			$stmt = $pdo->prepare("select id from usuario_rastreador where usuario_id = :u and rastreador_id = :r");
			$stmt->execute(["u" => $novo_dono, "r" => $rastreador_id]);
			$ur_id = $stmt->fetchColumn();
			if ($ur_id === false) {
				// Novo dono não era ouvinte: cria o vínculo já operante
				$stmt = $pdo->prepare("insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (:u, :r, :nome, 1)");
				$stmt->execute(["u" => $novo_dono, "r" => $rastreador_id, "nome" => $params['hardware'] ?? $atual['hardware']]);
			} else {
				// Já era ouvinte (ou tinha proposta): vira operante, sem restrições
				$pdo->prepare("delete from vinc_loc_oculta_usuario_rastreador where usuario_rastreador_id = :id")->execute(["id" => $ur_id]);
				$pdo->prepare("update usuario_rastreador set status = 1, ativo = true, loc_temporeal = true, loc_salvos = true where id = :id")->execute(["id" => $ur_id]);
			}

			if (!empty($dados['remover_dono_anterior']) && $dono_anterior !== null) {
				$stmt = $pdo->prepare("select id from usuario_rastreador where usuario_id = :u and rastreador_id = :r");
				$stmt->execute(["u" => $dono_anterior, "r" => $rastreador_id]);
				if ($ur_antigo = $stmt->fetchColumn()) {
					$pdo->prepare("delete from vinc_loc_oculta_usuario_rastreador where usuario_rastreador_id = :id")->execute(["id" => $ur_antigo]);
					$pdo->prepare("delete from usuario_rastreador where id = :id")->execute(["id" => $ur_antigo]);
				}
			}
		}

		if ($sets) {
			$pdo->prepare("update rastreador set " . implode(', ', $sets) . " where id = :id")->execute($params);
		}

		$stmt = $pdo->prepare("select * from vw_rastreadores_do_sistema where id = :id");
		$stmt->execute(["id" => $rastreador_id]);
		$rastreador = $stmt->fetch(PDO::FETCH_ASSOC);
		$pdo->commit();

		$Audit->depois(["rastreador" => $rastreador]);
		$Audit->sucesso(true);
		return ["success" => true, "rastreador" => $rastreador];
	} catch (Exception $e) {
		if ($pdo->inTransaction()) $pdo->rollBack();
		return ["error" => errorMessage("Error in editarRastreador", $e->getMessage())];
	}
}

/** Formato exigido pelo servidor-ponte: token privado = 32 hex (AES-128); público = até 32 bytes. */
function validarFormatoTokens($token, $token_publico) {
	if ($token !== null && !preg_match('/^[0-9a-fA-F]{32}$/', (string) $token)) return "O token privado deve ter 32 caracteres hexadecimais";
	if ($token_publico !== null && !preg_match('/^[a-zA-Z0-9._%+-]{8,32}$/', (string) $token_publico)) return "O token público deve ter de 8 a 32 caracteres, sem espaços";
	return null;
}
