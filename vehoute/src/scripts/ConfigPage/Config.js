//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class Config {
    
    static async getConfigs() {
        const response = await fetch_('/usuario/configuracao/configuracao.php', [{ getConfigs: true }]);
        if (response && response.success) {
            return response.configs;
        }
        console.error("Erro ao buscar configs do usuário");
        return [];
    }

    static async atualizarConfigUsuario(config_id, variavel) {
        const response = await fetch_('/usuario/configuracao/configuracao.php', [{ update: { config_id, variavel } }]);
        if (response && response.success) {
            return response.config;
        }
        console.error("Erro ao atualizar config do usuário");
        return null;
    }
}