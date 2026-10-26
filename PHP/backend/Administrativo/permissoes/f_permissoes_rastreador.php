<?php

function getPermissoesRastreador($credenciais, $agrupar_por, $filter) {
    $Audit = (new Auditoria())->add('vinc_perm_rastreador', 'getPermissoesRastreador', null, ["agrupar_por" => $agrupar_por, "filtro" => $filter], false);
    $pdo = $credenciais["pdo"];
    
    function findIndexEntity($agrupamento, $id, $is_tracker) {
        foreach ($agrupamento as $index => $entidade) {
            if ((int) $entidade["id"] == (int) $id && (($entidade["is_tracker"] ?? null) == $is_tracker)) {
                return $index;
            }
        }
        return -1;
    }

    $agrupamento = [];
    $where = "";

    if (!empty($filter)) {
        switch ($agrupar_por) {
            case "entidade":
                $where = "where (gr.nome ilike :filter or r.hardware ilike :filter)";
                break;
            case "permissao":
                $where = "where pr.nome ilike :filter";
                break;
            case "rastreador":
                $where = "where r.hardware ilike :filter or vpr.grupo_id is not null";
                break;
        }
    }

    $base_sql =
        "select vpr.id vpr_id, vpr.negado vpr_negado,
            r.id is not null as is_tracker,
            coalesce(gr.id, r.id) x_id,
            coalesce(gr.nome, r.hardware) x_nome,
            pr.id pr_id, pr.nome pr_nome
        from vinc_perm_rastreador vpr
        join permissao_rastreador pr on pr.id = vpr.perm_id
        left join rastreador r on r.id = vpr.rastreador_id
        left join grupo_rastreador gr on gr.id = vpr.grupo_id
        $where
        order by is_tracker desc";


    $stmt = $pdo->prepare($base_sql);
    $stmt->execute(
        !empty($filter) ? [":filter" => "%$filter%"] : []
    );
    $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($agrupar_por === "entidade") {
        foreach ($perms as $p) {
            $index = findIndexEntity($agrupamento, $p["x_id"], $p["is_tracker"]);

            if ($index === -1) {
                $agrupamento[] = [
                    "id" => $p["x_id"],
                    "nome" => $p["x_nome"],
                    "is_tracker" => (bool) $p["is_tracker"],
                    "permissoes" => [$p]
                ];
            } else {
                $agrupamento[$index]["permissoes"][] = $p;
            }
        }
    } elseif ($agrupar_por === "permissao") {
        foreach ($perms as $p) {
            $index = findIndexEntity($agrupamento, $p["pr_id"], null);

            if ($index === -1) {
                $agrupamento[] = [
                    "id" => $p["pr_id"],
                    "nome" => $p["pr_nome"],
                    "is_tracker" => null,
                    "permissoes" => [$p]
                ];
            } else {
                $agrupamento[$index]["permissoes"][] = $p;
            }
        }
    } elseif ($agrupar_por === "rastreador") {
        $sql = "select rastreador_id, grupo_id from vinc_grupo_rastreador";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $vinc_grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($perms as $p) {
            if ($p["is_tracker"]) {
                $index = findIndexEntity($agrupamento, $p["x_id"], $p["is_tracker"]);

                if ($index === -1) {
                    $agrupamento[] = [
                        "id" => $p["x_id"],
                        "nome" => $p["x_nome"],
                        "is_tracker" => true,
                        "permissoes" => [$p]
                    ];
                } else {
                    $agrupamento[$index]["permissoes"][] = $p;
                }
            } else {
                foreach ($vinc_grupos as $vinc) {
                    if ((int) $vinc["grupo_id"] == (int) $p["x_id"]) {
                        $index = findIndexEntity($agrupamento, $vinc["rastreador_id"], true);
                        if ($index !== -1) {
                            $agrupamento[$index]["permissoes"][] = $p;
                        }
                    }
                }
            }
        }

        foreach ($agrupamento as &$rastreador) {
            usort($rastreador["permissoes"], function ($a, $b) {
                if ((int) $a["pr_id"] == (int) $b["pr_id"]) {
                    return (int) $a["vpr_negado"] <=> (int) $b["vpr_negado"];
                }
                return (int) $a["pr_id"] <=> (int) $b["pr_id"];
            });
        }
        unset($rastreador);
    }

    $Audit->depois(["qnt_entidades" => count($agrupamento)]);
    $Audit->sucesso(true);
    return ["success" => true, "agrupamento" => $agrupamento];
}

function getGrupos($credenciais, $grupo_nome) {
    $Audit = (new Auditoria())->add('grupo_rastreador|vinc_grupo_rastreador|vinc_perm_rastreador', 'getGrupos', null, ["nome" => $grupo_nome], false);
    $pdo = $credenciais["pdo"];

    $sql = "select id from grupo_rastreador where nome ilike :grupo_nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":grupo_nome" => "%$grupo_nome%"]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ids)) {
        return ["success" => true, "grupos" => []];
    }
    
    $ids = '{' . implode(',', $ids) . '}';

    $sql = "select * from getGruposPermissaoRastreador(:grupo_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":grupo_id" => $ids]);
    $grupos_linha = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function findGrupoById($grupos, $id) {
        foreach ($grupos as $index => $grupo) {
            if ((int) $grupo["id"] == (int) $id) {
                return $index;
            }
        }
        return -1;
    }

    $grupos = [];
    foreach ($grupos_linha as $linha) {
        $grupo_id = $linha["gr_id"];
        $index = findGrupoById($grupos, $grupo_id);

        if ($index === -1) {
            $grupos[] = [
                "id" => $grupo_id,
                "nome" => $linha["gr_nome"],
                "rastreadores" => [],
            ];
            $index = count($grupos) - 1;
        }

        if ($linha["r_id"] === null) {
            continue;
        }

        $grupos[$index]["rastreadores"][] = [
            "id" => $linha["r_id"],
            "nome" => $linha["r_hardware"]
        ];
    }

    $Audit->depois(["qnt_grupos" => count($grupos)]);
    $Audit->sucesso(true);
    return ["success" => true, "grupos" => $grupos];
}

