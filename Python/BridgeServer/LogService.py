import queue
from datetime import datetime
from ProgramStop import ProgramStop

class LogService:
    MESSAGES_LOG = queue.Queue()

    @staticmethod
    def log(msg):
        LogService.MESSAGES_LOG.put(msg)

    @staticmethod
    def service():
        try:
            while True:
                msg = LogService.MESSAGES_LOG.get()
                if msg is None:
                    continue
                print(f"{datetime.now()}|{msg}")
        finally:
            ProgramStop.set("Log service")