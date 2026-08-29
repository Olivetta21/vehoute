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
                with DataBase.get() as conn:
                    with conn.cursor() as cur:
                        cur.execute(
                            "SELECT id, token from rastreador where token_publico = %s",
                            (public_token,)
                        )
                        result = cur.fetchone()

                self.tracker_id = result[0]
                result_byte = TcpServer.converter(result[1])
                TcpServer.log(f"[$][Private] Rastreador({self.CID}): {result}")

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
                TcpServer.log(f"[!] Erro ao validar rastreador: {e}")
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
                        tracker.Crypt.decrypt(DATA_DECRYPTED)
                        if len(DATA_DECRYPTED) != 8:
                            TcpServer.log(f"[!] Rastreador({tracker.CID}) Pacote decifrado inválido ({len(DATA_DECRYPTED)} bytes)")
                            continue
                        lat, lng = struct.unpack('<ff', DATA_DECRYPTED)
                    except Exception as e:
                        TcpServer.log(f"[!] Rastreador({tracker.CID}) Erro ao decifrar pacote: {e}")
                        TcpServer.log(f"[?] Rastreador({tracker.CID}) Pacote: {DATA.hex()}")
                        break

                    tracker.conn.sendall(TcpServer.R_OK)
                    TcpServer.log(f"[Loc] Rastreador({tracker.CID}) {tracker.tracker_id} {len(DATA)}b Lat:{lat:.6f} Lng:{lng:.6f}")
                    
                    # Envia nova localização para o WebSocket
                    NewLocProcessing.FILA_NEW_LOC.put(TrackerLocation(tracker.tracker_id, lat, lng))

        except AuthError as e:
            TcpServer.log(f"[!] Erro de autenticação: {e}")
        except (ConnectionResetError, BrokenPipeError):
            TcpServer.log(f"[!] Conexão perdida")
        except Exception as e:
            TcpServer.log(f"[Erro] {e}")
        #desconecta o cliente:
        connection.close()
        TcpServer.removeTrackerAtivo(CID_ref[0])
        TcpServer.log(f"[-] Rastreador({CID_ref[0]}) desconectado.")

    def start(self):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.bind((self.host, self.port))
            s.listen()
            TcpServer.log(f"[O] Servidor escutando em {self.host}:{self.port}...")

            while True:
                time.sleep(0.2) # Evitar sobrecarga
                try:
                    conn, addr = s.accept()
                    threading.Thread(target=self.handleTracker, args=(conn, addr), daemon=True).start()
                except Exception as e:
                    TcpServer.log(f"[Falha ao iniciar thread]: {e}")
                    break
        
        TcpServer.log("\n[-] Servidor encerrado.")
        ProgramStop.set("Servidor TCP encerrado")

