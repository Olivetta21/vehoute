from DataBase import DataBase
from ProgramStop import ProgramStop
from FormatMessage import FormatMessage
from LogService import LogService
import threading
import asyncio
import websockets

class AuthError(Exception):
    pass

# Código de fechamento usado quando o token de sessão não vale mais
CLOSE_INVALID_TOKEN = 4001

class WebSocketServer:
    USER_PERMISSION_REALTIME_TRACKING = 9
    TOKEN_CHECK_INTERVAL_S = 60  # reconfere a sessão de quem já está conectado
    MAX_CONNECTION_QUERY = 500
    CLIENTS = {}
    CLIENTS_LOCK = threading.Lock()
    TRACKERS = {}
    TRACKERS_LOCK = threading.Lock()
    LOOP = None  # loop do servidor: todo envio aos clientes precisa acontecer nele
    
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
            WebSocketServer.log(f"[+] Cliente({cli.CID}) {cli.identidade} adicionado.")

    @staticmethod
    def removeClient(cli):
        WebSocketServer.removeWatchFromTracker(cli.CID, None)
        with WebSocketServer.CLIENTS_LOCK:
            if cli.CID in WebSocketServer.CLIENTS:
                del WebSocketServer.CLIENTS[cli.CID]
                WebSocketServer.log(f"[-] Cliente({cli.CID}) {cli.identidade} removido.")

    @staticmethod
    def addWatchToTracker(cid, tracker_id):
        with WebSocketServer.TRACKERS_LOCK:
            if tracker_id not in WebSocketServer.TRACKERS:
                WebSocketServer.TRACKERS[tracker_id] = set()
            if cid not in WebSocketServer.TRACKERS[tracker_id]:
                WebSocketServer.TRACKERS[tracker_id].add(cid)
                WebSocketServer.log(f"[+] Cliente({cid}) observando o rastreador {tracker_id}")
                return True
        return False

    @staticmethod
    def removeWatchFromTracker(cid, tracker_id):
        with WebSocketServer.TRACKERS_LOCK:
            to_delete = []
            result = False

            def remove(tracker):
                if cid in WebSocketServer.TRACKERS[tracker]:
                    WebSocketServer.log(f"[-] Cliente({cid}) parou de observar o rastreador {tracker}")
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
                    WebSocketServer.log(f"[-] O rastreador {tracker} foi removido pois ficou sem clientes.")
            
            return result
    
    @staticmethod
    def _db_query(query, params):
        with DataBase.get() as conn:
            with conn.cursor() as cur:
                cur.execute(query, params)
                if cur.rowcount > 0:
                    return cur.fetchall()
                return None

    @staticmethod
    async def db_execute(query, params):
        try:
            # em thread separada: uma consulta lenta não congela os demais clientes
            return await asyncio.to_thread(WebSocketServer._db_query, query, params)
        except Exception as e:
            WebSocketServer.log(f"[DB] Erro na consulta: {e}")
            return None

    @staticmethod
    async def checkClientHasTracker(identidade, tracker_id):
        res = await WebSocketServer.db_execute(
            """
            select 1
            from usuario_rastreador
            where rastreador_id = %s
              and usuario_id = %s
              and status = 1
              and ativo = true
              and exists (
                  select 1
                  from getPermissoesDoUsuario(%s)
                  where perm_id = %s and negado = false
              )
            """,
            (tracker_id, identidade, [identidade], WebSocketServer.USER_PERMISSION_REALTIME_TRACKING)
        )
        return res and res[0][0] == 1

    @staticmethod
    async def getTokenUser(token):
        """Retorna (consultou, usuario_id). consultou=False: falha do banco, não do token."""
        try:
            res = await asyncio.to_thread(
                WebSocketServer._db_query, "select id from getUsuarioByToken(%s)", (token,)
            )
            return True, (res[0][0] if res else None)
        except Exception as e:
            WebSocketServer.log(f"[DB] Erro ao validar sessão: {e}")
            return False, None

    @staticmethod
    async def connectedTrackers(identidade, tracker_ids):
        """Quais dos rastreadores pedidos estão conectados, entre os que o usuário pode ver."""
        # import local: TcpServer -> NewLocProcessing -> WebSocketServer já formam essa cadeia
        from TcpServer import TcpServer
        conectados = TcpServer.connectedTrackerIds().intersection(tracker_ids)
        if not conectados:
            return []
        res = await WebSocketServer.db_execute(
            """
            select r.id
            from rastreador r
            where r.id = any(%s)
              and r.ativo = true
              and (
                  r.dono_id = %s
                  or exists (
                      select 1 from usuario_rastreador ur
                      where ur.rastreador_id = r.id and ur.usuario_id = %s
                        and ur.status = 1 and ur.ativo = true
                  )
              )
            """,
            (list(conectados), identidade, identidade)
        )
        return sorted(linha[0] for linha in res) if res else []

    @staticmethod
    async def endSession(cli):
        WebSocketServer.log(f"[!] cliente({cli.CID}) {cli.identidade}: sessão expirada")
        # para de entregar localizações antes mesmo de a conexão terminar de fechar
        WebSocketServer.removeWatchFromTracker(cli.CID, None)
        try:
            await cli.ws.send(FormatMessage.sessionExpired())
            await cli.ws.close(CLOSE_INVALID_TOKEN, "invalid_token")
        except websockets.ConnectionClosed:
            pass

    @staticmethod
    async def sessionIsValid(cli):
        consultou, usuario_id = await WebSocketServer.getTokenUser(cli.token)
        # com o banco fora do ar não dá para afirmar que a sessão acabou
        return not consultou or usuario_id == cli.identidade

    @staticmethod
    async def watchSession(cli):
        while True:
            await asyncio.sleep(WebSocketServer.TOKEN_CHECK_INTERVAL_S)
            if not await WebSocketServer.sessionIsValid(cli):
                await WebSocketServer.endSession(cli)
                return

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
                # o token de sessão nunca vai para o log
                exibida = "ident:***" if message.startswith("ident:") else message
                WebSocketServer.log(f"[@] Mensagem do cliente({self.CID}): {exibida}")
                if message.startswith("ident:"):
                    user_token = message[6:].strip()
                    
                    consultou, usuario_id = await WebSocketServer.getTokenUser(user_token)

                    WebSocketServer.log(f"[$][Ident] cliente({self.CID}): {usuario_id}")

                    if usuario_id:
                        self.token = user_token
                        return usuario_id

                    # só é "token inválido" se o banco de fato respondeu
                    await ws.send(FormatMessage.ident(False, "invalid_token" if consultou else "unavailable"))
                    if consultou:
                        await ws.close(CLOSE_INVALID_TOKEN, "invalid_token")
                    return None
            return None

        def __init__(self, ws):
            self.ws = ws
            self.identidade = None
            self.token = None
            self.session_task = None
            self.CID = WebSocketServer.ConnClient.getNextID()

        async def __aenter__(self):
            WebSocketServer.log(f"[@] Cliente({self.CID}) fazendo conexão: {self.ws.remote_address}")
            clientIdent_task = asyncio.create_task(self.getClientIdent(self.ws))
            try:
                self.identidade = await asyncio.wait_for(clientIdent_task, timeout=5.0)
            except asyncio.TimeoutError:
                self.identidade = None

            #todo: verifica no banco

            if not self.identidade:
                WebSocketServer.log(f"[!] Cliente({self.CID}) falhou na autenticação.")
                raise AuthError(f"Cliente({self.CID}) não autenticado.")

            await self.ws.send(FormatMessage.ident(True))
            WebSocketServer.addClient(self)
            self.session_task = asyncio.create_task(WebSocketServer.watchSession(self))

            return self

        async def __aexit__(self, exc_type, exc_val, exc_tb):
            if self.session_task:
                self.session_task.cancel()
            await self.ws.close()
            WebSocketServer.removeClient(self)

    async def handleClient(self, websocket):
        try:
            async with WebSocketServer.ConnClient(websocket) as cli:
                # Requisições do cliente
                async for message in cli.ws:
                    prefixo, _, valor = str(message).partition(":")

                    if prefixo in ("wta", "wtu", "con") and not await WebSocketServer.sessionIsValid(cli):
                        await WebSocketServer.endSession(cli)
                        break

                    if prefixo == "con":  # con:<pedido>:<id,id,...> quais estão conectados?
                        pedido, _, lista = valor.partition(":")
                        ids = [i for i in lista.split(",") if i.isdigit() and len(i) < 10]
                        if not pedido.isdigit() or len(ids) > WebSocketServer.MAX_CONNECTION_QUERY:
                            WebSocketServer.log(f"[!] cliente({cli.CID}) pedido de conexão inválido")
                            continue
                        conectados = await WebSocketServer.connectedTrackers(cli.identidade, {int(i) for i in ids})
                        await cli.ws.send(FormatMessage.connectedTrackers(int(pedido), conectados))
                        continue

                    if prefixo in ("wta", "wtr", "wtu") and not valor.strip().isdigit():
                        WebSocketServer.log(f"[!] cliente({cli.CID}) enviou id inválido: {message!r}")
                        continue

                    if message.startswith("wta:"): # Adiciona Watch desse rastreador pro cliente
                        tracker_id = int(message[4:].strip())                        
                        if await WebSocketServer.checkClientHasTracker(cli.identidade, tracker_id):
                            if WebSocketServer.addWatchToTracker(cli.CID, tracker_id):
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, True))
                            else:
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))
                        else:
                            await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))


                    elif message.startswith("wtr:"): # Remove Watch desse rastreador pro cliente
                        tracker_id = int(message[4:].strip())
                        # parar de observar não exige permissão (ela pode ter sido revogada)
                        removido = WebSocketServer.removeWatchFromTracker(cli.CID, tracker_id)
                        await cli.ws.send(FormatMessage.watchTracker(tracker_id, False, removido))


                    elif message.startswith("wtu:"): # Definir Watch apenas para esse rastreador pro cliente
                        tracker_id = int(message[4:].strip())                        
                        if await WebSocketServer.checkClientHasTracker(cli.identidade, tracker_id):
                            WebSocketServer.removeWatchFromTracker(cli.CID, None)
                            if WebSocketServer.addWatchToTracker(cli.CID, tracker_id):
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, True))
                            else:
                                await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))
                        else:
                            WebSocketServer.log(f"[$][Watch] cliente({cli.CID}) {cli.identidade}: {tracker_id} - não autorizado")
                            await cli.ws.send(FormatMessage.watchTracker(tracker_id, True, False))
                    else:
                        WebSocketServer.log(f"[!][message] cliente({cli.CID}) {cli.identidade}: {message}")
        except AuthError as e:
            WebSocketServer.log(f"[!] {e}")
        except websockets.ConnectionClosed:
            WebSocketServer.log(f"[!] Conexão encerrada")
        except Exception as e:
            WebSocketServer.log(f"[Erro] {e}")


    async def start_(self):
        WebSocketServer.LOOP = asyncio.get_running_loop()
        SRV = await websockets.serve(self.handleClient, self.host, self.port)
        WebSocketServer.log(f"[O] Servidor escutando em {self.host}:{self.port}...")

        await SRV.wait_closed()
        WebSocketServer.log("[-] Servidor encerrado.")
        ProgramStop.set("Servidor WSS encerrado.")

    def start(self):
        asyncio.run(self.start_())
