//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class SystemUserPermission {

    static async getPermissoesUsuario(agrupar_por, filter) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ permissoes: { agrupar_por, filter } }]);
        if (response && response.success) {
            return response.agrupamento;
        }
        console.error("Erro ao buscar permissões do usuário");
        return [];
    }
    
    static async getUsuariosDisponiveis(nome) {
        const response = await fetch_('/administrativo/usuarios/usuarios.php', [{ getSimple: nome }]);
        if (response && response.success) {
            return response.usuarios;
        }
        console.error("Erro ao buscar usuários disponíveis");
        return [];
    }

    static async getGruposDisponiveis(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ searchGrupos: nome }]);
        if (response && response.success) {
            return response.grupos;
        }
        console.error("Erro ao buscar grupos disponíveis");
        return [];
    }

    static async getPermissoesDisponiveis(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ searchPermissoes: nome }]);
        if (response && response.success) {
            return response.permissoes;
        }
        console.error("Erro ao buscar permissões disponíveis");
        return [];
    }

    static async addVinculoPermissao(usuarioOuGrupoID, is_user, negado, permissaoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ addVinculoPermissao: { usuarioOuGrupoID, is_user, negado, permissaoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao adicionar permissão");
        return false;
    }

    static async removeVinculoPermissao(vinculo_id, usuarioOuGrupoID, is_user, permissaoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ removeVinculoPermissao: { vinculo_id, usuarioOuGrupoID, is_user, permissaoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao remover permissão");
        return false;
    }
}