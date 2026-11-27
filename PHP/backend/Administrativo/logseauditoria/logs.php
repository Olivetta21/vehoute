<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_logs.php";
$credenciais = getCredentials();

try {

userHasPermission("Administrador");

if ($params = getRequestValue("get", "is_array")) {
    returnJson(getLogsDoSistema($credenciais, $params['titulo'] ?? null, $params['mensagem'] ?? null, $params['ip'] ?? null, $params['data_inicio'] ?? null, $params['data_fim'] ?? null, $params['limite'] ?? 200, $params['offset'] ?? 0, $params['id'] ?? null));
}

if (($filter = getRequestValue("getSimpleTitulos", "is_string")) !== null) {
    returnJson(getSimpleTitulos($credenciais, $filter));
}

} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}

returnJson(["error"=>"invalid_request"]);