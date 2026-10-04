<?php

function validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id) {
    $sql = "select id from rastreador where dono_id = :usuario_id and id = :rastreador_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "usuario_id" => $usuario_id,
        "rastreador_id" => $rastreador_id
    ]);

    return $stmt->rowCount() === 1;
}

function getLocOcultaDoRastreador($credenciais, $rastreador_id, $filter) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    $filter = normalizarFiltroTexto($filter);
    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para ver as localizações ocultas do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    try {
        $sql = "select * from getLocOcultaDoRastreador(:rastreador_id) where identificacao ilike :filter order by identificacao";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "rastreador_id" => '{' . $rastreador_id . '}',
            "filter" => '%' . $filter . '%'
        ]);
        $localizacoes_ocultas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$localizacoes_ocultas || count($localizacoes_ocultas) < 1) {
            return ["error" => errorMessage("Nenhuma localização oculta encontrada para o rastreador", $rastreador_id)];
        }

        $sql = "select * from getUsuariosDaLocOculta(:locsOcultasIds)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "locsOcultasIds" => '{' . implode(',', array_column($localizacoes_ocultas, 'id')) . '}'
        ]);
        $ouvintes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        foreach ($localizacoes_ocultas as &$loc) {
            $loc["ouvintes"] = [];
        }

        if ($ouvintes && count($ouvintes) > 0) {
            foreach ($localizacoes_ocultas as &$loc) {
                $loc["ouvintes"] = array_values(array_filter($ouvintes, function ($ouvinte) use ($loc) {
                    return $ouvinte["ilo_id"] == $loc["id"];
                }));
            }
        }

        return ["success" => true, "localizacoes_ocultas" => $localizacoes_ocultas];
    } catch (Exception $e) {
        return ["error" => errorMessage("Erro ao buscar ouvintes do rastreador", $e->getMessage())];
    }
}

function createLocOcultaDoRastreador($credenciais, $rastreador_id, $id_inicial, $id_final, $data_inicial, $data_final, $identificacao) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para criar localizações ocultas do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    try {
        $pdo->beginTransaction();

        $sql = "select * from createLocOcultaDoRastreador(:rastreador_id, :id_inicial, :id_final, :data_inicial, :data_final, :identificacao)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "rastreador_id" => $rastreador_id,
            "id_inicial" => $id_inicial,
            "id_final" => $id_final,
            "data_inicial" => $data_inicial,
            "data_final" => $data_final,
            "identificacao" => $identificacao
        ]);

        if ($rcount = $stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao criar localização oculta do rastreador", "Numero de linhas afetadas diferente de 1: " . $rcount)];
        }

        $pdo->commit();
        $loc_oculta = $stmt->fetch(PDO::FETCH_ASSOC);
        $loc_oculta["ouvintes"] = [];
        return ["success" => true, "localizacao_oculta" => $loc_oculta];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao criar localização oculta do rastreador", $e->getMessage())];
    }
}


function updateLocOcultaDoRastreador($credenciais, $rastreador_id, $lococulta_id, $array_caracteristicas_novosvalores) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    if (!validarIdPositivo($lococulta_id)) {
        return ["error" => errorMessage("Id de localização oculta inválido", $lococulta_id)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para atualizar localizações ocultas do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    if (!is_array($array_caracteristicas_novosvalores)) {
        return ["error" => errorMessage("Array de características inválido", $array_caracteristicas_novosvalores)];
    }

    $valid_caracteristicas = ["id_inicial", "id_final", "data_inicial", "data_final", "identificacao", "novos_ouvintes"];

    foreach ($array_caracteristicas_novosvalores as $caracteristica => $novoValor) {
        if (!in_array($caracteristica, $valid_caracteristicas)) {
            return ["error" => errorMessage("Característica inválida", $caracteristica)];
        }
        if (is_string($novoValor) && empty(trim($novoValor))) {
            $array_caracteristicas_novosvalores[$caracteristica] = null;
        }
    }

    try {
        $pdo->beginTransaction();

        $caracteristicas_str = "" . implode(", ", array_map(function ($caracteristica) {
            return '"' . $caracteristica . '" = :' . $caracteristica;
        }, array_keys($array_caracteristicas_novosvalores))) . " ";

        $sql = "update intervalo_loc_oculta set " . $caracteristicas_str . " where rastreador_id = :rastreador_id and id = :lococulta_id returning *";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":rastreador_id", $rastreador_id, PDO::PARAM_INT);
        $stmt->bindValue(":lococulta_id", $lococulta_id, PDO::PARAM_INT);

        $types = [
            "NULL" => PDO::PARAM_NULL,
            "integer" => PDO::PARAM_INT,
            "boolean" => PDO::PARAM_BOOL
        ];
        foreach ($array_caracteristicas_novosvalores as $caracteristica => $novoValor) {
            $stmt->bindValue(":$caracteristica", $novoValor, $types[gettype($novoValor)] ?? PDO::PARAM_STR);
        }

        $stmt->execute();

        if ($rcount = $stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao atualizar localização oculta do rastreador", "Numero de linhas afetadas diferente de 1: " . $rcount)];
        }

        $pdo->commit();
        return ["success" => true, "localizacao_oculta" => $stmt->fetch(PDO::FETCH_ASSOC)];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao atualizar localização oculta do rastreador", $e->getMessage())];
    }
}


