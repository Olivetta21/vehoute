<?php

function getRastreadores($pdo, $filtro = null) {
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

	return ["success" => true, "rastreadores" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

function toggleRastreadorAtivo($pdo, $rastreador_id) {
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

		return ["success" => true, "rastreador" => $rastreador];
	} catch (Exception $e) {
		if ($pdo?->inTransaction()) {
			$pdo->rollBack();
		}

		return ["error" => errorMessage("Error in toggleRastreadorAtivo", $e->getMessage())];
	}
}

function adicionarRastreador($pdo, $hardware, $token, $token_publico, $senha, $obs, $status, $dono_id) {
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

		$stmt = $pdo->prepare(
			"insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id)
			 values (:hardware, :token, :token_publico, :senha, :obs, :status, :dono_id)
			 returning id, hardware, token, token_publico, senha, obs, status, ativo, dono_id"
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

		return ["success" => true, "rastreador" => $rastreador];
	} catch (Exception $e) {
		if ($pdo?->inTransaction()) {
			$pdo->rollBack();
		}

		return ["error" => errorMessage("Error in adicionarRastreador", $e->getMessage())];
	}
}
