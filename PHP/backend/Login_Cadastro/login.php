<?php
require __DIR__ . "/../include_me.php";
require __DIR__ . "/f_login.php";


try {

if (getRequestValue("logoff")) {
    $token = getRequestValue("access_token", "is_string") ?? ($_COOKIE["access_token"] ?? null);
    resetAccessToken();
    returnJson(fazerLogoff($token));
}

if ($login_data = getRequestValue("login", "is_array")) {
    $login = (string) ($login_data["login"] ?? '');
    $senha = (string) ($login_data["senha"] ?? '');
    limitarRequisicoes('login', 10, 300, $login);

    $result = fazerLogin($login, $senha);
    if (isset($result['usuario']) && isset($result['usuario']['access_token'])) {
        $access_token = $result['usuario']['access_token'];
        if (!empty(trim($access_token))) {
            definirCookieAccessToken($access_token, time() + (60 * 60));
        }
    }

    returnJson($result);
}

if ($access_token = getRequestValue("access_token", "is_string")) {
    returnJson(testAccessToken($access_token));
}

returnJson(["error"=>"invalid_request"]);

} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}