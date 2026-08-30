from DataBase import DataBase
from FormatMessage import FormatMessage
from TrackerLocation import TrackerLocation
from WebSocketServer import WebSocketServer
from ProgramStop import ProgramStop
from LogService import LogService
import queue
import asyncio
import threading

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
    def trackerCanSendLoc(tracker_id):
        return True

    @staticmethod
    def processNewLocation():
        NewLocProcessing.log("[O] Iniciando processamento de novas localizações...")
        
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        threading.Thread(target=loop.run_forever, daemon=True).start()

        while True:
            try:
                new_loc = NewLocProcessing.FILA_NEW_LOC.get()

                new_loc.lat = float(round(new_loc.lat, 6))
                new_loc.lng = float(round(new_loc.lng, 6))
                
                if new_loc is None or not isinstance(new_loc, TrackerLocation):
                    NewLocProcessing.log("[!] Localização inválida recebida. Ignorando...")
                    continue

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

                loc_message = FormatMessage.loc(new_loc.tracker, new_loc.lat, new_loc.lng)

                for cid in tracker_copy:
                    client = None
                    with WebSocketServer.CLIENTS_LOCK:
                        if cid not in WebSocketServer.CLIENTS:
                            continue
                        client = WebSocketServer.CLIENTS[cid]
                    
                    NewLocProcessing.log(f"[>] Enviando loc do {new_loc.tracker} para o cliente({client.CID}) {client.identidade}")
                    loop.call_soon_threadsafe(
                        asyncio.create_task,
                        NewLocProcessing.sendNewLocToCLients(loc_message, client.ws)
                    )
            except Exception as e:
                ProgramStop.set("Process new location error: " + str(e))