<?php

function getPermissoesUsuario($credenciais, $agrupar_por, $filter) {
    $pdo = $credenciais["pdo"];

    function findIndexEntity($agrupamento, $id, $is_user) {
        foreach ($agrupamento as $index => $entidade) {
            if ($entidade["id"] == $id && $entidade["is_user"] == $is_user) {
                return $index;
            }
        }
        return -1;
    }

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


    if ($agrupar_por === "entidade") {
        foreach ($perms as $p) {
            $index = findIndexEntity($agrupamento, $p["x_id"], $p["is_user"]);

            if ($index === -1) {
                $agrupamento[] = [
                    "id" => $p["x_id"],
                    "nome" => $p["x_nome"],
                    "is_user" => $p["is_user"],
                    "permissoes" => [$p]
                ];
            } else {
                $agrupamento[$index]["permissoes"][] = $p;
            }
        }
    }

    else if ($agrupar_por === "permissao") {
        foreach ($perms as $p) {
            $index = findIndexEntity($agrupamento, $p["pu_id"], null);

            if ($index === -1) {
                $agrupamento[] = [
                    "id" => $p["pu_id"],
                    "nome" => $p["pu_nome"],
                    "is_user" => null,
                    "permissoes" => [$p]
                ];
            } else {
                $agrupamento[$index]["permissoes"][] = $p;
            }
        }
    }

    else if ($agrupar_por === "usuario") {
        $sql = "select usuario_id, grupo_id from vinc_grupo_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $vinc_grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($perms as $p) {
            if ($p["is_user"]) {
                $index = findIndexEntity($agrupamento, $p["x_id"], $p["is_user"]);

                if ($index === -1) {
                    $agrupamento[] = [
                        "id" => $p["x_id"],
                        "nome" => $p["x_nome"],
                        "is_user" => $p["is_user"],
                        "permissoes" => [$p]
                    ];
                } else {
                    $agrupamento[$index]["permissoes"][] = $p;
                }
            } else { //grupo
                foreach ($vinc_grupos as $vinc) {
                    if ($vinc["grupo_id"] == $p["x_id"]) {
                        $index = findIndexEntity($agrupamento, $vinc["usuario_id"], true);
                        if ($index === -1) {
                            continue;
                        } else {
                            $agrupamento[$index]["permissoes"][] = $p;
                        }
                    }
                }
            }
        }

        foreach ($agrupamento as &$user) {
            usort($user["permissoes"], function($a, $b) {
                if ($a["pu_id"] == $b["pu_id"]) {
                    return $a["vpu_negado"] <=> $b["vpu_negado"];
                }
                return $a["pu_id"] <=> $b["pu_id"];
            });
        }
    }


    return ["success" => true, "agrupamento" => $agrupamento];    
}


function getGrupos($credenciais, $grupo_nome) {
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
    
    function findGrupoById($grupos, $id) {
        foreach ($grupos as $index => $grupo) {
            if ($grupo["id"] == $id) {
                return $index;
            }
        }
        return -1;
    }
    $grupos = [];
    foreach ($grupos_linha as $linha) {
        $grupo_id = $linha["gu_id"];
        $index = findGrupoById($grupos, $grupo_id);
        if ($index === -1) {
            $grupos[] = [
                "id" => $grupo_id,
                "nome" => $linha["gu_nome"],
                "usuarios" => [],
            ];
            $index = count($grupos) - 1;
        } 
        if ($linha["u_id"] === null) {
            continue;
        }
        $grupos[$index]["usuarios"][] = [
            "id" => $linha["u_id"],
            "nome" => $linha["u_nome"]
        ];
    }

    return ["success" => true, "grupos" => $grupos];
}

function addGrupo($credenciais, $novo_grupo) {
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "insert into grupo_usuario (nome) values (:nome) returning id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":nome" => $novo_grupo]);
        $grupo_id = $stmt->fetchColumn();

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return [errorMessage("Erro ao adicionar grupo", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        return ["success" => true, "grupo" => ["id" => $grupo_id, "nome" => $novo_grupo]];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [errorMessage("Erro ao adicionar grupo", $e->getMessage())];
    }
}

function deleteGrupo($credenciais, $grupo_id) {
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
            return [errorMessage("Erro ao deletar grupo", "Foi deletado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [errorMessage("Erro ao deletar grupo", $e->getMessage())];
    }
}

function addUsuarioNoGrupo($credenciais, $usuario_id, $grupo_id) {
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "insert into vinc_grupo_usuario (usuario_id, grupo_id) values (:usuario_id, :grupo_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":usuario_id" => $usuario_id, ":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return [errorMessage("Erro ao adicionar usuário ao grupo", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [errorMessage("Erro ao adicionar usuário ao grupo", $e->getMessage())];
    }
}

function removerUsuarioDoGrupo($credenciais, $usuario_id, $grupo_id) {
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "delete from vinc_grupo_usuario where usuario_id = :usuario_id and grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":usuario_id" => $usuario_id, ":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return [errorMessage("Erro ao remover usuário do grupo", "Foi removido algo diferente de 1 registro.")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [errorMessage("Erro ao remover usuário do grupo", $e->getMessage())];
    }
}

function searchGrupos($credenciais, $group_name) {
    $pdo = $credenciais["pdo"];

    $sql = "select id, nome from grupo_usuario where nome ilike :group_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":group_name" => "%$group_name%"]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ["success" => true, "grupos" => $grupos];
}

function searchPermissoes($credenciais, $permissoes_name) {
    $pdo = $credenciais["pdo"];

    $sql = "select id, nome from permissao_usuario where nome ilike :permissoes_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":permissoes_name" => "%$permissoes_name%"]);
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ["success" => true, "permissoes" => $permissoes];
}

function addVinculoPermissao($credenciais, $usuarioOuGrupoID, $is_user, $negado, $permissaoID) {
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
            return [errorMessage("Erro ao adicionar permissão", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [errorMessage("Erro ao adicionar permissão", $e->getMessage())];
    }
}

function removeVinculoPermissao($credenciais, $vinculo_id, $usuarioOuGrupoID, $is_user, $permissaoID) {
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
            return [errorMessage("Erro ao remover permissão", "Foi removido algo diferente de 1 registro.")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [errorMessage("Erro ao remover permissão", $e->getMessage())];
    }
}