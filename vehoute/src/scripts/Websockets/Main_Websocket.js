import Gatekeeper from "../GateKeeper";
import Usuario from "../LoginPage/Usuario";
import MapPagina from "../MapPage/Map";

export default class MainWS {
    static _ws = null;

    static genWS() {
        const ws_url = "/ws/";
        MainWS._ws = new WebSocket(ws_url);
        MainWS._ws.onopen = () => MainWS.on_open();
        MainWS._ws.onmessage = (e) => MainWS.on_message(e);
        MainWS._ws.onclose = (e) => MainWS.on_close(e);
        MainWS._ws.onerror = (e) => MainWS.on_error(e);
    }

    static connect(access_token) {
        if (MainWS._ws) {
            console.log("WebSocket já conectado.");
            return;
        }
        console.log("tentando se conectar ao WebSocket com token:", access_token);

        MainWS.genWS();
    }


    static on_open() {
        // ENVIA IDENTIFICAÇÃO EM JSON:
        MainWS._ws.send("ident:" + Usuario.access_token);
        console.log("WebSocket conectado com sucesso.");
    }

    static on_message(evento) {
        console.log("Mensagem recebida do WebSocket:", evento.data);

        //{"t": "wTrk", "tk": "23", "a": true, "r": true}
        const message = JSON.parse(evento.data);
        if (message.t === "wTrk" && message.r === true) {
            const gate_token = `watch_tracker_${message.a ? 'add' : 'del'}_${message.tk}`;
            Gatekeeper.openGate(gate_token, true);
        }
        //{"t": "loc", "tk": 18, "lat": -22.25346, "lng": -54.813145, "id": 777, "date": "2026-08-29 22:56:57.929631"}	
        else if (message.t === "loc") {
            MapPagina.insertLocationWithRealTrackerID(message.tk, { id: message.id, lat: message.lat, lng: message.lng, l_data: message.date });
        }
    }

    static on_error(error) {
        console.error("Erro no WebSocket:", error);
    }

    static on_close(event) {
        console.log("WebSocket fechado:", event);
        MainWS._ws = null;
    }

    static disconnect() {
        if (MainWS._ws) {
            MainWS._ws.close();
        } else {
            console.log("Nenhum WebSocket conectado para desconectar.");
        }
    }

    static message(msg) {
        if (MainWS._ws && MainWS._ws.readyState === WebSocket.OPEN) {
            MainWS._ws.send(msg);
            console.log("Mensagem enviada para o WebSocket:", msg);
        } else {
            console.warn("WebSocket não está aberto. Não foi possível enviar a mensagem:", msg);
        }
    }
}