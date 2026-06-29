import socket
import struct
import threading
import time
from CryptLib.Crypt import Crypt
from dotenv import load_dotenv
import os
import asyncio
import websockets
import queue
import json
from datetime import datetime

load_dotenv()

class TrackerLocation:
    def __init__(self, veh, lat, lng):
        self.veh = veh
        self.lat = lat
        self.lng = lng

class AuthError(Exception):
    pass


class FMsg:
    @staticmethod
    def ident(res):
        return json.dumps({
            "t": "ident",
            "r": res
        })
    
    @staticmethod
    def loc(tk, lat, lng):
        return json.dumps({
            "t": "loc",
            "tk": tk,
            "lat": lat,
            "lng": lng
        })
    
    @staticmethod
    def watchVeh(tk, adding, res):
        return json.dumps({
            "t": "wVeh",
            "tk": tk,
            "a": (adding),
            "r": (res)
        })


class TCP:
    # RESPONSE TABLE
    R_OK = b'\x01'
    R_PLAIN_TOKEN_OK = b'\x02'
    R_TOKEN_OK = b'\x03'
    
    def __init__(self, host='0.0.0.0', port=12346):
        self.host = host
        self.port = port
    
    @staticmethod
    def log(msg):
        MESSAGES_LOG.put(f"[TCP]{msg}")


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
                if self.token not in TCP.ConnTracker.TEMP_TABLE_KEYS:
                    TCP.log(f"[!] Plain Token não encontrado na tabela")
                    return False
                self.Crypt = Crypt()
                self.Crypt.setKeys(bytearray(TCP.ConnTracker.TEMP_TABLE_KEYS[self.token]))
                return True
            except Exception as e:
                TCP.log(f"[!] Erro ao receber plain token do rastreador: {e}")
            return False

        def recValidateTracker(self, conn):
            try:
                if self.Crypt.decrypt(bytearray(conn.recv(1024))):
                    return True
            except Exception as e:
                TCP.log(f"[!] Erro ao validar rastreador: {e}")
            return False

        def __init__(self, conn, addr):
            self.conn = conn
            self.addr = addr
            self.token = None
            self.Crypt = None

        def __enter__(self):
            TCP.log(f"[+] Rastreador conectado por {self.addr}")

            # Pegar plain token do rastreador            
            if not self.recTrackerPlainToken(self.conn):
                TCP.log(f"[!] Plain Token inválido de {self.addr}. Desconectando...")
                raise AuthError("Plain Token inválido.")
            self.conn.sendall(TCP.R_PLAIN_TOKEN_OK)

            # Espera validação do rastreador
            if not self.recValidateTracker(self.conn):
                TCP.log(f"[!] O rastreador {self.addr} não foi validado. Desconectando...")
                raise AuthError("Não validado.")
            self.conn.sendall(TCP.R_TOKEN_OK)

            return self

        def __exit__(self, exc_type, exc_val, exc_tb):
            TCP.log(f"[-] Desligando rastreador {self.token or "não identificado"}...")
            self.conn.close()

    def handleTracker(self, connection, address):
        try:
            with TCP.ConnTracker(connection, address) as v:
                TCP.log(f"[$] Token recebido de {v.addr}: '{v.token}'")

                while True:
                    # Espera por latitude e longitude
                    DATA = v.conn.recv(1024)
                    if not DATA:
                        TCP.log(f"[!] {v.token} nenhuma resposta obtida.")
                        return
                    
                    lat, lng = None, None

                    try: # Desencripta e separa
                        DATA_DECRYPTED = bytearray(DATA)
                        v.Crypt.decrypt(DATA_DECRYPTED)
                        if len(DATA_DECRYPTED) != 8:
                            TCP.log(f"[!] Pacote decifrado inválido ({len(DATA_DECRYPTED)} bytes)")
                            continue
                        lat, lng = struct.unpack('<ff', DATA_DECRYPTED)
                    except Exception as e:
                        TCP.log(f"[!] Erro ao decifrar pacote: {e}")
                        TCP.log(f"[?] Pacote: {DATA.hex()}")
                        break

                    v.conn.sendall(TCP.R_OK)
                    TCP.log(f"[v] {v.token} {len(DATA)}bytes LAT:{lat:.6f} LNG:{lng:.6f}")
                    # Envia nova localização para o WebSocket
                    WSS.FILA_THREAD_NEW_LOC.put(TrackerLocation(v.token, lat, lng))

        except AuthError as e:
            TCP.log(f"[!] Erro de autenticação: {e}")
        except (ConnectionResetError, BrokenPipeError):
            TCP.log(f"[!] Conexão perdida")
        except Exception as e:
            TCP.log(f"[Erro] {e}")

    def start(self):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.bind((self.host, self.port))
            s.listen()
            TCP.log(f"[O] Servidor escutando em {self.host}:{self.port}...")

            while True:
                time.sleep(0.2) # Evitar sobrecarga
                try:
                    conn, addr = s.accept()
                    threading.Thread(target=self.handleTracker, args=(conn, addr), daemon=True).start()
                except Exception as e:
                    TCP.log(f"[Falha ao iniciar thread]: {e}")
                    break
        
        TCP.log("\n[-] Servidor encerrado.")
        ProgramError.set("Servidor TCP encerrado")







