from DataBase import DataBase
from FormatMessage import FormatMessage
from TrackerLocation import TrackerLocation
from WebSocketServer import WebSocketServer
from LogService import LogService
import queue
import asyncio
import math

class NewLocProcessing:
    FILA_NEW_LOC = queue.Queue()

    @staticmethod
    def log(msg):
        LogService.log(f"[NewLocProcessing]{msg}")

    @staticmethod
    async def sendNewLocToCLients(loc_message, cli_ws):
        try:
            await cli_ws.send(loc_message)
        except:
            NewLocProcessing.log(f"[!] Tentou se comunicar com {cli_ws.remote_address} mas a conexão estava encerrada.")

    @staticmethod
    def isValid(lat, lng):
        return (
            isinstance(lat, (int, float)) and isinstance(lng, (int, float))
            and math.isfinite(lat) and math.isfinite(lng)
            and -90 <= lat <= 90 and -180 <= lng <= 180
        )

    @staticmethod
    def trackerCanSendLoc(tracker_id):
        return DataBase.tracker_has_permission(tracker_id, 2)

    @staticmethod
    def processNewLocation():
        NewLocProcessing.log("[O] Iniciando processamento de novas localizações...")
        
        while True:
            try:
                new_loc = NewLocProcessing.FILA_NEW_LOC.get()

                if not isinstance(new_loc, TrackerLocation) or not NewLocProcessing.isValid(new_loc.lat, new_loc.lng):
                    NewLocProcessing.log("[!] Localização inválida recebida. Ignorando...")
                    continue

                new_loc.lat = float(round(new_loc.lat, 6))
                new_loc.lng = float(round(new_loc.lng, 6))

                NewLocProcessing.log(f"[<] Recebido loc do {new_loc.tracker} LAT:{new_loc.lat} LNG:{new_loc.lng}")
                
                if not NewLocProcessing.trackerCanSendLoc(new_loc.tracker):
                    NewLocProcessing.log("[!] Tracker não pode enviar loc. Ignorando...")
                    continue

                result = None
                with DataBase.get() as conn:
                    with conn.cursor() as cur:
                        cur.execute(
                            "select insereLocalizacao(%s, %s::numeric, %s::numeric)",
                            (new_loc.tracker, new_loc.lat, new_loc.lng)
                        )
                        result = cur.fetchone()
                
                if not result or result[0] is None or result[0] < 1:
                    NewLocProcessing.log(f"[!] Localização ignorada: {new_loc.tracker} Lat: {new_loc.lat} Lng: {new_loc.lng}")
                    continue
                
                tracker_copy = None
                with WebSocketServer.TRACKERS_LOCK:
                    if new_loc.tracker not in WebSocketServer.TRACKERS:
                        continue
                    tracker_copy = WebSocketServer.TRACKERS[new_loc.tracker].copy()

                loc_recuperada = None
                formatted_loc = None
                for cid in tracker_copy:
                    client = None
                    with WebSocketServer.CLIENTS_LOCK:
                        if cid not in WebSocketServer.CLIENTS:
                            continue
                        client = WebSocketServer.CLIENTS[cid]

                    if not DataBase.user_has_permission(
                        client.identidade,
                        WebSocketServer.USER_PERMISSION_REALTIME_TRACKING,
                    ):
                        NewLocProcessing.log(
                            f"[!] Cliente({client.CID}) sem permissão de rastreio em tempo real. Ignorando..."
                        )
                        continue
                    
                    with DataBase.get() as conn:
                        with conn.cursor() as cur:
                            cur.execute(
                                "select l_id, l_data from getLocDoRastreadorParaOuvinte(%s, %s, %s) where is_oculto = false and l_id = %s",
                                (new_loc.tracker, client.identidade, "tpreal", result[0],)
                            )
                            loc_recuperada = cur.fetchone()
                    if not loc_recuperada:
                        NewLocProcessing.log(f"[!] Não foi possível recuperar a localização para o cliente({client.CID}) {client.identidade}. Ignorando...")
                        continue
                    if not formatted_loc:
                        formatted_loc = FormatMessage.loc(new_loc.tracker, new_loc.lat, new_loc.lng, loc_recuperada[0], loc_recuperada[1])
                        
                    NewLocProcessing.log(f"[>] Enviando loc do {new_loc.tracker} para o cliente({client.CID}) {client.identidade}")
                    if WebSocketServer.LOOP is not None:
                        asyncio.run_coroutine_threadsafe(
                            NewLocProcessing.sendNewLocToCLients(formatted_loc, client.ws),
                            WebSocketServer.LOOP,
                        )
            except Exception as e:
                # uma falha pontual (ex.: banco oscilando) não derruba o servidor inteiro
                NewLocProcessing.log(f"[Erro] Falha ao processar localização: {e}")