<?php

/** Aceita somente 'AAAA-MM-DD' de uma data que exista; vazio significa "sem limite". */
function validarDataFiltro($data) {
    if ($data === null || $data === '') return null;
    if (!is_string($data) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $p) || !checkdate((int) $p[2], (int) $p[3], (int) $p[1])) {
        return false;
    }
    return $data;
}

function getLocalizacoesDoRastreador($credentials, $rastreador_id, $data_inicial = null, $data_final = null) {
    $inicio = validarDataFiltro($data_inicial);
    $fim = validarDataFiltro($data_final);
    if ($inicio === false || $fim === false) {
        return ["error" => "Período inválido"];
    }
    $Audit = (new Auditoria())->add('localizacao', 'getLocalizacoesDoRastreador', null, ["rastreador_id" => $rastreador_id, "inicio" => $inicio, "fim" => $fim], false);

    $pdo = null;
    try {
        $pdo = $credentials["pdo"];
        $parametros = [
            "rastreador_id"=>$rastreador_id,
            "usuario_id"=>$credentials["id"]
        ];
        $origem = "from getLocDoRastreadorParaOuvinte(:rastreador_id, :usuario_id, 'salvos') where is_oculto = false";

        // o período é aplicado pelo banco; a data final inclui o dia inteiro
        $periodo = "";
        $parametros_periodo = [];
        if ($inicio !== null) {
            $periodo .= " and l_data >= cast(:inicio as date)";
            $parametros_periodo["inicio"] = $inicio;
        }
        if ($fim !== null) {
            $periodo .= " and l_data < cast(:fim as date) + 1";
            $parametros_periodo["fim"] = $fim;
        }

        $stmt = $pdo->prepare("select l_id, l_lat, l_lng, l_data $origem $periodo");
        $stmt->execute($parametros + $parametros_periodo);
        if ($stmt->rowCount() > 0) {
            $localizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($localizacoes as &$localizacao) {
                $localizacao["l_lat"] = floatval($localizacao["l_lat"]);
                $localizacao["l_lng"] = floatval($localizacao["l_lng"]);
            }

            $Audit->depois(["qnt_localizacoes" => count($localizacoes)]);
            $Audit->sucesso(true);
            return ["success" => true, "localizacoes" => $localizacoes];
        } else {
            // nada no período: informa quando foi a última, para a tela poder ajustar o filtro
            $ultima = null;
            if ($periodo !== "") {
                $stmt = $pdo->prepare("select max(l_data) $origem");
                $stmt->execute($parametros);
                $ultima = $stmt->fetchColumn() ?: null;
            }
            $Audit->depois(["qnt_localizacoes" => 0]);
            $Audit->sucesso(true);
            return ["success" => true, "localizacoes" => [], "ultima_data" => $ultima, "message" => "Nenhuma localização encontrada"];
        }
    } catch (Exception $e) {
        return ["error" => errorMessage("Error in getLocalizacoesDoRastreador",$e->getMessage())];
    }
}