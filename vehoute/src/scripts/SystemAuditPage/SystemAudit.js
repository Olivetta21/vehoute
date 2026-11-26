//import router from "../../router";
import { fetch_ } from "../fetcher";


export default class SystemAudit {
    
    static async getAuditorias(tabela, filtro, data_inicio, data_fim, sucesso, offset = 0) {
        const response = await fetch_('/administrativo/logseauditoria/auditoria.php', [{ get: { tabela, filtro, data_inicio, data_fim, sucesso, offset } }]);
        if (response.success) {
            return { itens: response.auditorias, tem_mais: Boolean(response.tem_mais) };
        }
        return null;
    }

    static async getSimpleTabelas(filter) {
        const response = await fetch_('/administrativo/logseauditoria/auditoria.php', [{ getSimpleTabelas: filter }]);
        if (response.success) {
            return response.tabelas;
        }
        return [];
    }

}