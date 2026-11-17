<?php

function enviarEmailOTP($nome, $email, $otp) {
    $data = [
        "destinatario" => $email,
        "assunto" => "Vehoute - Crie sua conta",
        "provedor" => "resend",
        "template" => [
            "id" => "criar-conta",
            "variables" => [
                "first_name" => $nome,
                "website" => "Vehoute",
                "website_url" => SITE_URL,
                "reset_url" => SITE_URL . "/cadastro/finalizando?otp=$otp",
                "otp" => $otp
            ]
        ]
    ];

    return enviarEmail($data);
}

function gerarOTPeEnviarEmail($nome_usuario, $email) {
    if (!validarNomeUsuario($nome_usuario)) {
        return ["error" => errorMessage("Nome de usuário inválido", $nome_usuario)];
    }
    if (!validarEmail($email)) {
        return ["error" => errorMessage("Email inválido", $email)];
    }

    $otp = getRandomHex(8);

    $pdo = null;

    $ip = getClientIp();

    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select * from inserirUsuarioCadastrando(:nome, :email, :otp, :ip)");
        $stmt->execute([
            "nome"=>$nome_usuario,
            "email"=>$email,
            "otp"=>$otp,
            "ip"=>$ip
        ]);
        $cadastrando_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cadastrando_result["sucesso"] === true) {
            $send_mail_result = enviarEmailOTP($nome_usuario, $email, $otp);
            
            if ($send_mail_result["status"] === "sucesso") {
                $pdo->commit();

                $result = ["success" => true];
                if (DEVELOPMENT_ENV) $result["otp"] = $otp;
                return $result;
            } else {
                $pdo->rollBack();
                return ["error" => errorMessage("Erro ao enviar email de criação de conta", $send_mail_result["mensagem"])];
            }
        } 

        $pdo->rollBack();
        return ["error" => errorMessage($cadastrando_result["mensagem"],$cadastrando_result["detalhes"])];
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Error in gerarOTPeEnviarEmail",$e->getMessage())];
    }
}


function verificarOTP($otp) {
    if (!validarOTP($otp)) {
        return ["error" => errorMessage("OTP não atende os critérios", $otp)];
    }

    $pdo = null;
    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("update usuario_cadastrando set verificado = true where otp = :otp and expires_at > now() returning *");
        $stmt->execute([
            "otp"=>$otp
        ]);
        if ($stmt->rowCount() === 1) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $pdo->commit();
            return [
                "success" => true,
                "dados" => [
                    "nome" => $result["nome"],
                    "email" => $result["email"],
                    "otp" => $otp
                ]
            ];
        } else {
            $pdo->rollBack();
            return ["error" => errorMessage("OTP inválido", $otp)];
        }
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Error in verificarOTP", $e->getMessage())];
    }
}

function finalizarCadastro($nome, $email, $telefone, $login, $senha, $otp, $identidade, $tipo_ident_id) {
    if (!validarNomeUsuario($nome)) {
        return ["error" => errorMessage("Nome de usuário inválido", $nome)];
    }
    if (!validarEmail($email)) {
        return ["error" => errorMessage("Email inválido", $email)];
    }
    if (!validarSenha($senha)) {
        return ["error" => errorMessage("Senha não atende aos critérios", $senha)];
    }
    if (!validarTelefone($telefone)) {
        return ["error" => errorMessage("Telefone inválido", $telefone)];
    }
    if (!validarLoginUsuario($login)) {
        return ["error" => errorMessage("Login não atende aos critérios", $login)];
    }
    if (!validarOTP($otp)) {
        return ["error" => errorMessage("OTP não atende os critérios", $otp)];
    }


    $pdo = null;
    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select * from finalizarCadastroUsuario(:nome, :email, :telefone, :login, :senha, :otp, :identidade, :tipo_ident_id)");
        $stmt->execute([
            "nome"=>$nome,
            "email"=>$email,
            "telefone"=>$telefone,
            "login"=>$login,
            "senha"=> $senha,
            "otp"=>$otp,
            "identidade"=>$identidade,
            "tipo_ident_id"=>$tipo_ident_id
        ]);
        
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res["sucesso"] === true) {
            $pdo->commit();
            return ["success" => true];
        }
        
        $pdo->rollBack();
        return ["error" => errorMessage($res["mensagem"], $res["detalhes"])];
    } catch (PDOException $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Error in finalizarCadastro", $e->getMessage())];
    }
}


