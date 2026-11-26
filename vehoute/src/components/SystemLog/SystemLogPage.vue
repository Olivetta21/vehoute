<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <section class="filtros-log filter-bar" aria-label="Filtros de logs" v-if="is_searching">
            <label class="campo-filtro filter-field">
                <span>Título</span>
                <SelectInput
                    :searchFn="getSimpleTitulos"
                    @update:value="titulo = $event?.nome"
                />
            </label>

            <label class="campo-filtro filter-field">
                <span>Mensagem</span>
                <input type="text" v-model="mensagem" placeholder="Texto da mensagem">
            </label>

            <label class="campo-filtro filter-field">
                <span>IP</span>
                <input type="text" v-model="ip" placeholder="Endereço IP">
            </label>

            <label class="campo-filtro filter-field">
                <span>De</span>
                <input type="date" v-model="data_inicio">
            </label>

            <label class="campo-filtro filter-field">
                <span>Até</span>
                <input type="date" v-model="data_fim">
            </label>

            <button class="botao-buscar" @click="fetchLogs(titulo, mensagem, ip, data_inicio, data_fim)">
                Buscar logs
            </button>
            <button class="botao-buscar btn-secondary" @click="is_searching = !is_searching">
                cancelar
            </button>
        </section>
        <section class="filtros-log filter-bar" aria-label="Filtros de logs" v-else>
            <button class="btn-secondary" @click="is_searching = !is_searching">
                Filtrar logs
            </button>
        </section>

        <div class="tabela table-page" v-if="logs?.length > 0">
            <div class="table-wrap">
            <table class="data-table records">
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
.mensagem-log,
.url-log,
.ip-log { white-space: nowrap; color: var(--color-text-muted); }
.titulo-log { display: inline-block; padding: 0 var(--space-2); color: var(--color-primary); font-size: var(--font-xs); font-weight: var(--weight-bold); background: var(--color-primary-soft); border-radius: var(--radius-pill); }
</style>