class WSS:
    CLIENTS = {}
    CLIENTS_LOCK = threading.Lock()
    VEHICLES = {}
    VEHICLES_LOCK = threading.Lock()
    
    FILA_THREAD_NEW_LOC = queue.Queue()

    def __init__(self, host='0.0.0.0', port=12344):
        self.host = host
        self.port = port

    @staticmethod
    def log(msg):
        MESSAGES_LOG.put(f"[WSS]{msg}")


    @staticmethod
    def addClient(cli):
        with WSS.CLIENTS_LOCK:
            WSS.CLIENTS[cli.CID] = cli
            WSS.log(f"[+] Cliente {cli.CID} authenticado.")

    @staticmethod
    def removeClient(cli):
        WSS.removeWatch(cli.CID, None)
        with WSS.CLIENTS_LOCK:
            if cli.CID in WSS.CLIENTS:
                del WSS.CLIENTS[cli.CID]
                WSS.log(f"[-] Cliente {cli.CID} removido.")

    @staticmethod
    def addWatch(cid, veh_id):
        with WSS.VEHICLES_LOCK:
            if veh_id not in WSS.VEHICLES:
                WSS.VEHICLES[veh_id] = set()
            if cid not in WSS.VEHICLES[veh_id]:
                WSS.VEHICLES[veh_id].add(cid)
                WSS.log(f"[+] Cliente {cid} observando o veículo {veh_id}")
                return True
        return False

    @staticmethod
    def removeWatch(cid, veh_id):
        with WSS.VEHICLES_LOCK:
            to_delete = []
            result = False

            def rem(veh):
                if cid in WSS.VEHICLES[veh]:
                    WSS.log(f"[-] Cliente {cid} parou de observar o veículo {veh}")
                    WSS.VEHICLES[veh].discard(cid)
                    if not WSS.VEHICLES[veh]:  # Deletar se ficar vazio
                        to_delete.append(veh)
                    return True
                return False

            if veh_id is None: # Remover o cliente de todos os veículos
                for v_id in WSS.VEHICLES:
                    rem(v_id)
                result = True
            else:
                if veh_id in WSS.VEHICLES:
                    result = rem(veh_id)
            
            if to_delete:
                for veh in to_delete:
                    del WSS.VEHICLES[veh]
                    WSS.log(f"[-] Veículo vazio {veh} removido.")
            
            return result

    async def sendNewLocToCLients(self, vl, cli_ws):
        try:
            await cli_ws.send(FMsg.loc(vl.veh, vl.lat, vl.lng))
        except websockets.ConnectionClosed:
            WSS.log(f"[!] Tentou se comunicar com {cli_ws.remote_address} mas a conexão estava encerrada.")

    def processNewLocation(self):
        WSS.log("[O] Iniciando processamento de novas localizações...")
        
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        threading.Thread(target=loop.run_forever, daemon=True).start()

        while True:
            try:
                new_loc = WSS.FILA_THREAD_NEW_LOC.get()
                
                if new_loc is None or not isinstance(new_loc, TrackerLocation):
                    WSS.log("[!] Localização inválida recebida. Ignorando...")
                    continue
                            
                cpy_veh = None
                with WSS.VEHICLES_LOCK:
                    if new_loc.veh not in WSS.VEHICLES:
                        continue
                    cpy_veh = WSS.VEHICLES[new_loc.veh].copy()

                for cid in cpy_veh:
                    c_ws = None
                    with WSS.CLIENTS_LOCK:
                        if cid not in WSS.CLIENTS:
                            continue
                        c_ws = WSS.CLIENTS[cid].ws
                    
                    WSS.log(f"[+] Enviando {new_loc.veh} para cliente {cid}")
                    loop.call_soon_threadsafe(
                        asyncio.create_task,
                        self.sendNewLocToCLients(new_loc, c_ws)
                    )
                    time.sleep(0.2)
            except queue.Empty:
                time.sleep(0.1)
            except:
                ProgramError.set("Process new location")

    class ConnClient:
        serial_id = 0
        serial_id_lock = threading.Lock()

        @staticmethod
        def getNextID():
            with WSS.ConnClient.serial_id_lock:
                WSS.ConnClient.serial_id += 1
                return WSS.ConnClient.serial_id

        async def getClientIdent(self, ws):
            async for message in ws:
                WSS.log(f"[@] Mensagem recebida de {ws.remote_address}: {message}")
                if message.startswith("ident:"):
                    user_token = message[6:].strip()
                    #todo: verifica se é um cliente válido
                    if user_token:
                        return user_token
                    else:
                        await ws.send(FMsg.ident(False))
                        return None
            return None

        def __init__(self, ws):
            self.ws = ws
            self.identidade = None
            self.CID = WSS.ConnClient.getNextID()

        async def __aenter__(self):
            WSS.log(f"[@] Cliente conectado: {self.ws.remote_address}")
            clientIdent_task = asyncio.create_task(self.getClientIdent(self.ws))
            try:
                self.identidade = await asyncio.wait_for(clientIdent_task, timeout=5.0)
            except asyncio.TimeoutError:
                self.identidade = None
            #todo: verifica no banco
            if not self.identidade:
                WSS.log(f"[!] Cliente {self.CID} falhou na autenticação.")
                raise AuthError("Cliente não autenticado.")

            await self.ws.send(FMsg.ident(True))
            WSS.addClient(self)

            return self

        async def __aexit__(self, exc_type, exc_val, exc_tb):
            await self.ws.close()
            WSS.removeClient(self)

    async def handleClient(self, websocket):
        try:
            async with WSS.ConnClient(websocket) as c:
                # Requisições do cliente
                async for message in c.ws:
                    if message.startswith("wva:"): # Adiciona Watch desse veiculo pro cliente
                        #todo: verificar se o veiculo existe no banco antes, (e se ele pode ser rastreado)
                        veh_id = message[4:].strip()
                        if WSS.addWatch(c.CID, veh_id):
                            await c.ws.send(FMsg.watchVeh(veh_id, True, True))
                        else:
                            await c.ws.send(FMsg.watchVeh(veh_id, True, False))
                    elif message.startswith("wvr:"): # Remove Watch desse veiculo pro cliente
                        veh_id = message[4:].strip()
                        if WSS.removeWatch(c.CID, veh_id):
                            await c.ws.send(FMsg.watchVeh(veh_id, False, True))
                        else:
                            await c.ws.send(FMsg.watchVeh(veh_id, False, False))
                    elif message.startswith("wvu:"): # Definir Watch apenas para esse veiculo pro cliente
                        #todo: verificar se o veiculo existe no banco antes, (e se ele pode ser rastreado)
                        veh_id = message[4:].strip()
                        WSS.removeWatch(c.CID, None)
                        if WSS.addWatch(c.CID, veh_id):
                            await c.ws.send(FMsg.watchVeh(veh_id, True, True))
                        else:
                            await c.ws.send(FMsg.watchVeh(veh_id, True, False))
                    else:
                        WSS.log(f"[$][client] {c.identidade}: {message}")
        except AuthError as e:
            WSS.log(f"[!] {e}")
        except websockets.ConnectionClosed:
            WSS.log(f"[!] Conexão encerrada")
        except Exception as e:
            WSS.log(f"[Erro] {e}")


    async def start_(self):
        SRV = await websockets.serve(self.handleClient, self.host, self.port)
        WSS.log(f"[O] Servidor escutando em {self.host}:{self.port}...")
        threading.Thread(target=self.processNewLocation, daemon=True).start()

        await SRV.wait_closed()
        WSS.log("[-] Servidor encerrado.")
        ProgramError.set("Servidor WSS encerrado.")

    def start(self):
        asyncio.run(self.start_())



MESSAGES_LOG = queue.Queue()
def log_service():
    try:
        while True:
            msg = MESSAGES_LOG.get()
            if msg is None:
                continue
            print(f"{datetime.now()}|{msg}")
    finally:
        ProgramError.set("Log service")



class ProgramError:
    withError = False
    msg = []
    lock = threading.Lock()
    @staticmethod
    def set(msg):
        with ProgramError.lock:
            ProgramError.withError = True
            ProgramError.msg.append(msg)
    @staticmethod
    def test():
        with ProgramError.lock:
            return ProgramError.withError, ProgramError.msg

if __name__ == "__main__":
    threading.Thread(target=log_service, daemon=True).start()
    threading.Thread(target=WSS().start, daemon=True).start()
    threading.Thread(target=TCP().start, daemon=True).start()

    while not ProgramError.test()[0]:
        try:
            time.sleep(0.5)
        except:
            ProgramError.set("Main thread interrupted")

    print(f"[ProgramError]{ProgramError.test()[1]}")
