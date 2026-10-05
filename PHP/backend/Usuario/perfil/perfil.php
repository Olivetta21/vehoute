<?php
require __DIR__ . "/../../include_me.php";
require_once __DIR__ . "/f_perfil.php";
$credenciais = getCredentials();

try {

if (getRequestValue("get")) {
    returnJson(getPerfilUsuario($credenciais));
}

if ($update = getRequestValue("update", "is_array")) {
    returnJson(updatePerfilUsuario($credenciais, $update));
}


} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);