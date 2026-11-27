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
    if (isset($update["email"])) limitarRequisicoes('trocar_email', 10, 900, $credenciais["id"]);
    returnJson(updatePerfilUsuario($credenciais, $update));
}

if (getRequestValue("novo_codigo")) {
    userHasPermission("Modificar Perfil");
    limitarRequisicoes('novo_codigo', 10, 900, $credenciais["id"]);
    returnJson(gerarNovoCodigoUsuario($credenciais));
}

if ($codigo = getRequestValue("confirmar_email", "is_string")) {
    userHasPermission("Modificar Perfil");
    limitarRequisicoes('confirmar_email', 15, 900, $credenciais["id"]);
    returnJson(confirmarAlteracaoEmail($credenciais, $codigo));
}

if (getRequestValue("cancelar_email")) {
    userHasPermission("Modificar Perfil");
    returnJson(cancelarAlteracaoEmail($credenciais));
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