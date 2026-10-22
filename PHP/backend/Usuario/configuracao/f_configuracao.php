<?php
function getConfiguracaoUsuario($credenciais) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("SELECT 
        c.id AS config_id, c.nome AS config_nome, c.var_type AS config_var_type,
        cc.id as classe_id, cc.nome as classe_nome,
        coalesce(vc.variavel, c.padrao) AS config_variavel
        FROM configuracao c
        JOIN classe_configuracao cc on cc.id = c.classe_id
        LEFT JOIN vinculo_configuracao vc ON c.id = vc.config_id AND vc.usuario_id = :usuario_id
        ORDER BY c.classe_id, c.nome;
    ");
    $stmt->execute(['usuario_id' => $credenciais["id"]]);
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function findIndexConfig($array, $classeId) {
        foreach ($array as $indice => $config) {
            if ($config['classe_id'] == $classeId) {
                return $indice;
            }
        }
        return null;
    }

    $grupo = [];

    foreach ($configs as $config) {
        $classe_id = $config['classe_id'];
        $index = findIndexConfig($grupo, $classe_id);
        if ($index === null) {
            $grupo[] = [
                'classe_id' => $classe_id,
                'classe_nome' => $config['classe_nome'],
                'configs' => []
            ];
            $index = count($grupo) - 1; // Atualiza o índice para o novo grupo
        }
        $grupo[$index]['configs'][] = $config;

    }

    return ["success" => true, "configs" => $grupo];   
}

function updateConfiguracaoUsuario($credenciais, $config_id, $variavel) {
    $pdo = $credenciais["pdo"];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT var_type FROM configuracao WHERE id = :config_id");
        $stmt->execute(['config_id' => $config_id]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            return ["error" => errorMessage("Configuração não encontrada", $config_id)];
        }

        // Atualiza ou insere a configuração do usuário
        $stmt = $pdo->prepare("
            INSERT INTO vinculo_configuracao (config_id, config_var_type, usuario_id, variavel)
            VALUES (:config_id, :config_var_type, :usuario_id, :variavel)
            ON CONFLICT (config_id, usuario_id) DO UPDATE SET variavel = EXCLUDED.variavel
            RETURNING *;
        ");

        $stmt->execute([
            'config_id' => $config_id,
            'config_var_type' => $config['var_type'],
            'usuario_id' => $credenciais["id"],
            'variavel' => $variavel
        ]);

        if ($stmt->rowCount() == 0) {
            return ["error" => errorMessage("Erro ao atualizar configuração", "Nenhuma linha afetada")];
        }

        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
        return ["success" => true, "config" => $config];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao atualizar configuração", $e->getMessage())];
    }
}