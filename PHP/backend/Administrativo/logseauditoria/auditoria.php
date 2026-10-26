<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_auditoria.php";
$credenciais = getCredentials();

try {

if ($params = getRequestValue("get")) {
    returnJson(getAuditoriaDoSistema($credenciais, $params['tabela'] ?? null, $params['filtro'] ?? null, $params['data_inicio'] ?? null, $params['data_fim'] ?? null, $params['sucesso'] ?? null));
}

if ($filter = getRequestValue("getSimpleTabelas", "is_string")) {
    returnJson(getSimpleTabelasAuditoria($filter));
}

} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);