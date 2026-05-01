<?php

function selectUsuarioPorSenha($pdo, $login, $senha) {
    $stmt = $pdo->prepare("select id, nome, login from usuario where login = :login and senha = :senha");
    $stmt->execute([
        "login"=>$login,
        "senha"=>$senha
    ]);
    if ($stmt->rowCount() === 1) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    else {
        return false;
    }
}

function insertAccess_Token($pdo, $usuario_id) {
    $stmt = $pdo->prepare("insert into usuario_access_token(token, usuario_id, expires_at) values (:token, :usuario_id, now() + interval '1 hour')");
    $new_access_token = getRandomHex();
    $stmt->execute([
        "token"=>$new_access_token,
        "usuario_id"=>$usuario_id
    ]);
    if ($stmt->rowCount() > 0) {
        return $new_access_token;
    } else {
        return false;
    }
}

function selectPermissoesDoUsuario($pdo, $usuario_id) {
    $stmt = $pdo->prepare("select perm_id from getPermissoesDoUsuario(:usuario_id) where negado = false");
    $stmt->execute([
        "usuario_id"=> '{' . $usuario_id . '}'
    ]);
    if ($stmt->rowCount() > 0) {
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        return false;
    }
}

function selectUsuarioPorToken($pdo, $access_token) {
    $stmt = $pdo->prepare("select id, nome, login from usuario where id = (select id from getUsuarioByToken(:access_token))");
    $stmt->execute([
        "access_token"=> $access_token
    ]);
    if ($stmt->rowCount() === 1) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

function fazerLogin($login, $senha) {
    $pdo = null;
    try {
        $pdo = getDataBase();
        $pdo->beginTransaction();
        
        $usuario = selectUsuarioPorSenha($pdo, $login, $senha);
        if (!$usuario) return ["error"=> errorMessage("Usuario não encontrado", "login=". $login ."")];
        
        $access_token = insertAccess_Token($pdo, $usuario['id']);
        if (!$access_token) return ["error"=> errorMessage("access_token não adicionado", "usuario=". $usuario ."")];

        $permissoes = selectPermissoesDoUsuario($pdo, $usuario["id"]);
        if (!$permissoes) return ["error"=> errorMessage("Erro ao recuperar permissões", "usuario=". $usuario ."")];
        
        $usuario['access_token'] = $access_token;
        $usuario['permissoes'] = $permissoes;

        $pdo->commit();
        return ["success"=>true, "usuario"=>$usuario];
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error"=> errorMessage("Exceção ao fazer login", $e->getMessage())];
    }
}

function testAccessToken($access_token) {
    $pdo = null;
    try {
        $pdo = getDataBase();

        $usuario = selectUsuarioPorToken($pdo, $access_token);
        if (!$usuario) return ["error"=> errorMessage("Access_token inválido", "access_token=" . $access_token ."")];
        
        $permissoes = selectPermissoesDoUsuario($pdo, $usuario["id"]);
        if (!$permissoes) return ["error"=> errorMessage("Erro ao recuperar permissões", "usuario=". $usuario ."")];
        
        $usuario['access_token'] = $access_token;
        $usuario['permissoes'] = $permissoes;

        return ["success"=>true, "usuario"=>$usuario];
    } catch (Exception $e) {
        return ["error"=> errorMessage("Exceção ao testar access_token", $e->getMessage())];
    }
}