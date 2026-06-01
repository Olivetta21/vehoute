import { fetch_ } from "../fetcher";


export default class SystemUser {

    static async getUsuarios(name) {
        const response = await fetch_('/administrativo/usuarios/usuarios.php', [{ get: name }]);
        if (response.success) {
            return response.usuarios;
        }
        return [];
    }

    static async toggleAtivo(id) {
        return await fetch_('/administrativo/usuarios/usuarios.php', [{ toggle_ativo: id }]);
    }

    static async toggleAdm(id) {
        return await fetch_('/administrativo/usuarios/usuarios.php', [{ toggle_adm: id }]);
    }

    static async addUsuario(nome, email, identidade, tipoIdent, adm = false) {
        return await fetch_('/administrativo/usuarios/usuarios.php', [{ add_usuario: { nome, email, identidade, tipo_ident: tipoIdent, adm } }]);
    }

}