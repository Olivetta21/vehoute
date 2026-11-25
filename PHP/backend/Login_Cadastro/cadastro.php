<?php
require __DIR__ . "/../include_me.php";
require __DIR__ . "/f_cadastro.php";
require_once __DIR__ . '/../../backend/login_cadastro/f_login.php';


try {

if ($mail_data = getRequestValue("mail_data", "is_array")) {    
    returnJson(gerarOTPeEnviarEmail($mail_data["nome"], $mail_data["email"]));
}

if ($otp_check = getRequestValue("otp_check", "is_array")) {
    returnJson(verificarOTP($otp_check['otp']));   
}

if ($cadastro_data = getRequestValue("cadastro_data", "is_array")) {
    $nome = $cadastro_data['nome'];
    $email = $cadastro_data['email'];
    $telefone = $cadastro_data['telefone'];
    $login = $cadastro_data['login'];
    $senha = $cadastro_data['senha'];
    $otp = $cadastro_data['otp'];
    $identidade = $cadastro_data['identidade'];
    $tipo_ident_id = $cadastro_data['tipo_ident_id'];


    returnJson(
        finalizarCadastro(
            $nome,
            $email,
            $telefone,
            $login,
            $senha,
            $otp,
            $identidade,
            $tipo_ident_id
        )
    );
}

if ($getLegalIdentTipos = getRequestValue("getLegalIdentTipos")) {
    returnJson(getSimpleLegalIdentTipoCadastro());
}

if ($email = getRequestValue("recuperar_senha", "is_string")) {
    returnJson(enviarCodigoRecuperacao($email));
}

if ($otp = getRequestValue("recuperar_senha_check", "is_string")) {
    returnJson(testarCodigoRecuperacao($otp));
}

if ($nova = getRequestValue("recuperar_senha_save", "is_array")) {
    $email = $nova['email'];
    $otp = $nova['otp'];
    $senha = $nova['senha'];
    returnJson(salvarNovaSenhaRecuperada($email, $otp, $senha));
}

} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}