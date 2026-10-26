//import router from "../../router";
import { fetch_ } from "../fetcher";


export default class SystemAudit {
    
    static async getAuditorias(tabela, filtro, data_inicio, data_fim, sucesso) {
        const response = await fetch_('/administrativo/logseauditoria/auditoria.php', [{ get: { tabela, filtro, data_inicio, data_fim, sucesso } }]);
        if (response.success) {
            return response.auditorias;
        }
        return [];
    }

    static async getSimpleTabelas(filter) {
        const response = await fetch_('/administrativo/logseauditoria/auditoria.php', [{ getSimpleTabelas: filter }]);
        if (response.success) {
            return response.tabelas;
        }
        return [];
    }

}