function deleteLocOcultaDoRastreador($credenciais, $rastreador_id, $lococulta_ids) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    if (!is_array($lococulta_ids) || count($lococulta_ids) < 1) {
        return ["error" => errorMessage("Array de ids de localizações ocultas inválido", $lococulta_ids)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para deletar localizações ocultas do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("delete from vinc_loc_oculta_usuario_rastreador where intervalo_loc_oculta_id = any(:lococulta_ids::int[])");
        $stmt->execute(["lococulta_ids" => '{' . implode(',', array_map('intval', $lococulta_ids)) . '}']);

        $sql = "delete from intervalo_loc_oculta where rastreador_id = :rastreador_id and id = any(:lococulta_ids::int[]) returning *";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "rastreador_id" => $rastreador_id,
            "lococulta_ids" => '{' . implode(',', array_map('intval', $lococulta_ids)) . '}'
        ]);

        if ($rcount = $stmt->rowCount() != count($lococulta_ids)) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao deletar localização oculta do rastreador, número de linhas afetadas diferente do esperado", "" . $rcount . " != " . count($lococulta_ids))];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao deletar localização oculta do rastreador", $e->getMessage())];
    }
}

function getOuvintesDisponiveis($credenciais, $rastreador_id) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para ver ouvintes do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    try {
        $sql = "select ur_id, u_id, u_nome from getOuvintesDoRastreador(:rastreador_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "rastreador_id" => '{' . $rastreador_id . '}'
        ]);
        $ouvintes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ["success" => true, "ouvintes" => $ouvintes];
    } catch (Exception $e) {
        return ["error" => errorMessage("Erro ao buscar ouvintes do rastreador", $e->getMessage())];
    }
}

function setOuvintesLocOculta($credenciais, $lococulta_ids, $usuario_rastreador_ids, $rastreador_id) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    if (!is_array($lococulta_ids) || count($lococulta_ids) < 1) {
        return ["error" => errorMessage("Array de ids de localizações ocultas inválido", $lococulta_ids)];
    }

    if (!is_array($usuario_rastreador_ids)) {
        return ["error" => errorMessage("Array de ids de ouvintes inválido", $usuario_rastreador_ids)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerLocOcultaDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para setar ouvintes das localizações ocultas do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    try {
        $pdo->beginTransaction();

        $sql = "select * from setOuvintesParaLocOculta(:lococulta_ids, :usuario_rastreador_ids, :rastreador_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "lococulta_ids" => '{' . implode(',', array_map('intval', $lococulta_ids)) . '}',
            "usuario_rastreador_ids" => '{' . implode(',', array_map('intval', $usuario_rastreador_ids)) . '}',
            "rastreador_id" => $rastreador_id
        ]);

        if ($rcount = $stmt->rowCount() < 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao setar ouvintes das localizações ocultas do rastreador, nenhuma linha afetada", "" . $rcount)];
        }

        $sql = "select * from getUsuariosDaLocOculta(:locsOcultasIds)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "locsOcultasIds" => '{' . implode(',', array_map('intval', $lococulta_ids)) . '}'
        ]);
        $ouvintes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo->commit();
        return ["success" => true, "ouvintes" => $ouvintes];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao setar ouvintes das localizações ocultas do rastreador", $e->getMessage())];
    }
}