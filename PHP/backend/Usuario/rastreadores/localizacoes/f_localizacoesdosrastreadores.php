<?php

function getLocalizacoesDoRastreador($credentials, $rastreador_id) {

    $pdo = null;
    try {
        $pdo = $credentials["pdo"];
        $stmt = $pdo->prepare("select l_id, l_lat, l_lng, l_data from getLocDoRastreadorParaOuvinte(:rastreador_id, :usuario_id) where is_oculto = false");
        $stmt->execute([
            "rastreador_id"=>$rastreador_id,
            "usuario_id"=>$credentials["id"]
        ]);
        if ($stmt->rowCount() > 0) {
            $localizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["success" => true, "localizacoes" => $localizacoes];
        } else {
            return ["success" => true, "localizacoes" => [], "message" => errorMessage("Nenhuma localização encontrada", $rastreador_id . '-' . $credentials["id"])];
        }
    } catch (Exception $e) {
        return ["error" => errorMessage("Error in getLocalizacoesDoRastreador",$e->getMessage())];
    }
}