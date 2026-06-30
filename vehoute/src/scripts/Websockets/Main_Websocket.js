import Gatekeeper from "../GateKeeper";
import Usuario from "../LoginPage/Usuario";
import MapPagina from "../MapPage/Map";
import { getDateWithOffset } from "../utils";

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
        //{"t": "loc", "tk": "token_publico123", "lat": -22.81020736694336, "lng": -51.085899353027344}
        else if (message.t === "loc") {
            MapPagina.insertLocationWithPublicToken(message.tk, { id: new Date().getTime(), lat: message.lat, lng: message.lng, l_data: getDateWithOffset(-4) + ' 15:00:00' });
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