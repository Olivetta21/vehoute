<?php
require __DIR__ . "/../../../include_me.php";
require __DIR__ . "/f_localizacoesdosrastreadores.php";
$credentials = getCredentials();

try {


//usuario requisitando suas localizações de rastreadores;
if ($rastreador_id = getRequestValue("rastreador_id", "is_int")) {
    userHasPermission("Rastreio Salvo");
    $periodo = getRequestValue("periodo", "is_array") ?: [];
    returnJson(getLocalizacoesDoRastreador($credentials, $rastreador_id, $periodo["inicio"] ?? null, $periodo["fim"] ?? null));
}



} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}

returnJson(["error"=>"invalid_request"]);