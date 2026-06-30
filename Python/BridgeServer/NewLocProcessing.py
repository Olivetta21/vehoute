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
    def processNewLocation():
        NewLocProcessing.log("[O] Iniciando processamento de novas localizações...")
        
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        threading.Thread(target=loop.run_forever, daemon=True).start()

        while True:
            try:
                new_loc = NewLocProcessing.FILA_NEW_LOC.get()
                
                if new_loc is None or not isinstance(new_loc, TrackerLocation):
                    NewLocProcessing.log("[!] Localização inválida recebida. Ignorando...")
                    continue
                            
                tracker_copy = None
                with WebSocketServer.TRACKERS_LOCK:
                    if new_loc.tracker not in WebSocketServer.TRACKERS:
                        continue
                    tracker_copy = WebSocketServer.TRACKERS[new_loc.tracker].copy()

                loc_message = FormatMessage.loc(new_loc.tracker, new_loc.lat, new_loc.lng)

                for cid in tracker_copy:
                    c_ws = None
                    with WebSocketServer.CLIENTS_LOCK:
                        if cid not in WebSocketServer.CLIENTS:
                            continue
                        c_ws = WebSocketServer.CLIENTS[cid].ws
                    
                    NewLocProcessing.log(f"[+] Enviando {new_loc.tracker} para cliente {cid}")
                    loop.call_soon_threadsafe(
                        asyncio.create_task,
                        NewLocProcessing.sendNewLocToCLients(loc_message, c_ws)
                    )
            except:
                ProgramStop.set("Process new location")