<?php

function gerarLoginUsuario($pdo) {
    for ($tentativa = 0; $tentativa < 8; $tentativa++) {
        $login = 'usr' . substr(getRandomHex(8), 0, 8);
        $stmt = $pdo->prepare("select 1 from usuario where login = :login");
        $stmt->execute(["login" => $login]);

        if ($stmt->fetchColumn() === false) {
            return $login;
        }
    }

    return null;
}

function gerarSenhaUsuario() {
    return 'Aa1#' . substr(getRandomHex(8), 0, 8);
}

function getUsuarios($pdo, $nome = null) {
    $nome = normalizarFiltroTexto($nome);

    $sql = "SELECT * from vw_usuarios_do_sistema";
    $params = [];

    if ($nome !== null) {
        $sql .= " where nome ilike :nome";
        $params["nome"] = '%' . $nome . '%';
    }

    $sql .= " order by nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return ["success" => true, "usuarios" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

function toggleUsuarioAtivo($pdo, $usuario_id) {
    if (!validarIdPositivo($usuario_id)) {
        return ["error" => errorMessage("Id de usuário inválido", $usuario_id)];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("update usuario set ativo = not ativo where id = :id returning id, ativo");
        $stmt->execute(["id" => $usuario_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Usuário não encontrado", $usuario_id)];
        }

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();

        return ["success" => true, "usuario" => $usuario];
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }

        return ["error" => errorMessage("Error in toggleUsuarioAtivo", $e->getMessage())];
    }
}

function toggleUsuarioAdm($pdo, $usuario_id) {
    if (!validarIdPositivo($usuario_id)) {
        return ["error" => errorMessage("Id de usuário inválido", $usuario_id)];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("select id, nome from usuario where id = :id for update");
        $stmt->execute(["id" => $usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $pdo->rollBack();
            return ["error" => errorMessage("Usuário não encontrado", $usuario_id)];
        }

        $stmt = $pdo->prepare("select 1 from administrador where id = :id");
        $stmt->execute(["id" => $usuario_id]);
        $ehAdm = $stmt->fetchColumn() !== false;

        if ($ehAdm) {
            $stmt = $pdo->prepare("delete from administrador where id = :id");
            $stmt->execute(["id" => $usuario_id]);
            $novoAdm = false;
        } else {
            $stmt = $pdo->prepare("insert into administrador (id) values (:id)");
            $stmt->execute(["id" => $usuario_id]);
            $novoAdm = true;
        }

        $pdo->commit();

        return ["success" => true, "usuario" => ["id" => $usuario_id, "nome" => $usuario["nome"], "adm" => $novoAdm]];
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }

        return ["error" => errorMessage("Error in toggleUsuarioAdm", $e->getMessage())];
    }
}

function adicionarUsuario($pdo, $nome, $email, $identidade, $tipo_ident, $adm = false) {
    if (!validarNomeUsuario($nome)) {
        return ["error" => errorMessage("Nome de usuário inválido", $nome)];
    }

    if (!validarEmail($email)) {
        return ["error" => errorMessage("Email inválido", $email)];
    }

    $identidade = normalizarFiltroTexto($identidade);
    if ($identidade === null) {
        return ["error" => errorMessage("Identidade inválida", $identidade)];
    }

    if (!validarIdPositivo($tipo_ident)) {
        return ["error" => errorMessage("Tipo de identificação inválido", $tipo_ident)];
    }

    $adm = filter_var($adm, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    if ($adm === null) {
        $adm = false;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("select 1 from legal_ident_tipo where id = :tipo_ident and invalido = false");
        $stmt->execute(["tipo_ident" => $tipo_ident]);

        if ($stmt->fetchColumn() === false) {
            $pdo->rollBack();
            return ["error" => errorMessage("Tipo de identificação não encontrado", $tipo_ident)];
        }

        $stmt = $pdo->prepare("select 1 from usuario where email = :email");
        $stmt->execute(["email" => $email]);

        if ($stmt->fetchColumn() !== false) {
            $pdo->rollBack();
            return ["error" => errorMessage("Este email já está cadastrado", $email)];
        }

        $login = gerarLoginUsuario($pdo);
        if ($login === null) {
            $pdo->rollBack();
            return ["error" => errorMessage("Falha ao gerar login", $email)];
        }

        $senha = gerarSenhaUsuario();

        $stmt = $pdo->prepare("insert into legal_ident (tipo_id, identidade) values (:tipo_ident, :identidade) returning id");
        $stmt->execute([
            "tipo_ident" => $tipo_ident,
            "identidade" => $identidade
        ]);
        $legal_ident_id = $stmt->fetchColumn();

        if (!$legal_ident_id) {
            $pdo->rollBack();
            return ["error" => errorMessage("Falha ao cadastrar a identificação", $identidade)];
        }

        $stmt = $pdo->prepare("insert into usuario (nome, login, senha, legal_ident_id, email) values (:nome, :login, :senha, :legal_ident_id, :email) returning id, nome, login, ativo");
        $stmt->execute([
            "nome" => $nome,
            "login" => $login,
            "senha" => $senha,
            "legal_ident_id" => $legal_ident_id,
            "email" => $email
        ]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $pdo->rollBack();
            return ["error" => errorMessage("Falha ao cadastrar usuário", $nome)];
        }

        if ($adm) {
            $stmt = $pdo->prepare("insert into administrador (id) values (:id)");
            $stmt->execute(["id" => $usuario["id"]]);
        }

        $pdo->commit();

        $usuario["email"] = $email;
        $usuario["adm"] = $adm;
        $usuario["legal_ident_id"] = $legal_ident_id;
        $usuario["identidade"] = $identidade;

        return [
            "success" => true,
            "usuario" => $usuario,
            "credenciais" => [
                "login" => $login,
                "senha" => $senha
            ]
        ];
    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }

        return ["error" => errorMessage("Error in adicionarUsuario", $e->getMessage())];
    }
}