from ProgramStop import ProgramStop
from TrackerLocation import TrackerLocation
from NewLocProcessing import NewLocProcessing
from CryptLib.Crypt import Crypt
from LogService import LogService
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


    class ConnTracker:
        TEMP_TABLE_KEYS = {
            "arduino001": bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01]),            
            "token_publico123": bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01]),
            "tokenPublicoAlpha123": bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01]),
            "teste2": bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01]),
            "paiapublictoken": bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01]),
            "teste": bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01])
        }

        def recTrackerPlainToken(self, conn):
            try:
                self.token = conn.recv(32).decode(errors='ignore').rstrip('\x00')
                if self.token not in TcpServer.ConnTracker.TEMP_TABLE_KEYS:
                    TcpServer.log(f"[!] Plain Token não encontrado na tabela")
                    return False
                self.Crypt = Crypt()
                self.Crypt.setKeys(bytearray(TcpServer.ConnTracker.TEMP_TABLE_KEYS[self.token]))
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
            self.token = None
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
            TcpServer.log(f"[-] Desligando rastreador {self.token or "não identificado"}...")
            self.conn.close()

    def handleTracker(self, connection, address):
        try:
            with TcpServer.ConnTracker(connection, address) as tracker:
                TcpServer.log(f"[$] Token recebido de {tracker.addr}: '{tracker.token}'")

                while True:
                    # Espera por latitude e longitude
                    DATA = tracker.conn.recv(1024)
                    if not DATA:
                        TcpServer.log(f"[!] {tracker.token} nenhuma resposta obtida.")
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
                    TcpServer.log(f"[v] {tracker.token} {len(DATA)}bytes LAT:{lat:.6f} LNG:{lng:.6f}")
                    # Envia nova localização para o WebSocket

                    NewLocProcessing.FILA_NEW_LOC.put(TrackerLocation(tracker.token, lat, lng))

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

