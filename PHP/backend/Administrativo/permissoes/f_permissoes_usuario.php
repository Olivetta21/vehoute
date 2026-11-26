<?php

function getPermissoesUsuario($credenciais, $agrupar_por, $filter) {
    $Audit = (new Auditoria())->add('vinc_perm_usuario', 'getPermissoesUsuario', null, ["agrupar_por" => $agrupar_por, "filtro" => $filter], false);
    $pdo = $credenciais["pdo"];

    $agrupamento = [];
    /*
    [0] => {
        id: 1,
        nome: Fulano de Tal,
        is_user: true,
        permissoes: [
            {
                vpu_id: 1,
                vpu_negado: false,
                is_user: true,
                x_id: 1,
                x_nome: Fulano de Tal,
                pu_id: 1,
                pu_nome: Permissao 1
            },
            {
                vpu_id: 2,
                vpu_negado: true,
                is_user: false,
                x_id: 1,
                x_nome: Grupo Alpha,
                pu_id: 1,
                pu_nome: Permissao 1
            }
        ]
    }
    */
    $where = "";
    if (!empty($filter)) {
        switch ($agrupar_por) {
            case "entidade":
                $where = "where (gu.nome ilike :filter or u.nome ilike :filter)";
                break;
            case "permissao":
                $where = "where pu.nome ilike :filter";
                break;
            case "usuario":
                $where = "where u.nome ilike :filter or vpu.grupo_id is not null";
                break;
        }
    }

    $base_sql =
    "select vpu.id vpu_id, vpu.negado vpu_negado,
	u.id is not null is_user,
	coalesce(gu.id,  u.id) x_id,
	coalesce(gu.nome, u.nome) x_nome,
	pu.id pu_id, pu.nome pu_nome
	from vinc_perm_usuario vpu 
	join permissao_usuario pu on pu.id = vpu.perm_id
	left join usuario u on u.id = vpu.usuario_id
	left join grupo_usuario gu on gu.id = vpu.grupo_id
    $where
    order by is_user desc";


    $stmt = $pdo->prepare($base_sql);
    $stmt->execute(
        !empty($filter) ? [":filter" => "%$filter%"] : []
    );
    $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Agrupamento em O(n) usando mapas indexados por chave
    $inserir = function (string $chave, $id, $nome, $flag, array $perm) use (&$agrupamento) {
        $agrupamento[$chave] ??= ["id" => $id, "nome" => $nome, "is_user" => $flag, "permissoes" => []];
        $agrupamento[$chave]["permissoes"][] = $perm;
    };

    if ($agrupar_por === "entidade") {
        foreach ($perms as $p) {
            $inserir(((int) $p["is_user"]) . ":" . $p["x_id"], $p["x_id"], $p["x_nome"], (bool) $p["is_user"], $p);
        }
    } else if ($agrupar_por === "permissao") {
        foreach ($perms as $p) {
            $inserir((string) $p["pu_id"], $p["pu_id"], $p["pu_nome"], null, $p);
        }
    } else if ($agrupar_por === "usuario") {
        $sql = "select vg.usuario_id id, e.nome nome, vg.grupo_id
            from vinc_grupo_usuario vg join usuario e on e.id = vg.usuario_id"
            . (!empty($filter) ? " where e.nome ilike :filter" : "");
        $stmt = $pdo->prepare($sql);
        $stmt->execute(!empty($filter) ? [":filter" => "%$filter%"] : []);
        $membros = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $membros[$m["grupo_id"]][] = $m;
        }

        foreach ($perms as $p) {
            if ($p["is_user"]) {
                $inserir((string) $p["x_id"], $p["x_id"], $p["x_nome"], true, $p);
            } else {
                foreach ($membros[$p["x_id"]] ?? [] as $m) {
                    $inserir((string) $m["id"], $m["id"], $m["nome"], true, $p);
                }
            }
        }

        foreach ($agrupamento as &$item) {
            usort($item["permissoes"], fn($a, $b) =>
                [(int) $a["pu_id"], (int) $a["vpu_negado"]] <=> [(int) $b["pu_id"], (int) $b["vpu_negado"]]);
        }
        unset($item);
    }
    $agrupamento = array_values($agrupamento);

    $Audit->depois(["qnt_entidades" => count($agrupamento)]);
    $Audit->sucesso(true);
    return ["success" => true, "agrupamento" => $agrupamento];    
}


function getGrupos($credenciais, $grupo_nome) {
    $Audit = (new Auditoria())->add('grupo_usuario|vinc_grupo_usuario|vinc_perm_usuario', 'getGrupos', null, ["nome" => $grupo_nome], false);
    $pdo = $credenciais["pdo"];

    $sql = "select id from grupo_usuario where nome ilike :grupo_nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":grupo_nome" => "%$grupo_nome%"]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ids)) {
        return ["success" => true, "grupos" => []];
    }

    $ids = '{' . implode(',', $ids) . '}';

    $sql = "select * from getGruposPermissaoUsuario(:grupo_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":grupo_id" => $ids]);
    $grupos_linha = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $grupos = [];
    foreach ($grupos_linha as $linha) {
        $grupo_id = $linha["gu_id"];
        $grupos[$grupo_id] ??= ["id" => $grupo_id, "nome" => $linha["gu_nome"], "usuarios" => []];
        if ($linha["u_id"] === null) {
            continue;
        }
        $grupos[$grupo_id]["usuarios"][] = [
            "id" => $linha["u_id"],
            "nome" => $linha["u_nome"]
        ];
    }
    $grupos = array_values($grupos);

    $Audit->depois(["qnt_grupos" => count($grupos)]);
    $Audit->sucesso(true);
    return ["success" => true, "grupos" => $grupos];
}

