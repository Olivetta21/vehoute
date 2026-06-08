<?php
require_once __DIR__ . "/../f_RastreadoresUsuarios.php";

function validarUsuarioPodeVerOuvintesDoRastreador($pdo, $usuario_id, $rastreador_id) {
    $sql = "select id from rastreador where dono_id = :usuario_id and id = :rastreador_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "usuario_id" => $usuario_id,
        "rastreador_id" => $rastreador_id
    ]);

    return $stmt->rowCount() === 1;
}

function getOuvintesDoRastreador($credenciais, $rastreador_id, $name_filter) {
    if (!validarIdPositivo($rastreador_id)) {
        return ["error" => errorMessage("Id de rastreador inválido", $rastreador_id)];
    }

    $name_filter = normalizarFiltroTexto($name_filter);
    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    if (!validarUsuarioPodeVerOuvintesDoRastreador($pdo, $usuario_id, $rastreador_id)) {
        return ["error" => errorMessage("Usuário não tem permissão para ver os ouvintes do rastreador", $usuario_id . " - " . $rastreador_id)];
    }

    try {
        $sql = "select * from vw_ouvintes_dos_rastreadores where rastreador_id = :rastreador_id and (u_nome ilike :name_filter or email ilike :name_filter) order by u_nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "rastreador_id" => $rastreador_id,
            "name_filter" => '%' . $name_filter . '%'
        ]);

        return ["success" => true, "ouvintes" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (Exception $e) {
        return ["error" => errorMessage("Erro ao buscar ouvintes do rastreador", $e->getMessage())];
    }
}


function donoEnviaPropostaDeTransferenciaDePosse($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("select rastreador_id from vw_rastreadores_dos_usuarios where id = :ur_id and dono_id = :dono_id and ur_status in (1, 2)");
        $stmt->execute(["ur_id" => $ur_id, "dono_id" => $usuario_id]);
        if ($stmt->rowCount() !== 1) {
            return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere para enviar proposta de transferência de posse", $ur_id . " - " . $usuario_id)];
        }
        $rastreador_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("update usuario_rastreador set status = 5 where id = :id and status in (1, 2)");
        $stmt->execute(["id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao enviar proposta de transferência de posse para o ouvinte", $ur_id . " - " . $usuario_id)];
        }

        $stmt = $pdo->prepare("update rastreador set status = 3 where id = :rastreador_id and dono_id = :dono_id and status in (1, 2)");
        $stmt->execute(["rastreador_id" => $rastreador_id, "dono_id" => $usuario_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao enviar proposta de transferência de posse para o ouvinte ao atualizar status do rastreador", $rastreador_id . " - " . $usuario_id)];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao enviar proposta de transferência de posse para o ouvinte", $e->getMessage())];
     }
}


function donoCancelaTransferenciaDePosse($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    $pdo = $credenciais["pdo"];
    $usuario_id = $credenciais["id"];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("select rastreador_id from vw_rastreadores_dos_usuarios where id = :ur_id and dono_id = :dono_id and ur_status = 5");
        $stmt->execute(["ur_id" => $ur_id, "dono_id" => $usuario_id]);
        if ($stmt->rowCount() !== 1) {
            return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere para cancelar proposta de transferência de posse", $ur_id . " - " . $usuario_id)];
        }
        $rastreador_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("update usuario_rastreador set status = 2 where id = :id and status = 5");
        $stmt->execute(["id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao cancelar proposta de transferência de posse para o ouvinte", $ur_id . " - " . $usuario_id)];
        }

        $stmt = $pdo->prepare("update rastreador set status = 1 where id = :rastreador_id and dono_id = :dono_id and status = 3");
        $stmt->execute(["rastreador_id" => $rastreador_id, "dono_id" => $usuario_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao cancelar proposta de transferência de posse para o ouvinte ao atualizar status do rastreador", $rastreador_id . " - " . $usuario_id)];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao cancelar proposta de transferência de posse para o ouvinte", $e->getMessage())];
    }
}


