<?php
require __DIR__ . "/../include_me.php";
require __DIR__ . "/f_cadastro.php";
require_once __DIR__ . '/../../backend/Login_Cadastro/f_login.php';

if (isset($_POST["mail_data"])) {
    $mail_data = json_decode($_POST["mail_data"], true);
    
    returnJson(gerarOTPeEnviarEmail($mail_data["nome"], $mail_data["email"]));
}
else if (isset($_POST["otp_check"])) {
    $otp_check = json_decode($_POST["otp_check"], true);

    returnJson(verificarOTP($otp_check['otp']));   
}
else if (isset($_POST["cadastro_data"])) {
    $cadastro_data = json_decode($_POST["cadastro_data"], true);
    $nome = $cadastro_data['nome'];
    $email = $cadastro_data['email'];
    $telefone = $cadastro_data['telefone'];
    $login = $cadastro_data['login'];
    $senha = $cadastro_data['senha'];
    $otp = $cadastro_data['otp'];


    returnJson(
        finalizarCadastro(
            $nome,
            $email,
            $telefone,
            $login,
            $senha,
            $otp
        )
    );
} else {
    returnJson(["error" => "invalid_request"]);
}