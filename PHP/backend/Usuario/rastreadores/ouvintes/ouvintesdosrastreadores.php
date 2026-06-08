<?php
require __DIR__ . "/../../../include_me.php";
require __DIR__ . "/f_ouvintesdosrastreadores.php";
require __DIR__ . "/f_changestatusofrastreadorusuario.php";
$credenciais = getCredentials();

try {

//usuario requisitando seus ouvintes de rastreadores;
if ($search = getRequestValue("get", "is_array")) {    
    returnJson(getOuvintesDoRastreador($credenciais, $search["rastreador_id"], $search["name_filter"]));
}

//dono adicionando um rastreador a um usuario(ouvinte);
if ($dados = getRequestValue("dono_envia_proposta_ouvinte", "is_array")) {
    returnJson(donoEnviaPropostaDeOuvinteAUmUsuario(
        $credenciais,
        $dados["rastreador_id"] ?? null,
        $dados["nome"] ?? null,
        $dados["usuario_id_destino"] ?? null
    ));
}

//dono enviando uma proposta de transferência de posse de um rastreador a um usuario(ouvinte);
if ($ur_id = getRequestValue("proposta_transferencia_de_posse", "is_int")) {
    returnJson(donoEnviaPropostaDeTransferenciaDePosse(
        $credenciais,
        $ur_id ?? null
    ));
}

//dono cancela a proposta de transferência de posse de um rastreador a um usuario(ouvinte);
if ($ur_id = getRequestValue("cancelar_transferencia_de_posse", "is_int")) {
    returnJson(donoCancelaTransferenciaDePosse(
        $credenciais,
        $ur_id ?? null
    ));
}

//dono pausando um rastreamento de um usuario(ouvinte) a um rastreador seu;
if ($switch = getRequestValue("pause_tracking", "is_int")) {
    returnJson(pauseTrackingForOuvinte(
        $credenciais,
        $switch ?? null
    ));
}

//dono resumindo um rastreamento de um usuario(ouvinte) a um rastreador seu;
if ($switch = getRequestValue("resume_tracking", "is_int")) {
    returnJson(resumeTrackingForOuvinte(
        $credenciais,
        $switch ?? null
    ));
}

//dono aceitando um pedido de inclusão de um usuario(ouvinte) a um rastreador seu;
if ($ur_id = getRequestValue("aceitar_novo_ouvinte", "is_int")) {
    returnJson(donoAceitaNovoOuvinte($credenciais, $ur_id));
}

//dono recusando um pedido de inclusão de um usuario(ouvinte) a um rastreador seu;
if ($ur_id = getRequestValue("recusar_novo_ouvinte", "is_int")) {
    returnJson(donoRecusaNovoOuvinte($credenciais, $ur_id));
}

//dono deleta um ouvinte de um rastreador seu;
if ($ur_id = getRequestValue("deletar_ouvinte", "is_int")) {
    returnJson(donoDeletaOuvinte($credenciais, $ur_id));
}


} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);