<?php
require __DIR__ . "/../../include_me.php";
require_once __DIR__ . "/f_perfil.php";
$credenciais = getCredentials();

try {

if (getRequestValue("get")) {
    returnJson(getPerfilUsuario($credenciais));
}

if ($update = getRequestValue("update", "is_array")) {
    userHasPermission("Modificar Perfil");
    returnJson(updatePerfilUsuario($credenciais, $update));
}

if (getRequestValue("getSimpleLegalIdentTipo")) {
    returnJson(getSimpleLegalIdentTipo($credenciais));
}

if ($ident = getRequestValue("updateLegalIdent", "is_array")) {
    userHasPermission("Modificar Perfil");
    returnJson(updateLegalIdentUsuario($credenciais, $ident["identidade"], $ident["tipo_id"]));
}

} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}

returnJson(["error"=>"invalid_request"]);