<?php

function getPerfilUsuario($credenciais) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT id, nome, login, email, telefone FROM usuario WHERE id = :usuario_id");
    $stmt->execute(['usuario_id' => $credenciais["id"]]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT
        COUNT(*) FILTER (WHERE dono_id = :usuario_id) AS total_rastreadores,
        COUNT(*) FILTER (WHERE dono_id = :usuario_id AND ur_id IS NOT NULL) AS total_rastreadores_registrados,
        COUNT(*) FILTER (
            WHERE ur_id IS NOT NULL
            AND (dono_id != :usuario_id OR dono_id IS NULL)
        ) AS total_ouvinte
        FROM getrastreadoresdousuario(:usuario_id_array);
    ");
    $stmt->execute(['usuario_id' => $credenciais["id"], 'usuario_id_array' => '{'.$credenciais["id"].'}']);
    $contagem = $stmt->fetch(PDO::FETCH_ASSOC);

    $perfil = array_merge($usuario, $contagem);

    return ["success" => true, "perfil" => $perfil];
}

function updatePerfilUsuario($credenciais, $valores) {
    $pdo = $credenciais["pdo"];

    $sql = "UPDATE usuario SET ";
    foreach ($valores as $caracteristica => $valor) {
        $sql .= "$caracteristica = ";
        $caracteristica = ":$caracteristica";
        switch ($caracteristica) {
            case ':nome':
                if (!validarNomeUsuario($valor)) return ["error" => errorMessage("Nome inválido", $valor)];
                break;
            case ':email':
                if (!validarEmail($valor)) return ["error" => errorMessage("Email inválido", $valor)];
                break;
            case ':telefone':
                if (!validarTelefone($valor)) return ["error" => errorMessage("Telefone inválido", $valor)];
                break;
            case ':senha':
                if (!validarSenha($valor)) return ["error" => errorMessage("Senha inválida", $valor)];
                $caracteristica = "crypt(:senha, gen_salt('bf'))";
                break;
            default:
                return ["error" => errorMessage("Característica inválida", $caracteristica)];
        }
        $sql .= "$caracteristica, ";
    }
    $sql = rtrim($sql, ", ") . " WHERE id = :usuario_id";

    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    $valores['usuario_id'] = $credenciais["id"];
    $stmt->execute($valores);
    if ($stmt->rowCount() != 1) {
        $pdo->rollBack();
        return ["error" => errorMessage("Erro ao atualizar perfil", $valores)];
    }
    $pdo->commit();
    return getPerfilUsuario($credenciais);
}