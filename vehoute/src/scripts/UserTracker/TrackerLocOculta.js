import router from "../../router";
import { fetch_ } from "../fetcher";

export default class TrackerLocOculta {
    static tracker = null;

    static before_leave() {
        TrackerLocOculta.tracker = null;
    }

    static before_enter() {
        if (!TrackerLocOculta.tracker) {
            router.push({ name: 'owntracker' });
        }
    }

    static openPage(tracker) {
        TrackerLocOculta.tracker = tracker;
        router.push({ name: 'trackerlococulta' });
    }

    static async getLocsOcultas(search) {
        /* expected:
            [
                {
                    "id": 1,
                    "id_inicial": 1,
                    "id_final": 10,
                    "data_inicial": "2024-06-01 00:00:00",
                    "data_final": "2024-06-01 23:59:59",
                    "rastreador_id": 1,
                    "identificacao": "Ocultar dia X",
                    "novos_ouvintes": true,
                    "ouvintes": [
                        {
                            "usuario_rastreador_id": 1,
                            "nome": "Fulano de Tal"
                        }
                    ]
                }
            ]
        */  
        const response = await fetch_('/usuario/rastreadores/lococulta/lococultadosrastreadores.php', [{ get: { search, rastreador_id: TrackerLocOculta.tracker.r_id }}]);
        
        if (response && response.success) {
            return response.localizacoes_ocultas;
        }
        console.error("Erro ao buscar locs ocultas");
        return [];
    }

    static async criarLocOculta(id_inicial, id_final, data_inicial, data_final, identificacao) {
        const response = await fetch_('/usuario/rastreadores/lococulta/lococultadosrastreadores.php', [{ create: { id_inicial, id_final, data_inicial, data_final, identificacao, rastreador_id: TrackerLocOculta.tracker.r_id }}]);
        if (response && response.success) {
            return response.localizacao_oculta;
        }
        console.error("Erro ao criar localização oculta");
        return null;
    }

    static async atualizarLocOculta(lococulta_id, array_caracteristicas_novosvalores) {
        const response = await fetch_('/usuario/rastreadores/lococulta/lococultadosrastreadores.php', [{ update: { lococulta_id, array_caracteristicas_novosvalores, rastreador_id: TrackerLocOculta.tracker.r_id }}]);
        if (response && response.success) {
            return response.localizacao_oculta;
        }
        console.error("Erro ao atualizar localização oculta");
        return null;
    }

    static async deletarLocOculta(lococulta_ids) {
        const response = await fetch_('/usuario/rastreadores/lococulta/lococultadosrastreadores.php', [{ delete: { lococulta_ids, rastreador_id: TrackerLocOculta.tracker.r_id }}]);
        if (response && response.success) {
            return true;
        }
        console.error("Erro ao deletar localização oculta");
        return false;
    }

    static async getOuvintesDisponiveis() {
        const response = await fetch_('/usuario/rastreadores/lococulta/lococultadosrastreadores.php', [{ get_ouvintes: TrackerLocOculta.tracker.r_id }]);
        if (response && response.success) {
            return response.ouvintes;
        }
        console.error("Erro ao buscar ouvintes disponíveis");
        return [];
    }

    static async setOuvintes(lococulta_ids, usuario_rastreador_ids) {
        const response = await fetch_('/usuario/rastreadores/lococulta/lococultadosrastreadores.php', [{ set_ouvintes: { lococulta_ids, usuario_rastreador_ids, rastreador_id: TrackerLocOculta.tracker.r_id }}]);
        if (response && response.success) {
            return response.ouvintes;
        }
        console.error("Erro ao definir ouvintes para localizações ocultas");
        return false;
    }

}