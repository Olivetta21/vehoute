<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_usuarios.php";
$credenciais = getCredentials();

if ($name_filter = getRequestValue("get", "is_string")) {    
    returnJson(getUsuarios($credenciais["pdo"], $name_filter));
}

if ($id = getRequestValue("toggle_ativo", "is_int")) {
    returnJson(toggleUsuarioAtivo($credenciais["pdo"], $id));
}

if ($id = getRequestValue("toggle_adm", "is_int")) {
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

returnJson(["error"=>"invalid_request"]);