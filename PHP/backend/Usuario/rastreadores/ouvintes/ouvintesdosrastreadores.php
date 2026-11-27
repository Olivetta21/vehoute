<?php
require __DIR__ . "/../../../include_me.php";
require __DIR__ . "/f_ouvintesdosrastreadores.php";
require __DIR__ . "/f_changestatusofrastreadorusuario.php";
$credenciais = getCredentials();

try {

//usuario requisitando seus ouvintes de rastreadores;
if ($search = getRequestValue("get", "is_array")) {    
    returnJson(getOuvintesDoRastreador($credenciais, $search["rastreador_id"] ?? null, $search["name_filter"] ?? null));
}

//dono adicionando um rastreador a um usuario(ouvinte);
if ($dados = getRequestValue("dono_envia_proposta_ouvinte", "is_array")) {
    userHasPermission(["Gerenciar ouvintes", "Proposta Rastreio"]);
    limitarRequisicoes('proposta_ouvinte', 20, 600, $credenciais["id"]); // contra adivinhação de códigos
    returnJson(donoEnviaPropostaDeOuvintePorCodigo(
        $credenciais,
        $dados["rastreador_id"] ?? null,
        $dados["nome"] ?? null,
        $dados["codigo"] ?? null
    ));
}

//dono incluindo a si mesmo como ouvinte do próprio rastreador;
if ($dados = getRequestValue("incluir_me", "is_array")) {
    userHasPermission("Gerenciar ouvintes");
    returnJson(donoIncluiASiMesmo($credenciais, $dados["rastreador_id"] ?? null, $dados["nome"] ?? null));
}

//dono enviando uma proposta de transferência de posse de um rastreador a um usuario(ouvinte);
if ($ur_id = getRequestValue("proposta_transferencia_de_posse", "is_int")) {
    userHasPermission("Transferir Posse");
    // Transferir para o próprio vínculo deixaria uma proposta que ninguém pode aceitar
    $stmt = $credenciais["pdo"]->prepare("select 1 from usuario_rastreador where id = :id and usuario_id = :usuario_id");
    $stmt->execute(["id" => $ur_id, "usuario_id" => $credenciais["id"]]);
    if ($stmt->fetchColumn() !== false) {
        returnJson(["error" => "Você já é o dono deste rastreador"]);
    }
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
    userHasPermission("Gerenciar ouvintes");
    returnJson(pauseTrackingForOuvinte(
        $credenciais,
        $switch ?? null
    ));
}

//dono resumindo um rastreamento de um usuario(ouvinte) a um rastreador seu;
if ($switch = getRequestValue("resume_tracking", "is_int")) {
    userHasPermission("Gerenciar ouvintes");
    returnJson(resumeTrackingForOuvinte(
        $credenciais,
        $switch ?? null
    ));
}

//dono mudando o valor das configurações de localizacao de um usuario(ouvinte) a um rastreador seu;
if ($new_value = getRequestValue("change_loc_state", "is_array")) {
    userHasPermission("Gerenciar ouvintes");
    returnJson(changeLocStateForOuvinte(
        $credenciais,
        $new_value["loc_type"] ?? null,
        $new_value["ur_id"] ?? null,
        $new_value["new_value"] ?? null
    ));
}

//dono aceitando um pedido de inclusão de um usuario(ouvinte) a um rastreador seu;
if ($ur_id = getRequestValue("aceitar_novo_ouvinte", "is_int")) {
    userHasPermission("Gerenciar ouvintes");
    returnJson(donoAceitaNovoOuvinte($credenciais, $ur_id));
}

//dono recusando um pedido de inclusão de um usuario(ouvinte) a um rastreador seu;
if ($ur_id = getRequestValue("recusar_novo_ouvinte", "is_int")) {
    returnJson(donoRecusaNovoOuvinte($credenciais, $ur_id));
}

//dono deleta um ouvinte de um rastreador seu;
if ($ur_id = getRequestValue("deletar_ouvinte", "is_int")) {
    userHasPermission("Gerenciar ouvintes");
    returnJson(donoDeletaOuvinte($credenciais, $ur_id));
}


} catch (Throwable $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage() . " - " . $e->getLine() . " - " . $e->getFile())]);
}

returnJson(["error"=>"invalid_request"]);