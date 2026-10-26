//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class SystemLog {
    
    static async getLogs(titulo, mensagem, ip, data_inicio, data_fim) {
        const response = await fetch_('/administrativo/logseauditoria/logs.php', [{ get: { titulo, mensagem, ip, data_inicio, data_fim } }]);
        if (response.success) {
            return response.logs;
        }
        return [];
    }

    static async getSimpleTitulos(filter) {
        const response = await fetch_('/administrativo/logseauditoria/logs.php', [{ getSimpleTitulos: filter }]);
        if (response.success) {
            return response.titulos;
        }
        return [];
    }

}