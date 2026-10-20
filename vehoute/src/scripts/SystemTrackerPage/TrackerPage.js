import { fetch_ } from "../fetcher";

export default class TrackerPage {

    static async getRastreadores(name) {
        const response = await fetch_('/administrativo/rastreadores/rastreadores.php', [{ get: name }]);
        if (response.success) {
            return response.rastreadores;
        }
        return [];
    }

    static async toggleAtivo(id) {
        return await fetch_('/administrativo/rastreadores/rastreadores.php', [{ toggle_ativo: id }]);
    }

    static async addRastreador(hardware, token, tokenPublico, senha, obs, status, donoId) {
        return await fetch_('/administrativo/rastreadores/rastreadores.php', [{ add_rastreador: {
            hardware,
            token,
            token_publico: tokenPublico,
            senha,
            obs,
            status,
            dono_id: donoId
        } }]);
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