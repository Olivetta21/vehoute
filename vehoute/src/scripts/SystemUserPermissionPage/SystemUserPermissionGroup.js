//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class SystemUserPermissionGroup {

    static async getGrupos(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ grupos: nome }]);
        if (response && response.success) {
            return response.grupos;
        }
        console.error("Erro ao buscar grupos do usuário");
        return [];
    }

    static async addGrupo(nome) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ addGrupo: nome }]);
        if (response && response.success) {
            return response.grupo;
        }
        console.error("Erro ao adicionar grupo do usuário");
        return null;
    }

    static async deleteGrupo(grupoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ deleteGrupo: grupoID }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao deletar grupo do usuário");
        return false;
    }

    static async addUsuarioNoGrupo(usuarioID, grupoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ addUsuarioNoGrupo: { usuarioID, grupoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao adicionar usuário no grupo");
        return false;
    }

    static async removerUsuarioDoGrupo(usuarioID, grupoID) {
        const response = await fetch_('/administrativo/permissoes/permissoes_usuario.php', [{ removerUsuarioDoGrupo: { usuarioID, grupoID } }]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao remover usuário do grupo");
        return false;
    }

    static async getUsuariosDisponiveis(nome) {
        const response = await fetch_('/administrativo/usuarios/usuarios.php', [{ getSimple: nome }]);
        if (response && response.success) {
            return response.usuarios;
        }
        console.error("Erro ao buscar usuários disponíveis");
        return [];
    }
}