<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <section class="filtros-log" aria-label="Filtros de logs" v-if="is_searching">
            <label class="campo-filtro">
                <span>Título</span>
                <SelectInput
                    :searchFn="getSimpleTitulos"
                    @update:value="titulo = $event?.nome"
                />
            </label>

            <label class="campo-filtro">
                <span>Mensagem</span>
                <input type="text" v-model="mensagem" placeholder="Texto da mensagem">
            </label>

            <label class="campo-filtro">
                <span>IP</span>
                <input type="text" v-model="ip" placeholder="Endereço IP">
            </label>

            <label class="campo-filtro">
                <span>De</span>
                <input type="date" v-model="data_inicio">
            </label>

            <label class="campo-filtro">
                <span>Até</span>
                <input type="date" v-model="data_fim">
            </label>

            <button class="botao-buscar" @click="fetchLogs(titulo, mensagem, ip, data_inicio, data_fim)">
                Buscar logs
            </button>
            <button class="botao-buscar" @click="is_searching = !is_searching">
                cancelar
            </button>
        </section>
        <section class="filtros-log" aria-label="Filtros de logs" v-else>
            <button @click="is_searching = !is_searching">
                Filtrar logs
            </button>
        </section>

        <div class="tabela" v-if="logs?.length > 0">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Mensagem</th>
                        <th></th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(log, index) in logs" :key="index">
                        <tr class="log-row up-line">
                            <td data-label="Título"><span class="titulo-log">{{ log.titulo }}</span></td>
                            <td colspan="2" data-label="Mensagem" class="mensagem-log" :title="log.mensagem">{{ log.mensagem_short }}</td>
                            <td data-label="IP" class="ip-log">{{ log.ip_short }}</td>
                        </tr>
                        <tr class="log-row down-line">
                            <td data-label="ID">{{ log.id }}</td>
                            <td data-label="Usuário">{{ log.u_id }} - {{ log.u_nome }}</td>
                            <td data-label="Data">{{ log.data }}</td>
                            <td data-label="URL" class="url-log" :title="log.url">{{ log.url_short }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
</template>

<script>
import SystemLog from '../../scripts/SystemLogPage/SystemLog';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import SelectInput from '../utils/SelectInput.vue';

export default {
    name: 'SystemLogPage',
    data() {
        return {
            PagesRoutes,
            SystemLog,
            is_searching: false,

            titulo: '',
            mensagem: '',
            ip: '',
            data_inicio: '',
            data_fim: '',

            logs: [],
        };
    },
    methods: {
        async fetchLogs(titulo, mensagem, ip, data_inicio, data_fim) {
            let result = await SystemLog.getLogs(titulo, mensagem, ip, data_inicio, data_fim);
            if (result.length > 0) {
                this.logs = result.map(log => ({
                    ...log,
                    mensagem_short: log.mensagem?.length > 35 ? log.mensagem.substring(0, 32) + '...' : log.mensagem,
                    url_short: log.url?.length > 35 ? log.url.substring(0, 32) + '...' : log.url,
                    ip_short: log.ip?.length > 35 ? log.ip.substring(0, 32) + '...' : log.ip,
                }));
            }
        },
        async getSimpleTitulos(filter = '%') {
            let r = await SystemLog.getSimpleTitulos(filter);
            let id_ = 1;
            r = r.map(titulo => ({ id: id_++, nome: titulo }));
            return r;
        }
    },
    components: {
        HeaderTelas,
        SelectInput
    },
};
</script>

<style scoped>
.filtros-log {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
    padding: 18px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.campo-filtro {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.campo-filtro span {
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.campo-filtro input {
    width: 100%;
    height: 2.25rem;
    padding: 0 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
    color: #1e293b;
    font: inherit;
}

.campo-filtro input:focus {
    outline: 2px solid rgba(17, 98, 230, 0.2);
    border-color: var(--colorC3);
}

.botao-buscar {
    height: 2.25rem;
    padding: 0 16px;
    white-space: nowrap;
    font-size: 0.9rem;
    max-width: fit-content;
}

.tabela {
    padding: 20px;
    overflow-x: auto;
}

table {
    border-collapse: separate;
    border-spacing: 0;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #334155;
    background: #fff;
}

th,
td {
    padding: 5px;
    text-align: left;
}

th {
    background: #f1f5f9;
    border-bottom: 1px solid #cbd5e1;
    color: #475569;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.log-row td {
    border-bottom: 1px solid #eef2f7;
}

.up-line {
    font-weight: 600;
}

.up-line:first-child td {
    border-top: 1px solid black;
}

.up-line td:first-child {
    border-left: 1px solid black;
}

.up-line td:last-child {
    border-right: 1px solid black;
}

.down-line {
    color: #64748b;
    font-size: 0.86rem;
}

.down-line td {
    border-bottom: 1px solid black;
}

.down-line td:first-child {
    border-left: 1px solid black;
}

.down-line td:last-child {
    border-right: 1px solid black;
}

.mensagem-log,
.url-log {
    overflow: hidden;
    color: #64748b;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ip-log {
    white-space: nowrap;
}

.titulo-log {
    display: inline-flex;
    align-items: center;
    padding: 4px 9px;
    border-radius: 999px;
    background: #e0ecff;
    color: #1d4ed8;
    font-size: 0.78rem;
    font-weight: 700;
}

@media (max-width: 900px) {
    .filtros-log {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 520px) {
    .filtros-log,
    .tabela {
        padding: 14px;
    }

    .filtros-log {
        grid-template-columns: 1fr;
    }

    .botao-buscar {
        width: 100%;
        max-width: none;
    }
}
</style>