function addGrupo($credenciais, $novo_grupo) {
    $Audit = (new Auditoria())->add('grupo_usuario', 'addGrupo', null, ["nome" => $novo_grupo], false);
    $pdo = $credenciais["pdo"];

    $novo_grupo = trim((string) $novo_grupo);
    if ($novo_grupo === '' || mb_strlen($novo_grupo) > 100) {
        return ["error" => errorMessage("Nome de grupo inválido", $novo_grupo)];
    }

    try {
        $pdo->beginTransaction();
        $sql = "insert into grupo_usuario (nome) values (:nome) returning id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":nome" => $novo_grupo]);
        $grupo_id = $stmt->fetchColumn();

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao adicionar grupo", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->depois(["id" => $grupo_id, "nome" => $novo_grupo]);
        $Audit->sucesso(true);
        return ["success" => true, "grupo" => ["id" => $grupo_id, "nome" => $novo_grupo]];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao adicionar grupo", $e->getMessage())];
    }
}

function deleteGrupo($credenciais, $grupo_id) {
    $Audit = (new Auditoria())->add('grupo_usuario|vinc_grupo_usuario|vinc_perm_usuario', 'deleteGrupo', ["id" => $grupo_id], null, false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "delete from vinc_grupo_usuario where grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":grupo_id" => $grupo_id]);

        $sql = "delete from vinc_perm_usuario where grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":grupo_id" => $grupo_id]);

        $sql = "delete from grupo_usuario where id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao deletar grupo", "Foi deletado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao deletar grupo", $e->getMessage())];
    }
}

function addUsuarioNoGrupo($credenciais, $usuario_id, $grupo_id) {
    $Audit = (new Auditoria())->add('vinc_grupo_usuario', 'addUsuarioNoGrupo', null, ["usuario_id" => $usuario_id, "grupo_id" => $grupo_id], false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "insert into vinc_grupo_usuario (usuario_id, grupo_id) values (:usuario_id, :grupo_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":usuario_id" => $usuario_id, ":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao adicionar usuário ao grupo", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao adicionar usuário ao grupo", $e->getMessage())];
    }
}

function removerUsuarioDoGrupo($credenciais, $usuario_id, $grupo_id) {
    $Audit = (new Auditoria())->add('vinc_grupo_usuario', 'removerUsuarioDoGrupo', ["usuario_id" => $usuario_id, "grupo_id" => $grupo_id], null, false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "delete from vinc_grupo_usuario where usuario_id = :usuario_id and grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":usuario_id" => $usuario_id, ":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao remover usuário do grupo", "Foi removido algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao remover usuário do grupo", $e->getMessage())];
    }
}

function searchGrupos($credenciais, $group_name) {
    $Audit = (new Auditoria())->add('grupo_usuario', 'searchGrupos', null, ["nome" => $group_name], false);
    $pdo = $credenciais["pdo"];

    $sql = "select id, nome from grupo_usuario where nome ilike :group_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":group_name" => "%$group_name%"]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $Audit->depois(["qnt_grupos" => count($grupos)]);
    $Audit->sucesso(true);
    return ["success" => true, "grupos" => $grupos];
}

function searchPermissoes($credenciais, $permissoes_name) {
    $Audit = (new Auditoria())->add('permissao_usuario', 'searchPermissoes', null, ["nome" => $permissoes_name], false);
    $pdo = $credenciais["pdo"];

    $sql = "select id, nome from permissao_usuario where nome ilike :permissoes_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":permissoes_name" => "%$permissoes_name%"]);
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $Audit->depois(["qnt_permissoes" => count($permissoes)]);
    $Audit->sucesso(true);
    return ["success" => true, "permissoes" => $permissoes];
}

function addVinculoPermissao($credenciais, $usuarioOuGrupoID, $is_user, $negado, $permissaoID) {
    $Audit = (new Auditoria())->add('vinc_perm_usuario', 'addVinculoPermissao', null, ["entidade_id" => $usuarioOuGrupoID, "is_user" => $is_user, "permissao_id" => $permissaoID], false);
    $pdo = $credenciais["pdo"];

    $negado = $negado ? 1 : 0;

    try {
        $pdo->beginTransaction();
        $sql = "insert into vinc_perm_usuario (usuario_id, grupo_id, perm_id, negado) values (:usuario_id, :grupo_id, :perm_id, :negado)";
        $stmt = $pdo->prepare($sql);
        if ($is_user) {
            $stmt->execute([":usuario_id" => $usuarioOuGrupoID, ":grupo_id" => null, ":perm_id" => $permissaoID, ":negado" => $negado]);
        } else {
            $stmt->execute([":usuario_id" => null, ":grupo_id" => $usuarioOuGrupoID, ":perm_id" => $permissaoID, ":negado" => $negado]);
        }

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao adicionar permissão", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao adicionar permissão", $e->getMessage())];
    }
}

function removeVinculoPermissao($credenciais, $vinculo_id, $usuarioOuGrupoID, $is_user, $permissaoID) {
    $Audit = (new Auditoria())->add('vinc_perm_usuario', 'removeVinculoPermissao', ["id" => $vinculo_id], null, false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "DELETE
        FROM vinc_perm_usuario 
        WHERE id = :vinculo_id
        AND " . ($is_user ? "usuario_id" : "grupo_id") . " = :entidade_id " .
        "AND " . ($is_user ? "grupo_id" : "usuario_id") . " IS NULL " .
        "AND perm_id = :perm_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":vinculo_id" => $vinculo_id, ":entidade_id" => $usuarioOuGrupoID, ":perm_id" => $permissaoID]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao remover permissão", "Foi removido algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao remover permissão", $e->getMessage())];
    }
}