import router from "../../router";
import { fetch_ } from "../fetcher";

export default class TrackerOuvintes {
    static tracker = null;

    static before_leave() {
        TrackerOuvintes.tracker = null;
    }

    static after_enter() {
        if (!TrackerOuvintes.tracker) {
            router.push({ name: 'owntracker' });
        }
    }

    static openPage(tracker) {
        TrackerOuvintes.tracker = tracker;
        router.push({ name: 'trackerouvintes' });
    }

    static async getOuvintes(name) {
        //select ur.id, ur.usuario_id, ur.rastreador_id, ur.status as ur_status, ur.loc_temporeal, ur.loc_salvos, 
        // u.nome as u_nome, u.email, u.telefone
        const result = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ get: { rastreador_id: TrackerOuvintes.tracker.rastreador_id, name_filter: name } }]);
        if (result.success) {
            return result.ouvintes;
        }
        console.error("Erro ao buscar ouvintes:");
        return [];
    }

    static async donoEnviaPropostaParaTransferirPosse(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ proposta_transferencia_de_posse: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao enviar proposta de transferência de posse");
        return null;
    }

    static async donoCancelaTransferenciaDePosse(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ cancelar_transferencia_de_posse: ur_id }]);
          if (response && response.success) {
            return true;
        }
        console.error("Erro ao cancelar proposta de transferência de posse");
        return null;
    }

    static async pauseTracking(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ pause_tracking: ur_id }]);
        if (response && response.success) {
            return response.usuario_rastreador ?? true;
        }
        console.error("Erro ao pausar rastreamento");
        return null;
    }

    static async resumeTracking(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ resume_tracking: ur_id }]);
        if (response && response.success) {
            return response.usuario_rastreador ?? true;
        }
        console.error("Erro ao resumir rastreamento");
        return null;
    }

    static async aceitarNovoOuvinte(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ aceitar_novo_ouvinte: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao aceitar novo ouvinte");
        return false;
    }

    static async recusarNovoOuvinte(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ recusar_novo_ouvinte: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao recusar novo ouvinte");
        return false;
    }

    static async deletarOuvinte(ur_id) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ deletar_ouvinte: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao deletar ouvinte");
        return false;
    }

}