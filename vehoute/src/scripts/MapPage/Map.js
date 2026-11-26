import { ref } from "vue";
import { fetch_ } from "../fetcher";
import MapController from "./MapController";
import router from "../../router";
import { getDateWithOffset } from "../utils";
import Gatekeeper from "../GateKeeper";
import MainWS from "../Websockets/Main_Websocket";

export default class Map {
    static already_confirmed_fullscreen = false;

    static _tracker_id_to_start_viewing = null;
    static _tracker_selected_id = ref(null);
    static _tracker_watching_id = ref(null);
    static _date_filter = ref({ start: getDateWithOffset(-30), end: getDateWithOffset(0) });

    static async before_enter() {
        await Map.loadTrackers();
        Map._tracker_selected_id.value = Map.tracker_id_to_start_viewing;
    }

    static after_leave() {
        Map.unsetWatchForTracker();
        Map._tracker_id_to_start_viewing = null;
        Map.trackers = [];
        Map._actual_locs_reference.value = null;
        Map._tracker_selected_id.value = null;
        MapController.clearTrack();
    }


    static enterWithTracker(tracker_id) {
        Map._tracker_id_to_start_viewing = tracker_id;
        router.push({ name: 'map' });
    }
    static get tracker_id_to_start_viewing() {
        const id = Map._tracker_id_to_start_viewing;
        Map._tracker_id_to_start_viewing = null;
        return id;
    }



    static _trackers = ref([
        /*{
            id: 10, name: "Tracker 1", 
            localizacoes: [
                { lat: -23.55052, lng: -46.63331, id: 1 , l_data: '2024-10-20 10:00:00'}
            ]
        }*/
    ]);

    static get trackers() {
        return Map._trackers.value;
    }
    static set trackers(newTrackers) {
        Map._trackers.value = newTrackers;
    }

    static _actual_locs_reference = ref(null);
    static setActualLocsReference(tracker_id) {
        const tracker = Map.trackers.find(t => t.id === tracker_id);
        Map._actual_locs_reference.value = tracker ? tracker.localizacoes : null;
    }

    static get_localizacoes(id) {
        const tracker = Map.trackers.find(t => t.id === id);
        return tracker ? tracker.localizacoes : [];
    }

    static async fetchTrackers() {
        const response = await fetch_('/usuario/rastreadores/rastreadoresusuarios.php', [{ get: '%' }]);
        if (response.success) {
            return response.rastreadores;
        }
        console.error("Erro ao buscar rastreadores do usuário");
        return [];
    }

    static async loadTrackers() {
        try {
            const trackers = await Map.fetchTrackers();
            Map.trackers = trackers.map(t => ({
                id: t.ur_id,
                name: t.ur_nome,
                rastreador_id: t.r_id,
                token_publico: t.r_token_publico,
                localizacoes: [],
            }));

            return true;
        } catch (error) {
            console.error("Error loading trackers:", error);
        }
        return false;
    }

    static insertLocationWithRealTrackerID(id, location) {
        const tracker_id = Map.trackers.find(t => Number(t.rastreador_id) === Number(id))?.id;
        if (tracker_id !== undefined) Map.insertLocation(tracker_id, location);
    }

    /** Insere mantendo a ordem por id (busca binária, O(log n)) e ignorando repetidos. */
    static insertLocation(tracker_id, location) {
        const tracker = Map.trackers.find(t => t.id === tracker_id);
        if (!tracker) return;

        const nova = { ...location, id: Number(location.id), lat: Number(location.lat), lng: Number(location.lng) };
        const lista = tracker.localizacoes;
        let inicio = 0;
        let fim = lista.length;
        while (inicio < fim) {
            const meio = (inicio + fim) >> 1;
            if (lista[meio].id < nova.id) inicio = meio + 1;
            else fim = meio;
        }
        if (lista[inicio]?.id === nova.id) return;
        lista.splice(inicio, 0, nova);
    }


    static async fetchLocations(rastreador_id) {
        const response = await fetch_('/usuario/rastreadores/localizacoes/localizacoesdosrastreadores.php', [{ rastreador_id }]);
        if (response.success) {
            return response.localizacoes;
        }
        console.error("Erro ao buscar localizações do rastreador");
        return [];
    }

    static async loadLocations(ur_id) {
        try {
            const tracker = Map.trackers.find(t => t.id === ur_id);
            if (tracker) {
                const locations = await Map.fetchLocations(tracker.rastreador_id);
                locations.sort((a, b) => Number(a.l_id) - Number(b.l_id));
                const converted = locations.map(l => ({
                    id: Number(l.l_id),
                    lat: Number(l.l_lat),
                    lng: Number(l.l_lng),
                    l_data: l.l_data,
                }));

                tracker.localizacoes.splice(0, tracker.localizacoes.length, ...converted);

                let last = tracker.localizacoes[tracker.localizacoes.length - 1]?.l_data || null;
                if (last) {
                    last = last.split('T')[0].split(' ')[0]
                    if (last < Map._date_filter.value.start) {
                        Map._date_filter.value.start = last;
                    } else if (last > Map._date_filter.value.end) {
                        Map._date_filter.value.end = last;
                    }
                }

                return true;
            }
            console.error("Tracker not found for ID:", ur_id);
        } catch (error) {
            console.error("Error loading locations:", error);
        }
        return false;
    }

    static async setWatchForTracker(tracker_id) {
        if (Map._tracker_watching_id.value !== null && Map._tracker_watching_id.value !== tracker_id) {
            await Map.unsetWatchForTracker();
        }
        const tracker = Map.trackers.find(t => t.id === tracker_id);
        if (!tracker || Map._tracker_watching_id.value === tracker_id) return;

        const gate = Gatekeeper.openFor(`watch_tracker_add_${tracker.rastreador_id}`, 2000);
        MainWS.message(`wtu:${tracker.rastreador_id}`);

        if (await gate !== true) {
            console.warn("Sem confirmação para acompanhar o rastreador:", tracker_id);
            return;
        }
        Map._tracker_watching_id.value = tracker_id;
    }

    static async unsetWatchForTracker() {
        const tracker = Map.trackers.find(t => t.id === Map._tracker_watching_id.value);
        Map._tracker_watching_id.value = null;
        if (!tracker) return;

        const gate = Gatekeeper.openFor(`watch_tracker_del_${tracker.rastreador_id}`, 2000);
        MainWS.message(`wtr:${tracker.rastreador_id}`);

        if (await gate !== true) {
            console.warn("Sem confirmação para deixar de acompanhar o rastreador:", tracker.id);
        }
    }

    /** Reenvia o pedido de acompanhamento após o WebSocket reconectar. */
    static rewatch() {
        const tracker = Map.trackers.find(t => t.id === Map._tracker_watching_id.value);
        if (tracker) MainWS.message(`wtu:${tracker.rastreador_id}`);
    }

}
