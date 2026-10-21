<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_rastreadores.php";
$credenciais = getCredentials();

try {
if ($name_filter = getRequestValue("get", "is_string")) {
	returnJson(getRastreadores($credenciais["pdo"], $name_filter));
}

if ($name_filter = getRequestValue("getSimple", "is_string")) {
	returnJson(getSimpleRastreadores($credenciais["pdo"], $name_filter));
}

if ($id = getRequestValue("toggle_ativo", "is_int")) {
	returnJson(toggleRastreadorAtivo($credenciais["pdo"], $id));
}

if ($dados = getRequestValue("add_rastreador", "is_array")) {
	returnJson(adicionarRastreador(
		$credenciais["pdo"],
		$dados["hardware"] ?? null,
		$dados["token"] ?? null,
		$dados["token_publico"] ?? null,
		$dados["senha"] ?? null,
		$dados["obs"] ?? null,
		$dados["status"] ?? null,
		$dados["dono_id"] ?? null
	));
}

} catch (Exception $e) {
	returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error" => "invalid_request"]);
