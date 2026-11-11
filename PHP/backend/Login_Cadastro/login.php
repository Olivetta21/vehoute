<?php
require __DIR__ . "/../include_me.php";
require __DIR__ . "/f_login.php";


try {

if (getRequestValue("logoff")) {
    resetAccessToken();
    returnJson(["logoff" => true]);
}

if ($login_data = getRequestValue("login", "is_array")) {
    $login = $login_data["login"];
    $senha = $login_data["senha"];

    $result = fazerLogin($login, $senha);
    if (isset($result['usuario']) && isset($result['usuario']['access_token'])) {
        $access_token = $result['usuario']['access_token'];
        if (!empty(trim($access_token))) {
            setcookie("access_token", $access_token, time() + (60 * 60), "/");
        }
    }

    returnJson($result);
}

if ($access_token = getRequestValue("access_token", "is_string")) {
    returnJson(testAccessToken($access_token));
}

} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}