function donoEnviaPropostaDeOuvinteAUmUsuario($credenciais, $rastreador_id, $nome, $usuario_id_destino) {
    $pdo = $credenciais["pdo"];

    if (!$rastreador_id || !$nome || !$usuario_id_destino) {
        return ["error" => errorMessage("Id do rastreador, nome ou usuário destino não fornecidos", $rastreador_id . " - " . $usuario_id_destino)];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("select id from rastreador where id = :id and dono_id = :dono_id and status in (1, 2) and ativo = true");
        $stmt->execute(["id" => $rastreador_id, "dono_id" => $credenciais["id"]]);
        if ($stmt->rowCount() !== 1) {
            return ["error" => errorMessage("Rastreador não encontrado ou usuário autenticado não é o dono para enviar proposta de ouvinte", $rastreador_id . " - " . $credenciais["id"])];
        }

        $stmt = $pdo->prepare("insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (:usuario_id, :rastreador_id, :nome, 4)");
        $stmt->execute([
            "usuario_id" => $usuario_id_destino,
            "rastreador_id" => $rastreador_id,
            "nome" => $nome
        ]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao enviar proposta de ouvinte para o usuário", $rastreador_id . " - " . $usuario_id_destino)];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ["error" => errorMessage("Erro ao enviar proposta de ouvinte para o usuário", $e->getMessage())];
    }
}


function validarDonoCorretoForAcceptDecline($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("select id from vw_rastreadores_dos_usuarios where id = :ur_id and dono_id = :dono_id and ur_status = 3");
    $stmt->execute(["ur_id" => $ur_id, "dono_id" => $credenciais["id"]]);

    return $stmt->rowCount() === 1;
}

function donoAceitaNovoOuvinte($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarDonoCorretoForAcceptDecline($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte", $credenciais["id"] . " - " .$ur_id)];
    }

    try {
        $pdo = $credenciais["pdo"];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("update usuario_rastreador set status = 2 where id = :id and status = 3");
        $stmt->execute(["id" => $ur_id]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte ao atualizar", $credenciais["id"] . " - " .$ur_id)];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao aceitar novo ouvinte para o rastreador", $e->getMessage())];
     }
    
}


function donoRecusaNovoOuvinte($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarDonoCorretoForAcceptDecline($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, usuário autenticado não é o dono, ou status atual não confere ao aceitar novo ouvinte", $credenciais["id"] . " - " .$ur_id)];
    }
    
    try {
        $pdo = $credenciais["pdo"];    
        $pdo->beginTransaction();
        
        $deleteResult = deleteUsuarioRastreador($pdo, $ur_id);
        if (isset($deleteResult["error"]) or !isset($deleteResult["success"])) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao recusar novo ouvinte para o rastreador de usuário", $deleteResult["error"] ?? "Unknown error")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao recusar novo ouvinte para o rastreador de usuário", $e->getMessage())];
    }
}

function validarDonoCorretoForDelete($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("select id from vw_rastreadores_dos_usuarios where id = :ur_id and dono_id = :dono_id");
    $stmt->execute(["ur_id" => $ur_id, "dono_id" => $credenciais["id"]]);

    return $stmt->rowCount() === 1;
}

function donoDeletaOuvinte($credenciais, $ur_id) {
    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarDonoCorretoForDelete($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado ou usuário autenticado não é o dono para deletar ouvinte", $credenciais["id"] . " - " .$ur_id)];
    }

    try {
        $pdo = $credenciais["pdo"];    
        $pdo->beginTransaction();
        

        $deleteResult = deleteUsuarioRastreador($pdo, $ur_id);
        if (isset($deleteResult["error"]) or !isset($deleteResult["success"])) {
            $pdo->rollBack();
            return ["error" => errorMessage("Erro ao deletar ouvinte do rastreador de usuário", $deleteResult["error"] ?? "Unknown error")];
        }

        $pdo->commit();
        return ["success" => true];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao deletar ouvinte do rastreador de usuário", $e->getMessage())];
    }
}
