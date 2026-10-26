<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_logs.php";
$credenciais = getCredentials();

try {

if ($params = getRequestValue("get", "is_array")) {
    returnJson(getLogsDoSistema($credenciais, $params['titulo'] ?? null, $params['mensagem'] ?? null, $params['ip'] ?? null, $params['data_inicio'] ?? null, $params['data_fim'] ?? null));
}

if ($filter = getRequestValue("getSimpleTitulos", "is_string")) {
    returnJson(getSimpleTitulos($credenciais, $filter));
}

} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);