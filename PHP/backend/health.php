<?php
// Verificação de saúde para monitoramento: não expõe detalhes internos.
require __DIR__ . "/include_me.php";

getDataBase()->query("select 1");
returnJson(["success" => true, "status" => "ok", "time" => date(DATE_ATOM)]);
