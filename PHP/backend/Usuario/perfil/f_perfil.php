<?php

function getPerfilUsuario($credenciais) {
    $Audit = (new Auditoria())->add('usuario|usuario_rastreador', 'getPerfilUsuario', null, null, false);
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT u.id, u.nome, u.codigo, login, email, telefone, legal_ident_id, li.identidade, li.tipo_id li_tipo_id, li.regex FROM usuario u LEFT JOIN legal_ident li ON u.legal_ident_id = li.id WHERE u.id = :usuario_id");
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

    $stmt = $pdo->prepare("SELECT novo_email FROM usuario_alteracao_email WHERE usuario_id = :id AND expires_at > now()");
    $stmt->execute(['id' => $credenciais["id"]]);
    $perfil['email_pendente'] = $stmt->fetchColumn() ?: null;

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

    // O email nunca é gravado direto: fica pendente até o código enviado ao novo endereço ser confirmado
    $novo_email = null;
    if (isset($valores['email'])) {
        $stmt = $pdo->prepare("select email from usuario where id = :id");
        $stmt->execute(["id" => $credenciais["id"]]);
        if (strcasecmp((string) $stmt->fetchColumn(), $valores['email']) !== 0) {
            $novo_email = $valores['email'];
            $stmt = $pdo->prepare("select 1 from usuario where lower(email) = lower(:email) and id <> :id");
            $stmt->execute(["email" => $novo_email, "id" => $credenciais["id"]]);
            if ($stmt->fetchColumn() !== false) {
                return ["error" => "Este email já está em uso"];
            }
        }
        unset($valores['email']);
        $sets = array_values(array_filter($sets, fn($s) => !str_starts_with($s, 'email ')));
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
        if ($sets) {
            $stmt = $pdo->prepare("UPDATE usuario SET " . implode(', ', $sets) . " WHERE id = :usuario_id");
            $stmt->execute($valores + ['usuario_id' => $credenciais["id"]]);
            if ($stmt->rowCount() != 1) {
                $pdo->rollBack();
                return ["error" => errorMessage("Erro ao atualizar perfil", $credenciais["id"])];
            }
        }
        if ($novo_email !== null) {
            $erro = solicitarAlteracaoEmail($pdo, $credenciais["id"], $novo_email);
            if ($erro !== null) {
                $pdo->rollBack();
                return ["error" => $erro];
            }
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


/** Grava o pedido e envia o código ao novo email. Retorna a mensagem de erro ou null. */
function solicitarAlteracaoEmail($pdo, $usuario_id, $novo_email) {
    $codigo = strtoupper(getRandomHex(8));
    $stmt = $pdo->prepare("insert into usuario_alteracao_email (usuario_id, novo_email, otp, expires_at)
        values (:id, :email, :otp, now() + interval '30 minutes')
        on conflict (usuario_id) do update set novo_email = excluded.novo_email, otp = excluded.otp,
            tentativas = 0, criado_em = now(), expires_at = excluded.expires_at");
    $stmt->execute(["id" => $usuario_id, "email" => $novo_email, "otp" => hash('sha256', $codigo)]);

    $link = htmlspecialchars(SITE_URL . "/site/perfil?codigo_email=" . $codigo);
    $envio = enviarEmail([
        "destinatario" => $novo_email,
        "assunto" => "Vehoute - Confirme seu novo email",
        "provedor" => "resend",
        "mensagem" => "<p>Código para confirmar a troca de email: <b>$codigo</b></p>
            <p>Ou acesse, já conectado à sua conta: <a href=\"$link\">$link</a></p>
            <p>O código vale por 30 minutos. Se você não pediu a troca, ignore esta mensagem.</p>"
    ]);
    if ($envio["status"] !== "sucesso") {
        return errorMessage("Erro ao enviar email de confirmação", $envio["mensagem"]);
    }

    // Aviso ao email atual (melhor esforço: uma falha aqui não impede o pedido)
    $atual = getUserMail($usuario_id);
    if ($atual) {
        enviarEmail([
            "destinatario" => $atual,
            "assunto" => "Vehoute - Pedido de troca de email",
            "provedor" => "resend",
            "mensagem" => "<p>Foi pedida a troca do email da sua conta. Se não foi você, troque sua senha.</p>"
        ]);
    }
    return null;
}

function confirmarAlteracaoEmail($credenciais, $codigo) {
    $Audit = (new Auditoria())->add('usuario', 'confirmarAlteracaoEmail', ["usuario_id" => $credenciais["id"]], null, false);
    $pdo = $credenciais["pdo"];
    $codigo = strtoupper(trim((string) $codigo));
    if (!validarOTP($codigo)) {
        return ["error" => "Código de confirmação inválido"];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select novo_email, otp, tentativas from usuario_alteracao_email
            where usuario_id = :id and expires_at > now() for update");
        $stmt->execute(["id" => $credenciais["id"]]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido || $pedido["tentativas"] >= 5) {
            $pdo->rollBack();
            return ["error" => "Não há troca de email válida. Peça um novo código."];
        }
        if (!hash_equals($pedido["otp"], hash('sha256', $codigo))) {
            $pdo->prepare("update usuario_alteracao_email set tentativas = tentativas + 1 where usuario_id = :id")
                ->execute(["id" => $credenciais["id"]]);
            $pdo->commit();
            return ["error" => "Código de confirmação incorreto"];
        }

        // Códigos de recuperação do endereço antigo deixam de valer
        $pdo->prepare("delete from usuario_recuperacao_senha where email = (select email from usuario where id = :id)")
            ->execute(["id" => $credenciais["id"]]);
        $pdo->prepare("update usuario set email = :email where id = :id")
            ->execute(["email" => $pedido["novo_email"], "id" => $credenciais["id"]]);
        $pdo->prepare("delete from usuario_alteracao_email where usuario_id = :id")
            ->execute(["id" => $credenciais["id"]]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() === '23505') {
            return ["error" => "Este email já está em uso"];
        }
        return ["error" => errorMessage("Erro ao confirmar o email", $e->getMessage())];
    }

    $Audit->depois(["email" => $pedido["novo_email"]]);
    $Audit->sucesso(true);
    return getPerfilUsuario($credenciais);
}

function cancelarAlteracaoEmail($credenciais) {
    $credenciais["pdo"]->prepare("delete from usuario_alteracao_email where usuario_id = :id")
        ->execute(["id" => $credenciais["id"]]);
    return getPerfilUsuario($credenciais);
}

/** Troca o código de compartilhamento; o anterior deixa de funcionar na hora. */
function gerarNovoCodigoUsuario($credenciais) {
    $Audit = (new Auditoria())->add('usuario', 'gerarNovoCodigoUsuario', ["usuario_id" => $credenciais["id"]], null, false);
    $stmt = $credenciais["pdo"]->prepare("update usuario set codigo = gerarCodigoUsuario() where id = :id");
    $stmt->execute(["id" => $credenciais["id"]]);
    if ($stmt->rowCount() !== 1) {
        return ["error" => errorMessage("Erro ao gerar um novo código", $credenciais["id"])];
    }
    $Audit->sucesso(true);
    return getPerfilUsuario($credenciais);
}

function getSimpleLegalIdentTipo($credenciais) {
    $Audit = (new Auditoria())->add('legal_ident_tipo', 'getSimpleLegalIdentTipo', null, null, false);
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->query("SELECT id, descricao as nome, regex, mascara FROM legal_ident_tipo WHERE invalido = false");
    $idents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $Audit->depois(["qnt_idents" => count($idents)]);
    $Audit->sucesso(true);
    return ["success" => true, "legal_ident_tipo" => $idents];
}

function getLegalIdentUsuario($credenciais) {
    $Audit = (new Auditoria())->add('legal_ident', 'getLegalIdentUsuario', null, null, false);
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT li.id, li.tipo_id, li.identidade, li.regex FROM legal_ident li JOIN usuario u ON u.legal_ident_id = li.id WHERE u.id = :usuario_id");
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