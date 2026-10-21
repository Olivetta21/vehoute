//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class SystemTrackerPermissionGroup {

    static async getGrupos(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ grupos: nome }]);
        if (response && response.success) {
            return response.grupos ?? [];
        }
        console.error("Erro ao buscar grupos do rastreador");
        return [];
    }

    static async addGrupo(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ addGrupo: nome }]);
        if (response && response.success) {
            return response.grupo;
        }
        console.error("Erro ao adicionar grupo do rastreador");
        return null;
    }

    static async deleteGrupo(grupoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ deleteGrupo: grupoID }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao deletar grupo do rastreador");
        return false;
    }

    static async addRastreadorNoGrupo(rastreadorID, grupoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ addRastreadorNoGrupo: { rastreadorID, grupoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao adicionar rastreador no grupo");
        return false;
    }

    static async removerRastreadorDoGrupo(rastreadorID, grupoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ removerRastreadorDoGrupo: { rastreadorID, grupoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao remover rastreador do grupo");
        return false;
    }

    static async getRastreadoresDisponiveis(nome) {
        const response = await fetch_('/administrativo/rastreadores/rastreadores.php', [{ getSimple: nome }]);
        if (response && response.success) {
            return response.rastreadores;
        }
        console.error("Erro ao buscar rastreadores disponíveis");
        return [];
    }
}