<?php 

function existsExactUR_WithIdAndStatus($pdo, $ur_id, $status) {
    $stmt = $pdo->prepare("select * from rastreador_usuario where id = :id and status = :status");
    $stmt->execute(["id" => $ur_id, "status" => $status]);
    return $stmt->rowCount() === 1;
}

function existsExactR_WithIdDonoTKStatusSenha($pdo, $rastreador_id, $dono_id, $token, $status, $senha) {
    $stmt = $pdo->prepare("select * from rastreador where id = :id and dono_id = :dono_id and token_publico = :token and status = :status and senha = :senha and status in (1, 2) and ativo = true");
    $stmt->execute(["id" => $rastreador_id, "dono_id" => $dono_id, "token" => $token, "status" => $status, "senha" => $senha]);
    return $stmt->rowCount() === 1;
}

function getRastreadoresDoUsuario($credenciais, $name_filter) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("select * from vw_rastreadores_dos_usuarios where usuario_id = :usuario_id and rastreador_nome ilike :name_filter order by rastreador_nome");
    $stmt->execute(["usuario_id" => $credenciais["id"], "name_filter" => "%$name_filter%"]);
    $rastreadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return ["success" => true, "rastreadores" => $rastreadores];
}


function checkRastreadorDoUsuario($credenciais, $token, $senha) {
    if (!$token || !$senha) {
        return ["error" => errorMessage("Token ou senha não fornecidos", null)];
    }

    $stmt = $credenciais["pdo"]->prepare("select id, dono_id, token_publico, status from rastreador where token_publico = :token and senha = :senha and status in (1, 2) and ativo = true");
    $stmt->execute(["token" => $token, "senha" => $senha]);

    if ($stmt->rowCount() === 1) {
        $rastreador = $stmt->fetch(PDO::FETCH_ASSOC);
        return ["success" => true, "rastreador" => $rastreador];
    } else {
        return ["error" => errorMessage("Token ou senha inválidos", null)];
    }
}

function usuarioAdicionaUmRastreador($credenciais, $rastreador_id, $dono_id, $token, $senha, $status, $nome) {
    $pdo = $credenciais["pdo"];

    if (!$rastreador_id || !$nome) {
        return ["error" => errorMessage("Id do rastreador ou nome não fornecidos", $rastreador_id . " - " . $token)];
    }

    if (!existsExactR_WithIdDonoTKStatusSenha($pdo, $rastreador_id, $dono_id, $token, $status, $senha)) {
        return ["error" => errorMessage("Rastreador não encontrado ou status do rastreador não confere para adição", $rastreador_id . " - " . $token)];
    }

    $ur_status = ($dono_id === null || $dono_id === $credenciais["id"]) ? 1 : 3;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("select id from usuario_rastreador where usuario_id = :usuario_id and rastreador_id = :rastreador_id for update");
        $stmt->execute(["usuario_id" => $credenciais["id"], "rastreador_id" => $rastreador_id]);
        if ($stmt->rowCount() > 0) {
            $pdo->rollBack();
            return ["error" => errorMessage("Usuário já tem um rastreador com esse id", $credenciais["id"] .' - '. $rastreador_id)];
        }

        $stmt = $pdo->prepare("insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (:usuario_id, :rastreador_id, :nome, :status) returning id");
        $stmt->execute([
            "usuario_id" => $credenciais["id"],
            "rastreador_id" => $rastreador_id,
            "nome" => $nome,
            "status" => $ur_status
        ]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao adicionar rastreador ao usuário", $rastreador_id . " - " . $token)];
        }

        $ur_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("select * from vw_rastreadores_dos_usuarios where id = :ur_id");
        $stmt->execute(["ur_id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao recuperar rastreador de usuário adicionado", $ur_id . " - " . $token)];
        }

        $new_ur = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
        return ["success" => true, "usuario_rastreador" => $new_ur];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ["error" => errorMessage("Erro ao adicionar rastreador ao usuário", $e->getMessage())];
    }
}



function deleteUsuarioRastreador($pdo, $ur_id) {
    try {
        if (!validarIdPositivo($ur_id)) {
            return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
        }

        if (!$pdo->inTransaction()) {
            return ["error" => errorMessage("deleteUsuarioRastreador deve ser chamado dentro de uma transação", $ur_id)];
        }

        $stmt = $pdo->prepare("delete from usuario_rastreador where id = :id");
        $stmt->execute(["id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            return ["error" => errorMessage("Rastreador de usuário não encontrado para exclusão", $ur_id)];
        }

        return ["success" => true];
    } catch (Exception $e) {
        return ["error" => errorMessage("Erro ao excluir rastreador de usuário", $e->getMessage())];
    }
}



function validarUsuarioCorretoForAcceptDecline($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("select id from vw_rastreadores_dos_usuarios where id = :ur_id and usuario_id = :usuario_id and ur_status = 4");
    $stmt->execute(["ur_id" => $ur_id, "usuario_id" => $credenciais["id"]]);

    return $stmt->rowCount() === 1;
}

function usuarioAceitaPropostaDeOuvinte($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarUsuarioCorretoForAcceptDecline($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o usuário correto, ou status atual não confere ao aceitar proposta de ouvinte", $credenciais["id"] . " - " .$ur_id)];
    }

    try {
        $pdo = $credenciais["pdo"];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("update usuario_rastreador set status = 2 where id = :id and status = 4 and usuario_id = :usuario_id returning id");
        $stmt->execute(["id" => $ur_id, "usuario_id" => $credenciais["id"]]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o usuário correto, ou status atual não confere ao aceitar proposta de ouvinte ao atualizar", $credenciais["id"] . " - " .$ur_id)];
        }

        $ur_id = $stmt->fetchColumn();
        $stmt = $pdo->prepare("select * from vw_rastreadores_dos_usuarios where id = :ur_id");
        $stmt->execute(["ur_id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao recuperar rastreador de usuário aceito", $ur_id)];
        }

        $updated_ur = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
        return ["success" => true, "usuario_rastreador" => $updated_ur];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao aceitar proposta de ouvinte para o rastreador de usuário", $e->getMessage())];
     }
}

