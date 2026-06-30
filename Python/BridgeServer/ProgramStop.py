import queue

class ProgramStop:
    STOP_MESSAGE = queue.Queue()

    @staticmethod
    def set(msg):
        ProgramStop.STOP_MESSAGE.put(msg)
