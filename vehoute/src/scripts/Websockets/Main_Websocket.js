import Gatekeeper from "../GateKeeper";
import Usuario from "../LoginPage/Usuario";
import MapPagina from "../MapPage/Map";
import { avisarSessaoExpirada, encerrarSessaoExpirada } from "../session";

const TOKEN_INVALIDO = 4001;

export default class MainWS {
    static _ws = null;
    static _manter_conectado = false;
    static _tentativas = 0;
    static _timer = null;
    static _identificado = false;
    static _aguardando = [];
    static _pedido = 0;

    static genWS() {
        const protocolo = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const ws = new WebSocket(`${protocolo}//${window.location.host}/ws/`);
        MainWS._ws = ws;
        ws.onopen = () => MainWS.on_open();
        ws.onmessage = (e) => { if (MainWS._ws === ws) MainWS.on_message(e); };
        ws.onclose = (e) => MainWS.on_close(ws, e);
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
    }

    static on_identified() {
        MainWS._identificado = true;
        MainWS._aguardando.splice(0).forEach(resolver => resolver(true));
        // Após uma reconexão, volta a acompanhar o rastreador que estava aberto no mapa
        MapPagina.rewatch();
    }

    /** O token não vale mais: para de reconectar e age como o fetch. */
    static on_session_expired() {
        MainWS.disconnect();
        avisarSessaoExpirada();
        encerrarSessaoExpirada();
    }

    /** Resolve true quando a conexão estiver identificada (ou false após o tempo limite). */
    static whenReady(tempo_maximo_ms = 5000) {
        if (MainWS._identificado) return Promise.resolve(true);
        return new Promise((resolve) => {
            MainWS._aguardando.push(resolve);
            setTimeout(() => resolve(false), tempo_maximo_ms);
        });
    }

    /** Conjunto dos ids (entre os informados) que estão conectados, ou null se não foi possível saber. */
    static async connectedTrackers(ids) {
        if (!ids.length) return new Set();
        if (!(await MainWS.whenReady())) return null;
        const pedido = ++MainWS._pedido;
        const resposta = Gatekeeper.openFor(`connected_${pedido}`, 4000);
        MainWS.message(`con:${pedido}:${ids.join(',')}`);
        const conectados = await resposta;
        return conectados ? new Set(conectados.map(Number)) : null;
    }

    static on_message(evento) {
        let message;
        try {
            message = JSON.parse(evento.data);
        } catch {
            console.warn("Mensagem inválida recebida do WebSocket");
            return;
        }

        if (message.t === "ident") {
            if (message.r === true) MainWS.on_identified();
            else if (message.reason === "invalid_token") MainWS.on_session_expired();
        }
        else if (message.t === "expired") {
            MainWS.on_session_expired();
        }
        else if (message.t === "con") {
            Gatekeeper.openGate(`connected_${message.req}`, message.ids);
        }
        //{"t": "wTrk", "tk": "23", "a": true, "r": true}
        else if (message.t === "wTrk" && message.r === true) {
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

    static on_close(ws, evento) {
        if (MainWS._ws !== ws) return;
        MainWS._ws = null;
        MainWS._identificado = false;
        if (evento?.code === TOKEN_INVALIDO) {
            MainWS.on_session_expired();
            return;
        }
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
        MainWS._identificado = false;
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
