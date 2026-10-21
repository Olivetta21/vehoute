<?php
require __DIR__ . "/../../include_me.php";
require __DIR__ . "/f_permissoes_rastreador.php";
$credenciais = getCredentials();

try {

if ($permissoes = getRequestValue("permissoes", "is_array")) {
    returnJson(getPermissoesRastreador($credenciais, $permissoes['agrupar_por'], $permissoes['filter']));
}

if ($grupo_nome = getRequestValue("grupos", "is_string")) {
    returnJson(getGrupos($credenciais, $grupo_nome));
}

if ($novo_grupo = getRequestValue("addGrupo", "is_string")) {
    returnJson(addGrupo($credenciais, $novo_grupo));
}

if ($grupo_id = getRequestValue("deleteGrupo", "is_numeric")) {
    returnJson(deleteGrupo($credenciais, $grupo_id));
}

if ($trackerandgroup = getRequestValue("addRastreadorNoGrupo", "is_array")) {
    returnJson(addRastreadorNoGrupo($credenciais, $trackerandgroup['rastreadorID'], $trackerandgroup['grupoID']));
}

if ($trackerandgroup = getRequestValue("removerRastreadorDoGrupo", "is_array")) {
    returnJson(removerRastreadorDoGrupo($credenciais, $trackerandgroup['rastreadorID'], $trackerandgroup['grupoID']));
}

if ($group_name = getRequestValue("searchGrupos", "is_string")) {
    returnJson(searchGrupos($credenciais, $group_name));
}

if ($permissoes_name = getRequestValue("searchPermissoes", "is_string")) {
    returnJson(searchPermissoes($credenciais, $permissoes_name));
}

if ($permissao = getRequestValue("addVinculoPermissao", "is_array")) {
    returnJson(addVinculoPermissao($credenciais, $permissao['rastreadorOuGrupoID'], $permissao['is_tracker'], $permissao['negado'], $permissao['permissaoID']));
}

if ($permissao = getRequestValue("removeVinculoPermissao", "is_array")) {
    returnJson(removeVinculoPermissao($credenciais, $permissao['vinculo_id'], $permissao['rastreadorOuGrupoID'], $permissao['is_tracker'], $permissao['permissaoID']));
}

} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);