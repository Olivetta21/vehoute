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
    R_OK = b'\x01'
    R_PLAIN_TOKEN_OK = b'\x02'
    R_TOKEN_OK = b'\x03'
    
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


    class ConnTracker:

        def recTrackerPlainToken(self, conn):
            try:
                public_token = conn.recv(32).decode(errors='ignore').rstrip('\x00')

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
                TcpServer.log(f"[$][Pub_Priv_Token] {self.tracker_id}: {result[1]} - {public_token}")

                self.Crypt = Crypt()
                self.Crypt.setKeys(result_byte)
                return True
            except Exception as e:
                TcpServer.log(f"[!] Erro ao receber plain token do rastreador: {e}")
            return False

        def recValidateTracker(self, conn):
            try:
                if self.Crypt.decrypt(bytearray(conn.recv(1024))):
                    return True
            except Exception as e:
                TcpServer.log(f"[!] Erro ao validar rastreador: {e}")
            return False

        def __init__(self, conn, addr):
            self.conn = conn
            self.addr = addr
            self.tracker_id = None
            self.Crypt = None

        def __enter__(self):
            TcpServer.log(f"[+] Rastreador fazendo conexão por {self.addr}")

            # Pegar plain token do rastreador            
            if not self.recTrackerPlainToken(self.conn):
                TcpServer.log(f"[!] Plain Token inválido de {self.addr}. Desconectando...")
                raise AuthError("Plain Token inválido.")
            self.conn.sendall(TcpServer.R_PLAIN_TOKEN_OK)

            # Espera validação do rastreador
            if not self.recValidateTracker(self.conn):
                TcpServer.log(f"[!] O rastreador {self.addr} não foi validado. Desconectando...")
                raise AuthError("Não validado.")
            self.conn.sendall(TcpServer.R_TOKEN_OK)

            return self

        def __exit__(self, exc_type, exc_val, exc_tb):
            TcpServer.log(f"[-] Desligando rastreador {self.tracker_id or "não identificado"}...")
            self.conn.close()

    def handleTracker(self, connection, address):
        try:
            with TcpServer.ConnTracker(connection, address) as tracker:
                TcpServer.log(f"[$] ID recebido de {tracker.addr}: '{tracker.tracker_id}'")

                while True:
                    # Espera por latitude e longitude
                    DATA = tracker.conn.recv(1024)
                    if not DATA:
                        TcpServer.log(f"[!] {tracker.tracker_id} nenhuma resposta obtida.")
                        return
                    
                    lat, lng = None, None

                    try: # Desencripta e separa
                        DATA_DECRYPTED = bytearray(DATA)
                        tracker.Crypt.decrypt(DATA_DECRYPTED)
                        if len(DATA_DECRYPTED) != 8:
                            TcpServer.log(f"[!] Pacote decifrado inválido ({len(DATA_DECRYPTED)} bytes)")
                            continue
                        lat, lng = struct.unpack('<ff', DATA_DECRYPTED)
                    except Exception as e:
                        TcpServer.log(f"[!] Erro ao decifrar pacote: {e}")
                        TcpServer.log(f"[?] Pacote: {DATA.hex()}")
                        break

                    tracker.conn.sendall(TcpServer.R_OK)
                    TcpServer.log(f"[v] {tracker.tracker_id} {len(DATA)}bytes LAT:{lat:.6f} LNG:{lng:.6f}")
                    
                    # Envia nova localização para o WebSocket
                    NewLocProcessing.FILA_NEW_LOC.put(TrackerLocation(tracker.tracker_id, lat, lng))

        except AuthError as e:
            TcpServer.log(f"[!] Erro de autenticação: {e}")
        except (ConnectionResetError, BrokenPipeError):
            TcpServer.log(f"[!] Conexão perdida")
        except Exception as e:
            TcpServer.log(f"[Erro] {e}")

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

