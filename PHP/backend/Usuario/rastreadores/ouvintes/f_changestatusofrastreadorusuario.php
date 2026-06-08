<?php
/*
Numero 	Significado
1 	(registrado) Recebendo rastreio normalmente
2 	(registrado) Visualização do rastreio pausada
3 	(não registrado) o usuario está aguardando o dono aprovar sua inclusão
4 	(não registrado) o dono está aguardando um usuario aceitar a proposta
5 	(registrado) Este ouvinte recebeu um pedido de transferência, o dono espera pela resposta

Só vai pro normal se ele estiver no estado de pause;
Para o estado de pause, o atual pode qualquer um exceto 5;
um novo registro de UR sempre vai começar com:
    2 - quando usuario adicionou um rastreador que nao tinha nenhun dono;
    3 - quando um usuario quer adicionar um rastreador que já tem dono, e o dono precisa aprovar;
    4 - quando um dono quer adicionar um rastreador a um usuario, e o usuario precisa aprovar;

Só vai pro estado de transferencia (5) se o estado atual for 1 (normal) ou 2 (pause) e o usuario tem que ser diferente do dono.


*/

function validarDonoCorretoForPauseResume($credenciais, $ur_id) {
    $pdo = $credenciais["pdo"];
    $stmt = $pdo->prepare("select id from vw_rastreadores_dos_usuarios where id = :ur_id and dono_id = :dono_id and ur_status in (1, 2)");
    $stmt->execute(["ur_id" => $ur_id, "dono_id" => $credenciais["id"]]);

    return $stmt->rowCount() === 1;
}


function swtichBetweenPauseAndNormalTracking($credenciais, $ur_id, $pause) {

    if (!validarIdPositivo($ur_id)) {
        return ["error" => errorMessage("Id de rastreador de usuário inválido", $ur_id)];
    }

    if (!validarDonoCorretoForPauseResume($credenciais, $ur_id)) {
        return ["error" => errorMessage("Rastreador de usuário não encontrado, ou o usuário autenticado não é o dono, ou status atual não confere", $credenciais["id"] . " - " . $ur_id)];
    }

    try {
        $pdo = $credenciais["pdo"];
        $pdo->beginTransaction();

        $status_a_d = $pause ? [1,2] : [2,1];   

        $stmt = $pdo->prepare("update usuario_rastreador set status = :new_status where id = :id and status = :old_status");
        $stmt->execute(["id" => $ur_id, "new_status" => $status_a_d[1], "old_status" => $status_a_d[0]]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return ["error" => errorMessage("Rastreador de usuário não encontrado ou status atual não confere ao atualizar", $credenciais["id"] . " - " . $ur_id)];
        }

        $pdo->commit();
        return ["success" => true];

    } catch (Exception $e) {
        if ($pdo?->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => errorMessage("Erro ao atualizar status do rastreador de usuário", $e->getMessage())];
    }

}

function pauseTrackingForOuvinte($credenciais, $ur_id) {
    return swtichBetweenPauseAndNormalTracking($credenciais, $ur_id, true);
}

function resumeTrackingForOuvinte($credenciais, $ur_id) {
    return swtichBetweenPauseAndNormalTracking($credenciais, $ur_id, false);
}