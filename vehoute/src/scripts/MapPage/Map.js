import { ref } from "vue";
import { fetch_ } from "../fetcher";
import MapController from "./MapController";
import router from "../../router";
import { getDateWithOffset } from "../utils";
import Gatekeeper from "../GateKeeper";
import MainWS from "../Websockets/Main_Websocket";

export default class Map {

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
        if (tracker) {
            Map._actual_locs_reference.value = tracker.localizacoes;
        } else {
            Map._actual_locs_reference.value = null;
            console.error("Tracker not found for ID:", tracker_id);
        }
    }
    
    static get_localizacoes(id) {
        const tracker = Map.trackers.find(t => t.id === id);
        return tracker ? tracker.localizacoes : [];
    }

    static async fetchTrackers() {
        const response = await fetch_('/usuario/rastreadores/RastreadoresUsuarios.php', [{ get: '%' }]);
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
                id: t.id,
                name: t.rastreador_nome,
                rastreador_id: t.rastreador_id,
                token_publico: t.token_publico,
                localizacoes: [],
            }));
            
            return true;
        } catch (error) {
            console.error("Error loading trackers:", error);
        }
        return false;
    }

    static insertLocationWithPublicToken(token_publico, location) {
        const tracker_id = Map.trackers.find(t => t.token_publico === token_publico)?.id;
        
        Map.insertLocation(tracker_id, location);
    }

    static insertLocation(tracker_id, location) {
        const tracker = Map.trackers.find(t => t.id === tracker_id);
        if (tracker) {

            const size = tracker.localizacoes.length;
            let nextTo = -1;
            for (let i = size - 1; i >= 0; i--) {
                if (location.id > tracker.localizacoes[i].id) {
                    nextTo = i + 1;
                    break;
                }
            }

            try {
                if (nextTo !== -1) {
                    tracker.localizacoes.splice(nextTo, 0, location);
                } else {
                    tracker.localizacoes.push(location);
                }
            } catch (error) {
                console.error("Error inserting location:", error);
            }

            //ordena apenas se necessario
            for (let i = 1; i < tracker.localizacoes.length; i++) {
                if (tracker.localizacoes[i - 1].id > tracker.localizacoes[i].id) {
                    tracker.localizacoes.sort((a, b) => a.id - b.id);
                    break;
                }
            }

        } else {
            console.error("Tracker not found for ID:", tracker_id);
        }
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
                const converted = locations.map(l => ({
                    id: l.l_id,
                    lat: l.l_lat,
                    lng: l.l_lng,
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
        const tracker = Map.trackers.find(t => t.id === tracker_id);
        if (tracker) {
            setTimeout(() => {
                MainWS.message(`wvu:${tracker.token_publico}`);
            }, 10);
            const res = await Gatekeeper.openFor(`watch_tracker_add_${tracker.token_publico}`, 2000);

            if (res !== true) {
                console.warn("Failed to open gate for tracker ID:", tracker_id);
                return;
            }

            Map._tracker_watching_id.value = tracker_id;
            console.log("Watching for tracker ID:", tracker_id);
        } else {
            Map.unsetWatchForTracker();
            console.error("Tracker not found for ID:", tracker_id);
        }
    }

    static async unsetWatchForTracker() {
        const tracker_id = Map._tracker_watching_id.value;
        const tracker = Map.trackers.find(t => t.id === tracker_id);
        if (tracker) {
            setTimeout(() => {
                MainWS.message(`wvr:${tracker.token_publico}`);
            }, 10);
            const res = await Gatekeeper.openFor(`watch_tracker_del_${tracker.token_publico}`, 2000);

            if (res !== true) {
                console.warn("Failed to open gate for tracker ID:", tracker_id);
                return;
            }

            Map._tracker_watching_id.value = null;
            console.log("Stopped watching for tracker ID:", tracker_id);
        } else {
            console.error("Tracker not found for ID:", tracker_id);
        }
    }

}