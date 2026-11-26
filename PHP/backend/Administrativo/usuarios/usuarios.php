<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_usuarios.php";
$credenciais = getCredentials();


try {
userHasPermission("Administrador");

if (($name_filter = getRequestValue("get", "is_string")) !== null) {    
    returnJson(getUsuarios($credenciais["pdo"], $name_filter));
}

if (($name_filter = getRequestValue("getSimple", "is_string")) !== null) {    
    returnJson(getSimpleUsuarios($credenciais["pdo"], $name_filter));
}

if ($id = getRequestValue("toggle_ativo", "is_int")) {
    if ($id === $credenciais["id"]) returnJson(["error" => "Você não pode desativar a própria conta"]);
    returnJson(toggleUsuarioAtivo($credenciais["pdo"], $id));
}

if ($id = getRequestValue("toggle_adm", "is_int")) {
    if ($id === $credenciais["id"]) returnJson(["error" => "Você não pode remover o próprio acesso de administrador"]);
    returnJson(toggleUsuarioAdm($credenciais["pdo"], $id));
}

if ($dados = getRequestValue("add_usuario", "is_array")) {

    returnJson(adicionarUsuario(
        $credenciais["pdo"],
        $dados["nome"] ?? null,
        $dados["email"] ?? null,
        $dados["identidade"] ?? null,
        $dados["tipo_ident"] ?? null,
        $dados["adm"] ?? false
    ));
}

} catch (Throwable $e) {
	returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error" => "invalid_request"]);
