//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class SystemTrackerPermission {

    static async getPermissoesTracker(agrupar_por, filter) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ permissoes: { agrupar_por, filter } }]);
        if (response && response.success) {
            return response.agrupamento;
        }
        console.error("Erro ao buscar permissões do rastreador");
        return [];
    }
    
    static async getRastreadoresDisponiveis(nome) {
        const response = await fetch_('/administrativo/rastreadores/rastreadores.php', [{ getSimple: nome }]);
        if (response && response.success) {
            return response.rastreadores;
        }
        console.error("Erro ao buscar rastreadores disponíveis");
        return [];
    }

    static async getGruposDisponiveis(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ searchGrupos: nome }]);
        if (response && response.success) {
            return response.grupos;
        }
        console.error("Erro ao buscar grupos disponíveis");
        return [];
    }

    static async getPermissoesDisponiveis(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ searchPermissoes: nome }]);
        if (response && response.success) {
            return response.permissoes;
        }
        console.error("Erro ao buscar permissões disponíveis");
        return [];
    }

    static async addVinculoPermissao(rastreadorOuGrupoID, is_tracker, negado, permissaoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ addVinculoPermissao: { rastreadorOuGrupoID, is_tracker, negado, permissaoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao adicionar permissão");
        return false;
    }

    static async removeVinculoPermissao(vinculo_id, rastreadorOuGrupoID, is_tracker, permissaoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_rastreador.php', [{ removeVinculoPermissao: { vinculo_id, rastreadorOuGrupoID, is_tracker, permissaoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao remover permissão");
        return false;
    }
}