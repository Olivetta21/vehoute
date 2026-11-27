import threading
import os

from dotenv import load_dotenv
from psycopg_pool import ConnectionPool

from ProgramStop import ProgramStop
from LogService import LogService

load_dotenv()

DB_URL = os.getenv("DB_URL")


class DataBase:
    pool: ConnectionPool | None = None
    lock = threading.Lock()

    @staticmethod
    def setup():
        if DataBase.pool is not None:
            return

        with DataBase.lock:
            if DataBase.pool is not None:
                return

            try:
                LogService.log("[DB] Inicializando pool de conexões...")

                pool = ConnectionPool(
                    conninfo=DB_URL,
                    kwargs={"connect_timeout": 5},  # não quebra URLs que já têm "?parametros"
                    min_size=5,
                    max_size=50,
                    timeout=3,
                    open=True,
                )

                # Aguarda até que o banco esteja realmente disponível
                pool.wait()

                DataBase.pool = pool

                LogService.log("[DB] Banco conectado com sucesso.")

            except Exception as e:
                LogService.log(f"[DB] Falha ao iniciar banco: {e}")
                ProgramStop.set("[DATABASE ERROR] " + str(e))
                raise

    @staticmethod
    def get():
        if DataBase.pool is None:
            raise RuntimeError(
                "DataBase.setup() precisa ser chamado antes de DataBase.get()."
            )

        try:
            conn = DataBase.pool.connection()

            if conn is None:
                raise Exception("Pool retornou conexão nula.")

            return conn

        except Exception as e:
            LogService.log(f"[DB] Erro ao obter conexão: {e}")
            raise

    @staticmethod
    def tracker_has_permission(tracker_id, permission_id):
        with DataBase.get() as conn:
            with conn.cursor() as cur:
                cur.execute(
                    """
                    select exists (
                        select 1
                        from getPermissoesDoRastreador(%s)
                        where perm_id = %s and negado = false
                    )
                    """,
                    ([tracker_id], permission_id),
                )
                result = cur.fetchone()
                return bool(result and result[0])

    @staticmethod
    def user_has_permission(user_id, permission_id):
        with DataBase.get() as conn:
            with conn.cursor() as cur:
                cur.execute(
                    """
                    select exists (
                        select 1
                        from getPermissoesDoUsuario(%s)
                        where perm_id = %s and negado = false
                    )
                    """,
                    ([user_id], permission_id),
                )
                result = cur.fetchone()
                return bool(result and result[0])