function addGrupo($credenciais, $novo_grupo) {
    $Audit = (new Auditoria())->add('grupo_rastreador', 'addGrupo', null, ["nome" => $novo_grupo], false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "insert into grupo_rastreador (nome) values (:nome) returning id";
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
    $Audit = (new Auditoria())->add('grupo_rastreador|vinc_grupo_rastreador|vinc_perm_rastreador', 'deleteGrupo', ["id" => $grupo_id], null, false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();

        $sql = "delete from vinc_grupo_rastreador where grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":grupo_id" => $grupo_id]);

        $sql = "delete from vinc_perm_rastreador where grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":grupo_id" => $grupo_id]);

        $sql = "delete from grupo_rastreador where id = :grupo_id";
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

function addRastreadorNoGrupo($credenciais, $rastreador_id, $grupo_id) {
    $Audit = (new Auditoria())->add('vinc_grupo_rastreador', 'addRastreadorNoGrupo', null, ["rastreador_id" => $rastreador_id, "grupo_id" => $grupo_id], false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "insert into vinc_grupo_rastreador (rastreador_id, grupo_id) values (:rastreador_id, :grupo_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":rastreador_id" => $rastreador_id, ":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao adicionar rastreador ao grupo", "Foi adicionado algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao adicionar rastreador ao grupo", $e->getMessage())];
    }
}

function removerRastreadorDoGrupo($credenciais, $rastreador_id, $grupo_id) {
    $Audit = (new Auditoria())->add('vinc_grupo_rastreador', 'removerRastreadorDoGrupo', ["rastreador_id" => $rastreador_id, "grupo_id" => $grupo_id], null, false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "delete from vinc_grupo_rastreador where rastreador_id = :rastreador_id and grupo_id = :grupo_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":rastreador_id" => $rastreador_id, ":grupo_id" => $grupo_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao remover rastreador do grupo", "Foi removido algo diferente de 1 registro.")];
        }

        $pdo->commit();
        $Audit->sucesso(true);
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao remover rastreador do grupo", $e->getMessage())];
    }
}


function searchGrupos($credenciais, $group_name) {
    $Audit = (new Auditoria())->add('grupo_rastreador', 'searchGrupos', null, ["nome" => $group_name], false);
    $pdo = $credenciais["pdo"];

    $sql = "select id, nome from grupo_rastreador where nome ilike :group_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":group_name" => "%$group_name%"]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $Audit->depois(["qnt_grupos" => count($grupos)]);
    $Audit->sucesso(true);
    return ["success" => true, "grupos" => $grupos];
}

function searchPermissoes($credenciais, $permissoes_name) {
    $Audit = (new Auditoria())->add('permissao_rastreador', 'searchPermissoes', null, ["nome" => $permissoes_name], false);
    $pdo = $credenciais["pdo"];

    $sql = "select id, nome from permissao_rastreador where nome ilike :permissoes_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":permissoes_name" => "%$permissoes_name%"]);
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $Audit->depois(["qnt_permissoes" => count($permissoes)]);
    $Audit->sucesso(true);
    return ["success" => true, "permissoes" => $permissoes];
}

function addVinculoPermissao($credenciais, $rastreadorOuGrupoID, $is_tracker, $negado, $permissaoID) {
    $Audit = (new Auditoria())->add('vinc_perm_rastreador', 'addVinculoPermissao', null, ["entidade_id" => $rastreadorOuGrupoID, "is_tracker" => $is_tracker, "permissao_id" => $permissaoID], false);
    $pdo = $credenciais["pdo"];
    $negado = $negado ? 1 : 0;

    try {
        $pdo->beginTransaction();
        $sql = "insert into vinc_perm_rastreador (rastreador_id, grupo_id, perm_id, negado) values (:rastreador_id, :grupo_id, :perm_id, :negado)";
        $stmt = $pdo->prepare($sql);

        if ($is_tracker) {
            $stmt->execute([
                ":rastreador_id" => $rastreadorOuGrupoID,
                ":grupo_id" => null,
                ":perm_id" => $permissaoID,
                ":negado" => $negado,
            ]);
        } else {
            $stmt->execute([
                ":rastreador_id" => null,
                ":grupo_id" => $rastreadorOuGrupoID,
                ":perm_id" => $permissaoID,
                ":negado" => $negado,
            ]);
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

function removeVinculoPermissao($credenciais, $vinculo_id, $rastreadorOuGrupoID, $is_tracker, $permissaoID) {
    $Audit = (new Auditoria())->add('vinc_perm_rastreador', 'removeVinculoPermissao', ["id" => $vinculo_id], null, false);
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $sql = "DELETE FROM vinc_perm_rastreador
            WHERE id = :vinculo_id
            AND " . ($is_tracker ? "rastreador_id" : "grupo_id") . " = :entidade_id
            AND " . ($is_tracker ? "grupo_id" : "rastreador_id") . " IS NULL
            AND perm_id = :perm_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":vinculo_id" => $vinculo_id,
            ":entidade_id" => $rastreadorOuGrupoID,
            ":perm_id" => $permissaoID,
        ]);

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
