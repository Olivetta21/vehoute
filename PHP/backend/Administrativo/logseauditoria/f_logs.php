<?php


function getLogsDoSistema($credenciais, $titulo = null, $mensagem = null, $ip = null, $data_inicio = null, $data_fim = null) {
    $params = [];
    $where = [];

    if ($titulo) {
        $params[':titulo'] = "%$titulo%";
        $where[] = "el.titulo ILIKE :titulo";
    }
    if ($mensagem) {
        $params[':mensagem'] = "%$mensagem%";
        $where[] = "el.mensagem ILIKE :mensagem";
    }
    if ($ip) {
        $params[':ip'] = "%$ip%";
        $where[] = "el.ip ILIKE :ip";
    }
    if ($data_inicio) {
        $params[':data_inicio'] = "$data_inicio 00:00:00";
        $where[] = "el.data >= :data_inicio";
    }
    if ($data_fim) {
        $params[':data_fim'] = "$data_fim 23:59:59.999";
        $where[] = "el.data <= :data_fim";
    }

    $Audit = (new Auditoria())->add('errors_log', 'getLogsDoSistema', null, ["params" => $params], false);
    $pdo = $credenciais["pdo"];
    $sql = "SELECT el.id, el.titulo, el.mensagem, u.id u_id, u.nome u_nome, el.url, el.ip, el.data
	from errors_log el
	join usuario_access_token uat on uat.token = el.u_access_token
	join usuario u on u.id = uat.usuario_id
    " . (empty($where) ? "" : "WHERE " . implode(" AND ", $where)) . " ORDER BY el.data DESC"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $Audit->depois(["qnt_logs" => count($logs)]);
    $Audit->sucesso(true);
    return ["success" => true, "logs" => $logs];
}

function getSimpleTitulos($credenciais, $filter = '%') {
    $Audit = (new Auditoria())->add('errors_log', 'getSimpleTitulos', null, ["filter" => $filter], false);
    
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT DISTINCT lower(titulo) AS titulo FROM errors_log WHERE titulo ILIKE :filter ORDER BY lower(titulo)");
    $stmt->execute([':filter' => "%$filter%"]);
    $titulos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $Audit->depois(["qnt_titulos_juntos" => count($titulos)]);

    $titulos = refazArrayComValoresStringDelimitados($titulos, '|');
    $Audit->depois(["qnt_titulos_separados" => count($titulos)]);

    $Audit->sucesso(true);
    return ["success" => true, "titulos" => $titulos];
}