function getSimpleLegalIdentTipoCadastro() {
    $pdo = $pdo = getDataBase();
    $stmt = $pdo->query("SELECT id, descricao as nome, regex FROM legal_ident_tipo WHERE invalido = false");
    $idents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ["success" => true, "legal_ident_tipo" => $idents];
}


function enviarEmailRecuperacao($email, $otp) {
    $data = [
        "destinatario" => $email,
        "assunto" => "Vehoute - Código de Recuperação de Senha",
        "provedor" => "resend",
        "template" => [
            "id" => "redefinir-senha",
            "variables" => [
                "first_name" => "usuário",
                "website" => "Vehoute",
                "website_url" => SITE_URL,
                "reset_url" => SITE_URL . "/cadastro/recuperacao?otp=$otp",
                "otp" => $otp
            ]
        ]
    ];

    return enviarEmail($data);
}

function enviarCodigoRecuperacao($email) {
    if (!validarEmail($email)) {
        return ["error" => errorMessage("Email inválido", $email)];
    }

    $otp = getRandomHex(8);

    $pdo = null;

    $ip = getClientIp();

    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select * from inserirUsuarioRecuperacaoSenha(:email, :otp, :ip)");
        $stmt->execute([
            "email"=>$email,
            "otp"=>$otp,
            "ip"=>$ip
        ]);
        $recuperacao_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($recuperacao_result["sucesso"] === true) {
            $send_mail_result = enviarEmailRecuperacao($email, $otp);

            if ($send_mail_result["status"] === "sucesso") {
                $pdo->commit();

                $result = ["success" => true];
                if (DEVELOPMENT_ENV) $result["otp"] = $otp;
                return $result;
            } else {
                $pdo->rollBack();
                return ["error" => errorMessage("Erro ao enviar email de recuperação", $send_mail_result["mensagem"])];
            }
        } 

        $pdo->rollBack();
        return ["error" => errorMessage($recuperacao_result["mensagem"],$recuperacao_result["detalhes"])];
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Error in enviarCodigoRecuperacao",$e->getMessage())];
    }
}

function testarCodigoRecuperacao($otp) {
    if (!validarOTP($otp)) {
        return ["error" => errorMessage("Codigo não atende os critérios", $otp)];
    }

    $pdo = null;
    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("update usuario_recuperacao_senha set verificado = true where verificado = false and otp = :otp and expires_at > now() returning *");
        $stmt->execute([
            "otp"=>$otp
        ]);
        if ($stmt->rowCount() === 1) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $pdo->commit();
            return ["success" => true, "recuperacao_data" => ["email" => $result["email"], "otp" => $otp]];
        } else {
            $pdo->rollBack();
            return ["error" => errorMessage("Codigo inválido", $otp)];
        }
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Error in testarCodigoRecuperacao", $e->getMessage())];
    }
}

function salvarNovaSenhaRecuperada($email, $otp, $senha) {
    if (!validarEmail($email)) {
        return ["error" => errorMessage("Email inválido", $email)];
    }
    if (!validarSenha($senha)) {
        return ["error" => errorMessage("Senha não atende aos critérios", $senha)];
    }
    if (!validarOTP($otp)) {
        return ["error" => errorMessage("Codigo não atende os critérios", $otp)];
    }

    $pdo = null;
    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select * from salvarNovaSenhaRecuperada(:email, :otp, :senha)");
        $stmt->execute([
            "email"=>$email,
            "otp"=>$otp,
            "senha"=> $senha
        ]);
        
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res["sucesso"] === true) {
            $pdo->commit();
            return ["success" => true];
        }
        
        $pdo->rollBack();
        return ["error" => errorMessage($res["mensagem"], $res["detalhes"])];
    } catch (PDOException $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Error in salvarNovaSenha", $e->getMessage())];
    }
}