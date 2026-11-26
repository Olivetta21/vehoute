import Gatekeeper from "../GateKeeper";
import Usuario from "../LoginPage/Usuario";
import MapPagina from "../MapPage/Map";

export default class MainWS {
    static _ws = null;
    static _manter_conectado = false;
    static _tentativas = 0;
    static _timer = null;

    static genWS() {
        const protocolo = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const ws = new WebSocket(`${protocolo}//${window.location.host}/ws/`);
        MainWS._ws = ws;
        ws.onopen = () => MainWS.on_open();
        ws.onmessage = (e) => MainWS.on_message(e);
        ws.onclose = () => MainWS.on_close(ws);
        ws.onerror = (e) => MainWS.on_error(e);
    }

    static connect() {
        MainWS._manter_conectado = true;
        if (MainWS._ws) return;
        clearTimeout(MainWS._timer);
        MainWS.genWS();
    }

    static on_open() {
        MainWS._tentativas = 0;
        // ENVIA IDENTIFICAÇÃO EM JSON:
        MainWS._ws.send("ident:" + Usuario.access_token);
        // Após uma reconexão, volta a acompanhar o rastreador que estava aberto no mapa
        MapPagina.rewatch();
    }

    static on_message(evento) {
        let message;
        try {
            message = JSON.parse(evento.data);
        } catch {
            console.warn("Mensagem inválida recebida do WebSocket");
            return;
        }

        //{"t": "wTrk", "tk": "23", "a": true, "r": true}
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

    static on_close(ws) {
        if (MainWS._ws !== ws) return;
        MainWS._ws = null;
        if (!MainWS._manter_conectado || !Usuario.access_token) return;

        // Reconexão com espera exponencial (1s, 2s, 4s ... até 30s)
        const espera = Math.min(30000, 1000 * 2 ** MainWS._tentativas++);
        MainWS._timer = setTimeout(() => {
            if (MainWS._manter_conectado && !MainWS._ws) MainWS.genWS();
        }, espera);
    }

    static disconnect() {
        MainWS._manter_conectado = false;
        MainWS._tentativas = 0;
        clearTimeout(MainWS._timer);
        const ws = MainWS._ws;
        MainWS._ws = null;
        ws?.close();
    }

    static message(msg) {
        if (MainWS._ws && MainWS._ws.readyState === WebSocket.OPEN) {
            MainWS._ws.send(msg);
        } else {
            console.warn("WebSocket não está aberto; mensagem não enviada.");
        }
    }
}
