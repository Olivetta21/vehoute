from DataBase import DataBase
from ProgramStop import ProgramStop
from FormatMessage import FormatMessage
from LogService import LogService
import threading
import asyncio
import websockets

class AuthError(Exception):
    pass

class WebSocketServer:
    CLIENTS = {}
    CLIENTS_LOCK = threading.Lock()
    TRACKERS = {}
    TRACKERS_LOCK = threading.Lock()
    
    def __init__(self, host='0.0.0.0', port=12344):
        self.host = host
        self.port = port

    @staticmethod
    def log(msg):
        LogService.log(f"[WSS]{msg}")


    @staticmethod
    def addClient(cli):
        with WebSocketServer.CLIENTS_LOCK:
            WebSocketServer.CLIENTS[cli.CID] = cli
            WebSocketServer.log(f"[+] Cliente {cli.CID} adicionado.")

    @staticmethod
    def removeClient(cli):
        WebSocketServer.removeWatchFromTracker(cli.CID, None)
        with WebSocketServer.CLIENTS_LOCK:
            if cli.CID in WebSocketServer.CLIENTS:
                del WebSocketServer.CLIENTS[cli.CID]
                WebSocketServer.log(f"[-] Cliente {cli.CID} removido.")

    @staticmethod
    def addWatchToTracker(cid, tracker_id):
        with WebSocketServer.TRACKERS_LOCK:
            if tracker_id not in WebSocketServer.TRACKERS:
                WebSocketServer.TRACKERS[tracker_id] = set()
            if cid not in WebSocketServer.TRACKERS[tracker_id]:
                WebSocketServer.TRACKERS[tracker_id].add(cid)
                WebSocketServer.log(f"[+] Cliente {cid} observando o rastreador {tracker_id}")
                return True
        return False

    @staticmethod
    def removeWatchFromTracker(cid, tracker_id):
        with WebSocketServer.TRACKERS_LOCK:
            to_delete = []
            result = False

            def remove(tracker):
                if cid in WebSocketServer.TRACKERS[tracker]:
                    WebSocketServer.log(f"[-] Cliente {cid} parou de observar o rastreador {tracker}")
                    WebSocketServer.TRACKERS[tracker].discard(cid)
                    if not WebSocketServer.TRACKERS[tracker]:  # Deletar se ficar vazio
                        to_delete.append(tracker)
                    return True
                return False

            if tracker_id is None: # Significa remover o cliente de todos os rastreadores
                for tracker_id in WebSocketServer.TRACKERS:
                    remove(tracker_id)
                result = True
            else:
                if tracker_id in WebSocketServer.TRACKERS:
                    result = remove(tracker_id)
            
            if to_delete:
                for tracker in to_delete:
                    del WebSocketServer.TRACKERS[tracker]
                    WebSocketServer.log(f"[-] O rastreador {tracker} sem clientes foi removido.")
            
            return result
    
    @staticmethod
    async def db_execute(query, params):
        try:
            with DataBase.get() as conn:
                with conn.cursor() as cur:
                    cur.execute(query, params)
                    if cur.rowcount > 0:
                        return cur.fetchall()
                    return None
        except:
            return None

    @staticmethod
    async def checkClientHasTracker(identidade, tracker_id):
        res = await WebSocketServer.db_execute(
            "SELECT 1 from usuario_rastreador where rastreador_id = %s and usuario_id = %s",
            (tracker_id, identidade)
        )
        return res and res[0][0] == 1

    class ConnClient:
        serial_id = 0
        serial_id_lock = threading.Lock()

        @staticmethod
        def getNextID():
            with WebSocketServer.ConnClient.serial_id_lock:
                WebSocketServer.ConnClient.serial_id += 1
                return WebSocketServer.ConnClient.serial_id

        async def getClientIdent(self, ws):
            async for message in ws:
                WebSocketServer.log(f"[@] Mensagem recebida de {ws.remote_address}: {message}")
                if message.startswith("ident:"):
                    user_token = message[6:].strip()
                    
                    
                    res = await WebSocketServer.db_execute(
                        "select id from getUsuarioByToken(%s)",
                        (user_token,)
                    )

                    WebSocketServer.log(f"[$][Ident from db] {res}")

                    if res:
                        return res[0][0]
                    else:
                        await ws.send(FormatMessage.ident(False))
                        return None
            return None

        def __init__(self, ws):
            self.ws = ws
            self.identidade = None
            self.CID = WebSocketServer.ConnClient.getNextID()

        async def __aenter__(self):
            WebSocketServer.log(f"[@] Cliente fazendo conexão: {self.ws.remote_address}")
            clientIdent_task = asyncio.create_task(self.getClientIdent(self.ws))
            try:
                self.identidade = await asyncio.wait_for(clientIdent_task, timeout=5.0)
            except asyncio.TimeoutError:
                self.identidade = None

            #todo: verifica no banco

            if not self.identidade:
                WebSocketServer.log(f"[!] Cliente {self.CID} falhou na autenticação.")
                raise AuthError("Cliente não autenticado.")

            await self.ws.send(FormatMessage.ident(True))
            WebSocketServer.addClient(self)

            return self

        async def __aexit__(self, exc_type, exc_val, exc_tb):
            await self.ws.close()
            WebSocketServer.removeClient(self)

    async def handleClient(self, websocket):
        try:
            async with WebSocketServer.ConnClient(websocket) as cli:
                # Requisições do cliente
                async for message in cli.ws:
                    if message.startswith("wta:"): # Adiciona Watch desse rastreador pro cliente
                        tracker_id = int(message[4:].strip())                        
                        if WebSocketServer.checkClientHasTracker(cli.identidade, tracker_id):
                            if WebSocketServer.addWatchToTracker(cli.CID, tracker_id):
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, True))
                            else:
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))
                        else:
                            await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))


                    elif message.startswith("wtr:"): # Remove Watch desse rastreador pro cliente
                        tracker_id = int(message[4:].strip())                        
                        if WebSocketServer.checkClientHasTracker(cli.identidade, tracker_id):
                            if WebSocketServer.removeWatchFromTracker(cli.CID, tracker_id):
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, False, True))
                            else:
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, False, False))
                        else:
                            await cli.ws.send(FormatMessage.watchTracker(tracker_id, False, False))


                    elif message.startswith("wtu:"): # Definir Watch apenas para esse rastreador pro cliente
                        tracker_id = int(message[4:].strip())                        
                        if WebSocketServer.checkClientHasTracker(cli.identidade, tracker_id):
                            WebSocketServer.removeWatchFromTracker(cli.CID, None)
                            if WebSocketServer.addWatchToTracker(cli.CID, tracker_id):
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, True))
                            else:
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))
                        else:
                            WebSocketServer.log(f"[$][Watch] {cli.identidade}: {tracker_id} - não autorizado")
                            await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))
                    else:
                        WebSocketServer.log(f"[$][client] {cli.identidade}: {message}")
        except AuthError as e:
            WebSocketServer.log(f"[!] {e}")
        except websockets.ConnectionClosed:
            WebSocketServer.log(f"[!] Conexão encerrada")
        except Exception as e:
            WebSocketServer.log(f"[Erro] {e}")


    async def start_(self):
        SRV = await websockets.serve(self.handleClient, self.host, self.port)
        WebSocketServer.log(f"[O] Servidor escutando em {self.host}:{self.port}...")

        await SRV.wait_closed()
        WebSocketServer.log("[-] Servidor encerrado.")
        ProgramStop.set("Servidor WSS encerrado.")

    def start(self):
        asyncio.run(self.start_())

