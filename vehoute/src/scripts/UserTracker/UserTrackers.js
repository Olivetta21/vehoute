import { fetch_ } from "../fetcher";

export default class UserTrackers {

    static async getRastreadores(nome) {
        if (!nome?.trim()) {
            nome = '%';
        }
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ get: nome }]);
        if (response.success) {
            return response.rastreadores;
        }
        console.error("Erro ao buscar rastreadores do usuário");
        return [];
    }

    static async checkRastreador(token, senha) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ check_rastreador: { token, senha } }]);
        if (response.success) {
            return response.rastreador;
        }
        console.error("Erro ao verificar rastreador");
        return null;
    }

    static async addRastreador(rastreador_id, dono_id, token, senha, status, nome) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ add_rastreador: { rastreador_id, dono_id, token, senha, status, nome } }]);
        if (response.success) {
            return response.usuario_rastreador;
        }
        console.error("Erro ao adicionar rastreador");
        return null;
    }

    static async donoEnviaPropostaDeNovoOuvinte(rastreador_id, nome, usuario_id_destino) {
        const response = await fetch_('/usuario/rastreadores/ouvintes/ouvintesdosrastreadores.php', [{ dono_envia_proposta_ouvinte: { rastreador_id, nome, usuario_id_destino } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao enviar proposta de ouvinte");
        return false;
    }

    static async aceitarPropostaOuvinte(ur_id) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ aceitar_proposta_ouvinte: ur_id }]);
        if (response && response.success) {
            return response.usuario_rastreador ?? true;
        }
        console.error("Erro ao aceitar proposta de ouvinte");
        return null;
    }

    static async recusarPropostaOuvinte(ur_id) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ recusar_proposta_ouvinte: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao recusar proposta de ouvinte");
        return false;
    }

    static async aceitarTransferencia(ur_id) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ aceitar_transferencia_posse: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao aceitar transferência de posse");
        return false;
    }

    static async recusarTransferencia(ur_id) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ recusar_transferencia_posse: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao recusar transferência de posse");
        return false;
    }

    static async excluirRastreador(ur_id) {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ excluir_rastreador: ur_id }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao excluir rastreador do ouvinte");
        return false;
    }
}