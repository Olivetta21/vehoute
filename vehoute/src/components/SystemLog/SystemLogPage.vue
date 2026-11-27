<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <section class="filtros-log filter-bar" aria-label="Filtros de logs" v-if="is_searching">
            <label class="campo-filtro filter-field">
                <span>Código do erro</span>
                <input type="number" min="1" v-model="id_erro" placeholder="Informado ao suporte" @keyup.enter="fetchLogs()">
            </label>

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

            <button class="botao-buscar" @click="fetchLogs()">
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
                    <template v-for="log in logs" :key="log.id">
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
        <p v-if="buscou && !logs.length" class="rodape-lista">Nenhum registro encontrado.</p>
        <div v-if="logs.length" class="rodape-lista">
            <button v-if="tem_mais" type="button" class="btn-secondary" :disabled="carregando" @click="fetchLogs(true)">
                Carregar mais
            </button>
            <button type="button" class="btn-secondary" @click="exportar">Exportar CSV</button>
        </div>
</template>

<script>
import { baixarCsv, getDateWithOffset } from '../../scripts/utils';
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

            id_erro: '',
            titulo: '',
            mensagem: '',
            ip: '',
            data_inicio: '',
            data_fim: '',

            logs: [],
            tem_mais: false,
            buscou: false,
            carregando: false,
        };
    },
    methods: {
        async fetchLogs(acrescentar = false) {
            if (this.carregando) return;
            this.carregando = true;
            try {
                const offset = acrescentar ? this.logs.length : 0;
                const result = await SystemLog.getLogs(this.titulo, this.mensagem, this.ip, this.data_inicio, this.data_fim, offset, Number(this.id_erro) || null);
                if (!result) return;
                const encurtar = (t) => t?.length > 35 ? t.substring(0, 32) + '...' : t;
                const novos = result.itens.map(log => ({
                    ...log,
                    mensagem_short: encurtar(log.mensagem),
                    url_short: encurtar(log.url),
                    ip_short: encurtar(log.ip),
                }));
                this.logs = acrescentar ? [...this.logs, ...novos] : novos;
                this.tem_mais = result.tem_mais;
                this.buscou = true;
            } finally {
                this.carregando = false;
            }
        },
        exportar() {
            const linhas = this.logs.map((item) => Object.fromEntries(
                Object.entries(item).filter(([chave]) => !chave.endsWith('_short'))
            ));
            baixarCsv('logs-' + getDateWithOffset(0) + '.csv', linhas);
        },
        async getSimpleTitulos(filter = '%') {
            let r = await SystemLog.getSimpleTitulos(filter);
            let id_ = 1;
            r = r.map(titulo => ({ id: id_++, nome: titulo }));
            return r;
        }
    },
    mounted() {
        this.fetchLogs();
    },
    components: {
        HeaderTelas,
        SelectInput
    },
};
</script>

<style scoped>
.rodape-lista { display: flex; justify-content: center; gap: var(--space-2); padding: var(--space-4); }
.mensagem-log,
.url-log,
.ip-log { white-space: nowrap; color: var(--color-text-muted); }
.titulo-log { display: inline-block; padding: 0 var(--space-2); color: var(--color-primary); font-size: var(--font-xs); font-weight: var(--weight-bold); background: var(--color-primary-soft); border-radius: var(--radius-pill); }
</style>
