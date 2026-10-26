<?php

function getAuditoriaDoSistema($credenciais, $tabela = null, $filtro = null, $data_inicio = null, $data_fim = null, $sucesso = null) {
    $params = [];
    $where = [];

    if ($tabela) {
        $params[':tabela'] = "%$tabela%";
        $where[] = "al.tabela ILIKE :tabela";
    }
    if ($filtro) {
        $params[':filtro'] = "%$filtro%";
        $where[] = "(al.acao ILIKE :filtro OR al.antes ILIKE :filtro OR al.depois ILIKE :filtro)";
    }
    if ($data_inicio) {
        $params[':data_inicio'] = "$data_inicio 00:00:00";
        $where[] = "al.data >= :data_inicio";
    }
    if ($data_fim) {
        $params[':data_fim'] = "$data_fim 23:59:59.999";
        $where[] = "al.data <= :data_fim";
    }
    if ($sucesso !== null) {
        $params[':sucesso'] = $sucesso ? 1 : 0;
        $where[] = "al.sucesso = :sucesso";
    }


    $Audit = (new Auditoria())->add('audit_log', 'getAuditoriaDoSistema', null, ["params" => $params], false);
    
    $pdo = $credenciais["pdo"];
    $sql = "SELECT al.*, u.nome 
    FROM audit_log al
    JOIN usuario u
    ON u.id = al.usuario_id
    " . (empty($where) ? "" : "WHERE " . implode(" AND ", $where)) . " ORDER BY al.data DESC"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $Audit->depois(["qnt_audits" => count($logs)]);
    $Audit->sucesso(true);
    return ["success" => true, "auditorias" => $logs];
}

function getSimpleTabelasAuditoria($credenciais, $filter = '%') {
    $Audit = (new Auditoria())->add('audit_log', 'getSimpleTabelasAuditoria', null, null, false);
    
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT DISTINCT lower(tabela) AS tabela FROM audit_log WHERE tabela ILIKE :filter ORDER BY lower(tabela)");
    $stmt->execute([':filter' => "%$filter%"]);
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $Audit->depois(["qnt_tabelas_juntas" => count($tabelas)]);

    $tabelas = refazArrayComValoresStringDelimitados($tabelas, '|');
    $Audit->depois(["qnt_tabelas_separadas" => count($tabelas)]);

    $Audit->sucesso(true);
    return ["success" => true, "tabelas" => $tabelas];
}