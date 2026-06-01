<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_usuarios.php";
$credenciais = getCredentials();

function getRequestValue($key) {
    return isset($_POST[$key]) ? json_decode($_POST[$key], true) : null;
}


if (isset($_POST["get"])) {    
    returnJson(getUsuarios($credenciais["pdo"], getRequestValue("get")));
}

if (isset($_POST["toggle_ativo"])) {
    returnJson(toggleUsuarioAtivo($credenciais["pdo"], getRequestValue("toggle_ativo")));
}

if (isset($_POST["toggle_adm"])) {
    returnJson(toggleUsuarioAdm($credenciais["pdo"], getRequestValue("toggle_adm")));
}

if (isset($_POST["add_usuario"])) {
    $dados = getRequestValue("add_usuario");
    if (!is_array($dados)) {
        returnJson(["error" => errorMessage("Dados de cadastro inválidos", json_encode($dados))]);
    }

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