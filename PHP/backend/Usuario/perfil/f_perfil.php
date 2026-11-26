<?php

function getPerfilUsuario($credenciais) {
    $Audit = (new Auditoria())->add('usuario|usuario_rastreador', 'getPerfilUsuario', null, null, false);
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT u.id, u.nome, login, email, telefone, legal_ident_id, li.identidade, li.tipo_id li_tipo_id, li.regex FROM usuario u LEFT JOIN legal_ident li ON u.legal_ident_id = li.id WHERE u.id = :usuario_id");
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

    $Audit->depois(["perfil_usuario_id" => $credenciais["id"]]);
    $Audit->sucesso(true);
    return ["success" => true, "perfil" => $perfil];
}

function updatePerfilUsuario($credenciais, $valores) {
    $Audit = (new Auditoria())->add('usuario', 'updatePerfilUsuario', ["usuario_id" => $credenciais["id"]], $valores, false);
    $pdo = $credenciais["pdo"];

    $senha_atual = $valores['senha_atual'] ?? null;
    unset($valores['senha_atual']);

    // Lista fechada de colunas: nada vindo do cliente é concatenado no SQL
    $regras = [
        'nome' => ['validarNomeUsuario', "Nome inválido", ":nome"],
        'email' => ['validarEmail', "Email inválido", ":email"],
        'telefone' => ['validarTelefone', "Telefone inválido", ":telefone"],
        'senha' => ['validarSenha', "Senha inválida", "crypt(:senha, gen_salt('bf'))"],
    ];

    $sets = [];
    foreach ($valores as $campo => $valor) {
        if (!isset($regras[$campo])) {
            return ["error" => errorMessage("Característica inválida", ":$campo")];
        }
        [$validar, $mensagem, $expressao] = $regras[$campo];
        if (!is_string($valor) || !$validar($valor)) {
            return ["error" => errorMessage($mensagem, $campo === 'senha' ? '***' : $valor)];
        }
        $sets[] = "$campo = $expressao";
    }
    if (!$sets) {
        return ["error" => errorMessage("Nenhum valor para atualizar", $credenciais["id"])];
    }

    if (isset($valores['senha'])) {
        $stmt = $pdo->prepare("select 1 from usuario where id = :id and senha = crypt(:senha, senha)");
        $stmt->execute(["id" => $credenciais["id"], "senha" => (string) $senha_atual]);
        if ($stmt->fetchColumn() === false) {
            return ["error" => "Informe corretamente a senha atual para trocar a senha"];
        }
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE usuario SET " . implode(', ', $sets) . " WHERE id = :usuario_id");
        $stmt->execute($valores + ['usuario_id' => $credenciais["id"]]);
        if ($stmt->rowCount() != 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao atualizar perfil", $credenciais["id"])];
        }
        if (isset($valores['senha'])) {
            $token = isset($_POST['access_token']) ? json_decode($_POST['access_token'], true) : null;
            revogarTokensDoUsuario($pdo, $credenciais["id"], is_string($token) ? $token : null);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() === '23505') {
            return ["error" => "Este email já está em uso"];
        }
        return ["error" => errorMessage("Erro ao atualizar perfil", $e->getMessage())];
    }
    $Audit->sucesso(true);
    return getPerfilUsuario($credenciais);
}


function getSimpleLegalIdentTipo($credenciais) {
    $Audit = (new Auditoria())->add('legal_ident_tipo', 'getSimpleLegalIdentTipo', null, null, false);
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->query("SELECT id, descricao as nome, regex FROM legal_ident_tipo WHERE invalido = false");
    $idents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $Audit->depois(["qnt_idents" => count($idents)]);
    $Audit->sucesso(true);
    return ["success" => true, "legal_ident_tipo" => $idents];
}

function getLegalIdentUsuario($credenciais) {
    $Audit = (new Auditoria())->add('legal_ident', 'getLegalIdentUsuario', null, null, false);
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT id, tipo_id, identidade, regex FROM legal_ident WHERE usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $credenciais["id"]]);
    $identidade = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $Audit->depois(["identidade" => $identidade]);
    $Audit->sucesso(true);
    return ["success" => true, "legal_ident" => $identidade];
}

function validarLegalIdent($ident, $regex) {
    if (!$regex || !preg_match('~' . str_replace('~', '\~', $regex) . '~u', (string) $ident)) return false;
    return true;
}

function updateLegalIdentUsuario($credenciais, $identidade, $tipo) {
    $Audit = (new Auditoria())->add('legal_ident', 'updateLegalIdentUsuario', ["usuario" => ["id" => $credenciais["id"]]], $identidade, false);
    
    try {
        $pdo = $credenciais["pdo"];
        
        $stmt = $pdo->prepare("SELECT u.id u_id, li.tipo_id li_tipo_id, li.identidade li_identidade FROM usuario u join legal_ident li ON u.legal_ident_id = li.id WHERE u.id = :usuario_id");
        $stmt->execute(['usuario_id' => $credenciais["id"]]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        $Audit->antes(["usuario" => ["identidade" => $usuario["li_identidade"] ?? null]]);

        $stmt = $pdo->prepare("SELECT regex FROM legal_ident_tipo WHERE id = :tipo_id");
        $stmt->execute(['tipo_id' => $tipo]);
        $tipo_regex = $stmt->fetchColumn();

        if (!$tipo_regex) {
            return ["error" => errorMessage("Tipo de identidade inválido", $tipo)];
        }

        if (!validarLegalIdent($identidade, $tipo_regex)) {
            return ["error" => errorMessage("Identidade inválida", $identidade)];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO
        legal_ident (tipo_id, regex, identidade)
        VALUES (:tipo_id, :regex, :identidade)
        ON CONFLICT (tipo_id, identidade)
        DO UPDATE SET last_used = NOW()
        RETURNING id, tipo_id, identidade, regex");

        $stmt->execute([
            'tipo_id' => $tipo,
            'regex' => $tipo_regex,
            'identidade' => $identidade
        ]);

        $legal_ident = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE usuario SET legal_ident_id = :legal_ident_id WHERE id = :usuario_id");
        $stmt->execute([
            'legal_ident_id' => $legal_ident['id'],
            'usuario_id' => $credenciais["id"]
        ]);
        
        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true, "legal_ident" => $legal_ident];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao atualizar identidade legal", $e->getMessage())];
    }
}