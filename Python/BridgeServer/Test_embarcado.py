import socket
import struct
import time
import random
from CryptLib.Crypt import Crypt

# RESPONSE TABLE
#R_OK = b'\x01'
#R_PLAIN_TOKEN_OK = b'\x02'
#R_TOKEN_OK = b'\x03'
R_OK = b"receb\n"
R_PLAIN_TOKEN_OK = b'plain\n'
R_TOKEN_OK = b'priva\n'

# SERVER AND CLIENT CONFIGURATION
HOST = '127.0.0.1'
PORT = 12346
TOKEN = b'arduino001'
INTERVAL = 40
MAXSTACK = 10

def hex_bytes_conv(value: str | bytes | bytearray) -> bytearray | str:
    if isinstance(value, str):
        value = value.replace("0x", "").replace(",", " ").strip()

        # HEX contínuo: 23456789abcdef0123456789abcdef01
        if " " not in value:
            if len(value) % 2 != 0:
                raise ValueError("HEX deve possuir uma quantidade par de caracteres")

            value = " ".join(value[i:i+2] for i in range(0, len(value), 2))

        return bytearray.fromhex(value)

    elif isinstance(value, (bytes, bytearray)):
        return ", ".join(f"0x{b:02X}" for b in value)

    raise TypeError("O valor deve ser str, bytes ou bytearray")

# ENCRYPTION
tracker_key = bytearray([0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01, 0x23, 0x45, 0x67, 0x89, 0xAB, 0xCD, 0xEF, 0x01])
crypt = Crypt()

def enviarLocalizacao(sock, lat, lng):    
    if sock is None or sock.fileno() == -1:
        print("[!] Socket inválido.")
        return False
    
    # Montar Pacote
    encrypted = bytearray(struct.pack('<ff', lat, lng))
    if crypt.encrypt(encrypted):
        sock.sendall(encrypted)

    print(f"[→] Enviando: {lat:.6f}, {lng:.6f}\n[?] pacote criptografado: {encrypted.hex()}\n[~] Tamanho: {len(encrypted)} bytes")

    resposta = sock.recv(1024)
    if resposta == R_OK:
        return True
    else:
        print("[←] Resposta inesperada:", resposta)
        return False

def conectar():
    try:
        sock = socket.create_connection((HOST, PORT), timeout=5)

        # Enviando token em plain text
        print("[→] Enviando plain token...")
        sock.sendall(TOKEN)
        if sock.recv(1024) != R_PLAIN_TOKEN_OK:
            print("[-] Resposta inesperada. Verifique a chave pública.")
            sock.close()
            return None

        # Mensagem de confirmação
        print("[→] Enviando encripted...")
        encrypted = bytearray(random.randbytes(4))
        crypt.encrypt(encrypted)
        sock.sendall(encrypted)
        if sock.recv(1024) != R_TOKEN_OK:
            print("[-] Resposta inesperada. Verifique o token.")
            sock.close()
            return None

        return sock
    except Exception as e:
        print(f"[-] Erro de conexão: {e}.")
    return None

def main():
    global HOST, PORT, TOKEN, tracker_key

    HOST = input(f"Endereço do host [{HOST}]: ") or HOST
    PORT = input(f"Porta [{PORT}]: ") or PORT
    TOKEN = input(f"Token [{TOKEN.decode()}]: ").encode() or TOKEN
    tracker_key = hex_bytes_conv(input(f"Token privado [{hex_bytes_conv(tracker_key)}]: ")) or tracker_key

    print(f"Conectando-se a {HOST}:{PORT} com token '{TOKEN.decode()}' e chave privada '{hex_bytes_conv(tracker_key)}'...")

    crypt.setKeys(tracker_key)

    sock = None
    locsNotSended = []

    # Timestamp inicial
    last_time = 0

    while True:
        try:
            # Esperar o minimo de segundos entre envios
            timestamp = time.time()
            interval = timestamp - last_time            
            if interval < INTERVAL:
                toWait = INTERVAL - interval
                print(f"[⏳] Aguardando {toWait:.2f} segundos para o próximo envio...")
                time.sleep(toWait)
            last_time = time.time()
            print("[O] Enviando...")

            # Mock de localização
            lat = -20 - 10 * random.random()
            lng = -50 - 10 * random.random()
            locsNotSended.append([lat, lng])

            if len(locsNotSended) > MAXSTACK:
                print(f"[!] Pilha de localizações excedida ({len(locsNotSended)}).")
                locsNotSended = locsNotSended[-MAXSTACK:]
            
            if sock is None or sock.fileno() == -1:
                sock = conectar()

            print(f"Localizações pendentes: {len(locsNotSended)}")
            while len(locsNotSended) > 0:                
                if enviarLocalizacao(sock, locsNotSended[0][0], locsNotSended[0][1]):
                    locsNotSended.pop(0)
                    print("[←] Sucesso")
                else:
                    print("[!] Falha ao enviar localização.")
                    break
        
        except TimeoutError:
            print("[!] Tempo limite de conexão excedido.")
            continue

        except (ConnectionResetError, BrokenPipeError, ConnectionAbortedError):
            print("[-] Conexão perdida...")
            sock.close()

        except KeyboardInterrupt:
            print("\n[!] Encerrando cliente.")
            sock.close()
            break

if __name__ == "__main__":
    main()
