<?php

function enviarEmailOTP($nome, $email, $otp) {
    $message = 'Olá ' . $nome . ", esse é o codigo para fazer o seu cadastro: " . $otp;
    
    return true;
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
    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select * from inserirUsuarioCadastrando(:nome, :email, :otp)");
        $stmt->execute([
            "nome"=>$nome_usuario,
            "email"=>$email,
            "otp"=>$otp
        ]);
        $cadastrando_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cadastrando_result["sucesso"] === true) {
            $send_mail_result = enviarEmailOTP($nome_usuario, $email, $otp);
            if ($send_mail_result) {
                $pdo->commit();

                $result = ["success" => true];
                if (DEVELOPMENT_ENV) $result["otp"] = $otp;
                return $result;
            } else {
                $pdo->rollBack();
                return ["error" => errorMessage("Não foi possivel enviar o email","nome_usuario: $nome_usuario, email: $email, otp: $otp")];
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

function finalizarCadastro($nome, $email, $telefone, $login, $senha, $otp) {
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
        $stmt = $pdo->prepare("select * from finalizarCadastroUsuario(:nome, :email, :telefone, :login, :senha, :otp)");
        $stmt->execute([
            "nome"=>$nome,
            "email"=>$email,
            "telefone"=>$telefone,
            "login"=>$login,
            "senha"=> $senha,
            "otp"=>$otp
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