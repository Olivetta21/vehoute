import importlib
from pathlib import Path
import subprocess
import sys

def ler_bibliotecas(caminho_arquivo_):
    caminho_arquivo = Path(__file__).parent / caminho_arquivo_
    
    bibliotecas = {}

    with open(caminho_arquivo, "r", encoding="utf-8") as arquivo:
        for linha in arquivo:
            linha = linha.strip()

            if not linha:
                continue

            partes = linha.split(";", 1)

            if len(partes) != 2:
                continue

            titulo, descricao = partes

            bibliotecas[titulo.strip()] = descricao.strip()

    return bibliotecas

def testar_bibliotecas(caminho_arquivo):
    print("Verificando bibliotecas necessárias...")

    BIBLIOTECAS = ler_bibliotecas(caminho_arquivo)
    if not BIBLIOTECAS:
        print("Nenhuma biblioteca encontrada no arquivo.")
        return

    have_all = True
    for modulo, pacote in BIBLIOTECAS.items():
        try:
            importlib.import_module(modulo)
        except ImportError:
            have_all = False
            print(f"Não instalada:  {modulo} -> {pacote}")
            resposta = input("\nDeseja instalar esta biblioteca? [S/N]: ")

            if resposta.strip().lower() in ("s", "sim"):

                subprocess.run([
                    sys.executable,
                    "-m",
                    "pip",
                    "install",
                    pacote
                ])

                print("\nInstalação concluída.")

            else:
                print("\nInstalação cancelada.")
            
            print("\n\n")

    if have_all:
        print("Todas as bibliotecas estão instaladas.")
    else:
        print("Se todas as bibliotecas foram instaladas, o programa será iniciado normalmente.")