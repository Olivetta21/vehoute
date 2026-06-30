from TcpServer import TcpServer
from WebSocketServer import WebSocketServer
from NewLocProcessing import NewLocProcessing
from time import sleep
from LogService import LogService
from ProgramStop import ProgramStop
import threading
import os
from datetime import datetime

def closeProgram():
    LogService.log("closing")
    sleep(3)
    ProgramStop.set("Program closed")

def adminActions():
    try:
        while True:
            msg = input()
            if msg == "exit":
                ProgramStop.set("Admin action: exit")
            elif msg == "clear":
                os.system("cls")
    except:
        ProgramStop.set("Admin action error")

if __name__ == "__main__":
    threading.Thread(target=LogService.service, daemon=True).start()
    threading.Thread(target=NewLocProcessing.processNewLocation, daemon=True).start()
    threading.Thread(target=WebSocketServer().start, daemon=True).start()
    threading.Thread(target=TcpServer().start, daemon=True).start()
    threading.Thread(target=adminActions, daemon=True).start()
    
    msg = None
    try:
        while True:
            msg = ProgramStop.STOP_MESSAGE.get()
            if msg is None:
                continue
            print(f"{datetime.now()}|{msg}")
            break
    finally:
        ProgramStop.set("Main thread interrupted")