function usuarioRecusaPropostaDeOuvinte($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarUsuarioCorretoForAcceptDecline($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o usuário correto, ou status atual não confere ao recusar proposta de ouvinte", $credenciais["id"] . " - " .$ur_id)];
    }
    
    $pdo = $credenciais["pdo"];
    try {
        $pdo->beginTransaction();
        $deleteResult = deleteUsuarioRastreador($pdo, $ur_id);
        if (isset($deleteResult["error"]) or !isset($deleteResult["success"])) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao recusar proposta de ouvinte para o rastreador de usuário", $deleteResult["error"] ?? "Unknown error")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao recusar proposta de ouvinte para o usuário", $e->getMessage())];
    }
}


function validarUsuarioCorretoForTransferencia($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("
    select id
    from rastreador
    where
        id = (select rastreador_id from usuario_rastreador where id = :id and usuario_id = :usuario_id and status = 5)
        and dono_id != :usuario_id
        and status = 3");
    $stmt->execute(["id" => $ur_id, "usuario_id" => $credenciais["id"]]);

    if ($stmt->rowCount() === 1) {
        return $stmt->fetchColumn();
    }
    return null;
}
//
function acceptTransferenciaDePosse($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];

    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }
    
    $rastreador_id = validarUsuarioCorretoForTransferencia($credenciais, $ur_id);
    if (!$rastreador_id) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o usuário correto, ou status atual não confere ao aceitar transferência de posse", $credenciais["id"] . " - " .$ur_id)];
    }  

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("update usuario_rastreador set status = 1 where id = :id and status = 5");
        $stmt->execute(["id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador de usuário não encontrado ou status atual não confere ao aceitar transferência", $credenciais["id"] . " - " . $ur_id)];
        }

        $stmt = $pdo->prepare("update rastreador set dono_id = :novo_dono, status = 2 where id = :id and dono_id != :novo_dono and status = 3");
        $stmt->execute(["id" => $rastreador_id, "novo_dono" => $credenciais["id"]]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador não encontrado ou usuário não é o novo dono", $rastreador_id . " - " . $credenciais["id"])];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao aceitar transferência do rastreador de usuário", $e->getMessage())];
    }
}

function declineTransferenciaDePosse($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];

    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    $rastreador_id = validarUsuarioCorretoForTransferencia($credenciais, $ur_id);
    if (!$rastreador_id) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o usuário correto, ou status atual não confere ao aceitar transferência de posse", $credenciais["id"] . " - " .$ur_id)];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("update usuario_rastreador set status = 2 where id = :id and status = 5");
        $stmt->execute(["id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador de usuário não encontrado ou status atual não confere ao recusar transferência", $credenciais["id"] . " - " . $ur_id)];
        }

        $stmt = $pdo->prepare("update rastreador set status = 2 where id = :id and dono_id != :novo_dono and status = 3");
        $stmt->execute(["id" => $rastreador_id, "novo_dono" => $credenciais["id"]]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador não encontrado ou usuário é o dono", $rastreador_id . " - " . $credenciais["id"])];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao recusar transferência do rastreador de usuário", $e->getMessage())];
    }
}



function validarOuvinteCorretoForDeleteUR($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("select ur_status from vw_rastreadores_dos_usuarios where id = :ur_id and usuario_id = :ouvinte_id");
    $stmt->execute(["ur_id" => $ur_id, "ouvinte_id" => $credenciais["id"]]);

    if ($stmt->rowCount() === 1) {
        $ur_status = $stmt->fetchColumn();
        return in_array($ur_status, [1,2,3,4]);
    }

    return false;
}
function excluirRastreadorDoOuvinte($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarOuvinteCorretoForDeleteUR($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado ou usuário autenticado não é o ouvinte para excluir", $credenciais["id"] . " - " .$ur_id)];
    }


    try {
        $pdo = $credenciais["pdo"];    
        $pdo->beginTransaction();
        
        $deleteResult = deleteUsuarioRastreador($pdo, $ur_id);
        if (isset($deleteResult["error"]) or !isset($deleteResult["success"])) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao excluir rastreador do ouvinte", $deleteResult["error"] ?? "Unknown error")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao excluir rastreador do ouvinte", $e->getMessage())];
    }
}