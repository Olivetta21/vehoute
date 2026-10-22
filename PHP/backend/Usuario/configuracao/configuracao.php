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

} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);