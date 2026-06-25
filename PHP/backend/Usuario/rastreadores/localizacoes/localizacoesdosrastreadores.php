<?php
require __DIR__ . "/../../../include_me.php";
require __DIR__ . "/f_localizacoesdosrastreadores.php";
$credentials = getCredentials();

try {


//usuario requisitando suas localizações de rastreadores;
if ($rastreador_id = getRequestValue("rastreador_id", "is_int")) {    
    returnJson(getLocalizacoesDoRastreador($credentials, $rastreador_id));
}



} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);