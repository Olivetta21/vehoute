<?php
require __DIR__ . "/../../include_me.php";
require_once __DIR__ . "/f_rastreadoresusuarios.php";
$credenciais = getCredentials();

try {

//usuario requisitando seus rastreadores;
if ($name_filter = getRequestValue("get", "is_string")) {
    returnJson(getRastreadoresDoUsuario($credenciais, $name_filter));
}

//usuario tentando verificar se esse rastreador existe e se ele pode ser adicionado por ele;
if ($dados_for_check = getRequestValue("check_rastreador", "is_array")) {
    returnJson(checkRastreadorDoUsuario(
        $credenciais,
        $dados_for_check["token"] ?? null,
        $dados_for_check["senha"] ?? null,
    ));
}

//usuario tentando adicionar um rastreador a si mesmo;
if ($dados_for_add = getRequestValue("add_rastreador", "is_array")) {
    returnJson(usuarioAdicionaUmRastreador(
        $credenciais,
        $dados_for_add["rastreador_id"] ?? null,
        $dados_for_add["dono_id"] ?? null,
        $dados_for_add["token"] ?? null,
        $dados_for_add["senha"] ?? null,
        $dados_for_add["status"] ?? null,
        $dados_for_add["nome"] ?? null
    ));
}

//usuario(ouvinte) aceitando um pedido de inclusão de um rastreador a ele por um dono;
if ($ur_id = getRequestValue("aceitar_proposta_ouvinte", "is_int")) {
    returnJson(usuarioAceitaPropostaDeOuvinte($credenciais, $ur_id));
}

//usuario(ouvinte) recusando um pedido de inclusão de um rastreador a ele por um dono;
if ($ur_id = getRequestValue("recusar_proposta_ouvinte", "is_int")) {
    returnJson(usuarioRecusaPropostaDeOuvinte($credenciais, $ur_id));
}

//usuario(ouvinte) aceitando um pedido de transferência de um rastreador a ele por um dono;
if ($ur_id = getRequestValue("aceitar_transferencia_posse", "is_int")) {
    returnJson(acceptTransferenciaDePosse($credenciais, $ur_id));
}

//usuario(ouvinte) recusando um pedido de transferência de um rastreador a ele por um dono;
if ($ur_id = getRequestValue("recusar_transferencia_posse", "is_int")) {
    returnJson(declineTransferenciaDePosse($credenciais, $ur_id));
}

//usuario(ouvinte) deletando o proprio UR;
if ($ur_id = getRequestValue("excluir_rastreador", "is_int")) {
    returnJson(excluirRastreadorDoOuvinte($credenciais, $ur_id));
}

} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);