<?php
require __DIR__ . "/../include_me.php";
require __DIR__ . "/f_login.php";


if (isset($_POST["logoff"])) {
    resetAccessToken();
    returnJson(["logoff" => true]);
}
else if (isset($_POST["login"])) {
    $infos = json_decode($_POST["login"], true);
    $login = $infos["login"];
    $senha = $infos["senha"];

    $result = fazerLogin($login, $senha);
    if (isset($result['usuario']) && isset($result['usuario']['access_token'])) {
        $access_token = $result['usuario']['access_token'];
        if (!empty(trim($access_token))) {
            setcookie("access_token", $access_token, time() + (60 * 60), "/");
        }
    }

    returnJson($result);
}
else if (isset($_POST["access_token"])) {
    $access_token = json_decode($_POST["access_token"], true);
    
    returnJson(testAccessToken($access_token));
}
else {
    returnJson(["error" => errorMessage("Dados inválidos", [$_POST, $_COOKIE, $_REQUEST])]);
}