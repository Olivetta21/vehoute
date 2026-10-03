<?php
/*
    [
        {
            "id": 1,
            "id_inicial": 1,
            "id_final": 10,
            "data_inicial": "2024-06-01 00:00:00",
            "data_final": "2024-06-01 23:59:59",
            "rastreador_id": 1,
            "identificacao": "Ocultar dia X",
            "novos_ouvintes": true,
            "ouvintes": [
                {
                    "usuario_rastreador_id": 1,
                    "nome": "Fulano de Tal"
                }
            ]
        }
    ]

 */
require __DIR__ . "/../../../include_me.php";
require __DIR__ . "/f_lococultadosrastreadores.php";
$credenciais = getCredentials();

try {

//usuario requisitando suas localizações ocultas de rastreadores;
if ($search = getRequestValue("get", "is_array")) {    
    returnJson(getLocOcultaDoRastreador($credenciais, $search["rastreador_id"], $search["search"]));
}

if ($create = getRequestValue("create", "is_array")) {
    returnJson(
        createLocOcultaDoRastreador($credenciais,
            $create["rastreador_id"], $create["id_inicial"], $create["id_final"], $create["data_inicial"], $create["data_final"], $create["identificacao"]
        )
    );
}

if ($update = getRequestValue("update", "is_array")) {
    returnJson(
        updateLocOcultaDoRastreador($credenciais,
            $update["rastreador_id"], $update["lococulta_id"], $update["array_caracteristicas_novosvalores"]
        )
    );
}

if ($delete = getRequestValue("delete", "is_array")) {    
    returnJson(deleteLocOcultaDoRastreador($credenciais, $delete["rastreador_id"], $delete["lococulta_ids"]));
}


if ($rastreador_id = getRequestValue("get_ouvintes", "is_int")) {
    returnJson(getOuvintesDisponiveis($credenciais, $rastreador_id));
}

if ($set_ouvintes = getRequestValue("set_ouvintes", "is_array")) {
    returnJson(setOuvintesLocOculta($credenciais, $set_ouvintes["lococulta_ids"], $set_ouvintes["usuario_rastreador_ids"], $set_ouvintes["rastreador_id"]));
}

} catch (Exception $e) {
    returnJson(["error" => errorMessage("Erro ao processar requisição", $e->getMessage())]);
}

returnJson(["error"=>"invalid_request"]);

?>