<?php
require __DIR__ . "/../../include_me.php";
require_once __DIR__ . "/f_configuracao.php";
$credenciais = getCredentials();

try {

if (getRequestValue("getConfigs")) {
    returnJson(getConfiguracaoUsuario($credenciais));
}

if ($update = getRequestValue("update", "is_array")) {
    returnJson(updateConfiguracaoUsuario($credenciais, $update['config_id'], $update['variavel']));
}

} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}

returnJson(["error"=>"invalid_request"]);