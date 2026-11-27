from random import randint
from ProgramStop import ProgramStop
from TrackerLocation import TrackerLocation
from NewLocProcessing import NewLocProcessing
from CryptLib.Crypt import Crypt
from LogService import LogService
from DataBase import DataBase
import socket
import struct
import threading
import time

class AuthError(Exception):
    pass

class TcpServer:
    TRACKER_PERMISSION_CONNECTION = 1
    TRACKER_PERMISSION_SEND_LOCATION = 2
    AUTH_TIMEOUT_S = 60  # tempo máximo para cada etapa da autenticação (GSM é lento)
    # RESPONSE TABLE
    R_OK = b"receb\n"
    R_PLAIN_TOKEN_OK = b'plain\n'
    R_TOKEN_OK = b'priva\n'
    TRACKERS_ATIVOS = {}
    TRACKERS_ATIVOS_LOCK = threading.Lock()
    
    def __init__(self, host='0.0.0.0', port=12346):
        self.host = host
        self.port = port
    
    @staticmethod
    def log(msg):
        LogService.log(f"[TCP]{msg}")

    @staticmethod
    def converter(valor):
        if isinstance(valor, str):
            #recebe string de HEX retorna um bytearray
            return bytearray.fromhex(valor)
        elif isinstance(valor, (bytearray)):
            #recebe bytearray retorna string em hex
            return bytes(valor).hex()
        else:
            raise TypeError("O valor deve ser uma string, bytes ou bytearray")

    @staticmethod
    def sendToAllClients(message):
        TcpServer.log(f"[i] Enviando mensagem para todos os rastreadores ativos: {message}")
        message = message.replace("\\n", "\n")

        with TcpServer.TRACKERS_ATIVOS_LOCK:
            for cid, info in TcpServer.TRACKERS_ATIVOS.items():
                try:
                    TcpServer.log(f"[i] Enviando mensagem para rastreador({cid}) {info['tracker_id']} em {info['addr']}")
                    if info['conn']:
                        info['conn'].sendall(message.encode())
                except Exception as e:
                    TcpServer.log(f"[!] Erro ao enviar mensagem para rastreador({cid}) {info['tracker_id']} em {info['addr']}: {e}")

    @staticmethod
    def addTrackerAtivo(CID, addr, tracker_id=None, auth=False, conn=None):
        with TcpServer.TRACKERS_ATIVOS_LOCK:
            TcpServer.TRACKERS_ATIVOS[CID] = {
                "tracker_id": tracker_id,
                "addr": addr,
                "auth": auth,
                "conn": conn
            }

    @staticmethod
    def removeTrackerAtivo(CID):
        with TcpServer.TRACKERS_ATIVOS_LOCK:
            TcpServer.TRACKERS_ATIVOS.pop(CID, None)

    @staticmethod
    def connectedTrackerIds():
        """Ids dos rastreadores autenticados e com conexão aberta."""
        with TcpServer.TRACKERS_ATIVOS_LOCK:
            return {info["tracker_id"] for info in TcpServer.TRACKERS_ATIVOS.values() if info["auth"]}

    @staticmethod
    def detectDeadConnection(conn):
        """Faz o sistema derrubar em poucos minutos um rastreador que sumiu sem avisar."""
        try:
            conn.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 1)
            if hasattr(socket, "SIO_KEEPALIVE_VALS"):  # Windows: (ligado, ociosidade ms, intervalo ms)
                conn.ioctl(socket.SIO_KEEPALIVE_VALS, (1, 60_000, 10_000))
            else:
                for opcao, valor in (("TCP_KEEPIDLE", 60), ("TCP_KEEPINTVL", 10), ("TCP_KEEPCNT", 6),
                                     ("TCP_USER_TIMEOUT", 180_000)):
                    if hasattr(socket, opcao):
                        conn.setsockopt(socket.IPPROTO_TCP, getattr(socket, opcao), valor)
        except OSError as e:
            TcpServer.log(f"[!] Não foi possível configurar a detecção de conexão morta: {e}")

    @staticmethod
    def logTrackersAtivos():
        with TcpServer.TRACKERS_ATIVOS_LOCK:
            if not TcpServer.TRACKERS_ATIVOS:
                TcpServer.log("[i] Nenhum rastreador conectado no momento.")
                return

            TcpServer.log(f"[i] Rastreador(es) conectados: {len(TcpServer.TRACKERS_ATIVOS)}")
            for cid, info in TcpServer.TRACKERS_ATIVOS.items():
                TcpServer.log(f"[i] CID={cid} tracker_id={info['tracker_id']} addr={info['addr']} auth={info['auth']}" )


    class ConnTracker:
        serial_id = 0
        serial_id_lock = threading.Lock()
        
        @staticmethod
        def getNextID():
            with TcpServer.ConnTracker.serial_id_lock:
                TcpServer.ConnTracker.serial_id += 1
                return TcpServer.ConnTracker.serial_id

        def recTrackerPlainToken(self, conn):
            try:
                public_token = conn.recv(32).decode(errors='ignore').rstrip('\x00')                
                TcpServer.log(f"[$][Public] Rastreador({self.CID}): {public_token}")
                result = None
                with DataBase.get() as db_conn:
                    with db_conn.cursor() as cur:
                        cur.execute(
                            "SELECT id, token from rastreador where token_publico = %s",
                            (public_token,)
                        )
                        result = cur.fetchone()

                if not result:
                    raise AuthError(f"Rastreador({self.CID}) não encontrado.")

                self.tracker_id = result[0]
                if not DataBase.tracker_has_permission(
                    self.tracker_id,
                    TcpServer.TRACKER_PERMISSION_CONNECTION,
                ):
                    TcpServer.log(
                        f"[!] Rastreador({self.CID}) sem permissão de conexão. Desconectando..."
                    )
                    return False

                result_byte = TcpServer.converter(result[1])
                TcpServer.log(f"[$][Private] Rastreador({self.CID}): chave carregada para o id {self.tracker_id}")

                self.Crypt = Crypt()
                self.Crypt.setKeys(result_byte)
                return True
            except Exception as e:
                TcpServer.log(f"[!] Rastreador({self.CID}) Erro ao receber plain token: {e}")
            return False

        def recValidateTracker(self, conn):
            try:
                validation = bytearray(conn.recv(1024))
                TcpServer.log(f"[$][Validation] Rastreador({self.CID}): {validation.hex()}")                
                if self.Crypt.decrypt(validation):
                    return True
            except Exception as e:
                TcpServer.log(f"[!] Rastreador({self.CID}) Erro ao validar rastreador: {e}")
            return False

        def __init__(self, conn, addr, cid_ref):
            self.CID = TcpServer.ConnTracker.getNextID()
            cid_ref[0] = self.CID
            self.conn = conn
            self.addr = addr
            self.tracker_id = None
            self.Crypt = None

        def __enter__(self):
            TcpServer.addTrackerAtivo(self.CID, self.addr, auth=False, conn=self.conn)
            TcpServer.log(f"[+] Rastreador({self.CID}) fazendo conexão por {self.addr}")
            self.conn.settimeout(TcpServer.AUTH_TIMEOUT_S)

            # Pegar plain token do rastreador            
            if not self.recTrackerPlainToken(self.conn):
                TcpServer.log(f"[!] Plain Token do rastreador({self.CID}) inválido. Desconectando...")
                raise AuthError(f"Plain Token do rastreador({self.CID}) inválido.")
            self.conn.sendall(TcpServer.R_PLAIN_TOKEN_OK)

            # Espera validação do rastreador
            if not self.recValidateTracker(self.conn):
                TcpServer.log(f"[!] O rastreador({self.CID}) não foi validado. Desconectando...")
                raise AuthError(f"O rastreador({self.CID}) não foi validado.")
            self.conn.sendall(TcpServer.R_TOKEN_OK)

            # Autenticado: pode ficar em silêncio (sem sinal de GPS); o keepalive detecta conexões mortas
            self.conn.settimeout(None)
            TcpServer.detectDeadConnection(self.conn)

            TcpServer.addTrackerAtivo(self.CID, self.addr, self.tracker_id, auth=True, conn=self.conn)
            
            return self

        def __exit__(self, exc_type, exc_val, exc_tb):
            TcpServer.log(f"[-] Desligando rastreador({self.CID}) {self.tracker_id or 'não identificado'}...")

    def handleTracker(self, connection, address):
        try:
            CID_ref = [None]
            with TcpServer.ConnTracker(connection, address, CID_ref) as tracker:
                TcpServer.log(f"[$] ID recebido do rastreador({tracker.CID}): '{tracker.tracker_id}'")

                while True:
                    # Espera por latitude e longitude
                    DATA = tracker.conn.recv(1024)
                    if not DATA:
                        TcpServer.log(f"[!] rastreador({tracker.CID}) {tracker.tracker_id} nenhuma resposta obtida.")
                        break
                    
                    lat, lng = None, None

                    try: # Desencripta e separa
                        DATA_DECRYPTED = bytearray(DATA)
                        if not tracker.Crypt.decrypt(DATA_DECRYPTED) or len(DATA_DECRYPTED) != 8:
                            TcpServer.log(f"[!] Rastreador({tracker.CID}) Pacote decifrado inválido ({len(DATA_DECRYPTED)} bytes)")
                            continue
                        lat, lng = struct.unpack('<ff', DATA_DECRYPTED)
                    except Exception as e:
                        TcpServer.log(f"[!] Rastreador({tracker.CID}) Erro ao decifrar pacote: {e}")
                        TcpServer.log(f"[?] Rastreador({tracker.CID}) Pacote: {DATA.hex()}")
                        break

                    tracker.conn.sendall(TcpServer.R_OK)
                    TcpServer.log(f"[Loc] Rastreador({tracker.CID}) {tracker.tracker_id} {len(DATA)}b Lat:{lat:.6f} Lng:{lng:.6f}")

                    if not DataBase.tracker_has_permission(
                        tracker.tracker_id,
                        TcpServer.TRACKER_PERMISSION_SEND_LOCATION,
                    ):
                        TcpServer.log(
                            f"[!] Rastreador({tracker.CID}) sem permissão para enviar localização. Ignorando..."
                        )
                        continue
                    
                    # Envia nova localização para o WebSocket
                    NewLocProcessing.FILA_NEW_LOC.put(TrackerLocation(tracker.tracker_id, lat, lng))

        except AuthError as e:
            TcpServer.log(f"[!] Rastreador({CID_ref[0]}) Erro de autenticação: {e}")
        except (ConnectionResetError, BrokenPipeError):
            TcpServer.log(f"[!] Rastreador({CID_ref[0]}) Conexão perdida")
        except Exception as e:
            TcpServer.log(f"[Erro] Rastreador({CID_ref[0]}){e}")
        #desconecta o cliente:
        connection.close()
        TcpServer.removeTrackerAtivo(CID_ref[0])
        TcpServer.log(f"[-] Rastreador({CID_ref[0]}) desconectado.")

    def start(self):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            s.bind((self.host, self.port))
            s.listen()
            TcpServer.log(f"[O] Servidor escutando em {self.host}:{self.port}...")

            while True:
                time.sleep(0.2) # Evitar sobrecarga
                try:
                    conn, addr = s.accept()
                    threading.Thread(target=self.handleTracker, args=(conn, addr), daemon=True).start()
                except RuntimeError as e:  # sem recursos para nova thread: recusa só esta conexão
                    TcpServer.log(f"[Falha ao iniciar thread]: {e}")
                    conn.close()
                except OSError as e:
                    TcpServer.log(f"[Falha ao aceitar conexão]: {e}")
                    if s.fileno() == -1:
                        break
        
        TcpServer.log("\n[-] Servidor encerrado.")
        ProgramStop.set("Servidor TCP encerrado")

class HeartBeat:
    #envia um simples \n para todos os rastreadores

    def __init__(self, interval=60, randMin=0, randMax=59):
        self.interval = interval
        self.randMin = randMin
        self.randMax = randMax

    def start(self):
        TcpServer.log(f"[O] Heartbeat iniciado. Intervalo: {self.interval}s, Aleatório: {self.randMin}-{self.randMax}s")
        while True:
            time.sleep(self.interval)
            TcpServer.log(f"[+] Heartbeat")
            sockets = {}
            
            with TcpServer.TRACKERS_ATIVOS_LOCK:
                for cid, info in TcpServer.TRACKERS_ATIVOS.items():
                    randomTime = randint(self.randMin, self.randMax)

                    if sockets.get(randomTime) is None:
                        sockets[randomTime] = []

                    sockets[randomTime].append((info['conn']))

            for randomTime, conns in sockets.items():
                time.sleep(randomTime)
                for conn in conns:
                    try:
                        conn.sendall("\n".encode())
                    except Exception as e:
                        TcpServer.log(f"[!] Erro ao enviar heartbeat para rastreador: {e}")
                        try:  # libera o recv da thread, que remove o rastreador da lista
                            conn.shutdown(socket.SHUT_RDWR)
                        except OSError:
                            pass