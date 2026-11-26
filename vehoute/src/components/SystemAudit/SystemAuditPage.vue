<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <section class="filtros-auditoria filter-bar" aria-label="Filtros de auditoria" v-if="is_searching">
            <label class="campo-filtro filter-field">
                <span>Tabela</span>
                <SelectInput
                    :searchFn="getSimpleTabelas"
                    @update:value="tabela = $event?.nome"
                />
            </label>

            <label class="campo-filtro filter-field">
                <span>Texto</span>
                <input type="text" v-model="filtro" placeholder="Ação ou conteúdo">
            </label>

            <label class="campo-filtro filter-field">
                <span>De</span>
                <input type="date" v-model="data_inicio">
            </label>

            <label class="campo-filtro filter-field">
                <span>Até</span>
                <input type="date" v-model="data_fim">
            </label>
            
            <label class="campo-filtro filter-field">
                <span>Resultado</span>
                <SwitchButtonInput
                    :value="sucesso"
                    :label="{'true':'Sucesso','false':'Falha','null':'Todos'}[String(sucesso)]"
                    :canSwitchToNeutral="true"
                    @update:value="sucesso = $event"
                />
            </label>

            <button class="botao-buscar" @click="fetchAuditorias(tabela, filtro, data_inicio, data_fim, sucesso)">
                Buscar auditorias
            </button>
            <button class="botao-buscar btn-secondary" @click="is_searching = !is_searching">
                cancelar
            </button>
        </section>
        <section class="filtros-auditoria filter-bar" aria-label="Filtros de auditoria" v-else>
            <button class="btn-secondary" @click="is_searching = !is_searching">
                Filtrar auditorias
            </button>
        </section>

        <!-- "id"	"usuario_id"	"tabela"	"acao"	"antes"	"depois"	"sucesso"	"data" -->

        <div class="tabela table-page" v-if="auditorias?.length > 0">
            <div class="table-wrap">
            <table class="data-table records">
                <thead>
                    <tr>
                        <th>Tabela</th>
                        <th>Ação</th>
                        <th>Antes</th>
                        <th>Depois</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(auditoria, index) in auditorias" :key="index">
                        <tr class="auditoria-row up-line">
                            <td data-label="Tabela">{{ auditoria.tabela }}</td>
                            <td data-label="Ação"><span class="acao">{{ auditoria.acao }}</span></td>
                            <td data-label="Antes" class="valor-auditoria" :title="auditoria.antes">{{ auditoria.antes_short }}</td>
                            <td data-label="Depois" class="valor-auditoria" :title="auditoria.depois">{{ auditoria.depois_short }}</td>
                            <td data-label="Data" class="data-auditoria">{{ auditoria.data }}</td>
                        </tr>
                        <tr class="auditoria-row down-line">
                            <td data-label="ID">{{ auditoria.id }}</td>
                            <td></td>
                            <td data-label="Usuário">{{ auditoria.usuario_id }} - {{ auditoria.nome }}</td>
                            <td></td>
                            <td data-label="Resultado"><span :class="['resultado', auditoria.sucesso ? 'sucesso' : 'falha']">{{ auditoria.sucesso ? 'Sucesso' : 'Falha' }}</span></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>

</template>

<script>
import SystemAudit from '../../scripts/SystemAuditPage/SystemAudit';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import SelectInput from '../utils/SelectInput.vue';
import SwitchButtonInput from '../utils/SwitchButtonInput.vue';

export default {
    name: 'SystemAuditPage',
    data() {
        return {
            PagesRoutes,
            SystemAudit,
            is_searching: false,

            tabela: '',
            filtro: '',
            data_inicio: '',
            data_fim: '',
            sucesso: null,

            auditorias: [],
        };
    },
    methods: {
        async fetchAuditorias(tabela, filtro, data_inicio, data_fim, sucesso) {
            let result = await SystemAudit.getAuditorias(tabela, filtro, data_inicio, data_fim, sucesso);
            if (result.length > 0) {
                this.auditorias = result.map(auditoria => ({
                    ...auditoria,
                    antes_short: auditoria.antes?.length > 35 ? auditoria.antes.substring(0, 32) + '...' : auditoria.antes,
                    depois_short: auditoria.depois?.length > 35 ? auditoria.depois.substring(0, 32) + '...' : auditoria.depois
                }));
            }
        },
        async getSimpleTabelas(filter = '%') {
            let r = await SystemAudit.getSimpleTabelas(filter);
            let id_ = 1;
            r = r.map(tabela => ({ id: id_++, nome: tabela }));
            return r;
        }
    },
    components: {
        HeaderTelas,
        SelectInput,
        SwitchButtonInput
    },
};
</script>

<style scoped>
.valor-auditoria { color: var(--color-text-muted); white-space: nowrap; }
.data-auditoria { white-space: nowrap; }
.acao,
.resultado { display: inline-block; padding: 0 var(--space-2); font-size: var(--font-xs); font-weight: var(--weight-bold); border-radius: var(--radius-pill); }
.acao { color: var(--color-primary); background: var(--color-primary-soft); }
.resultado.sucesso { color: var(--color-success); background: var(--color-success-soft); }
.resultado.falha { color: var(--color-danger); background: var(--color-danger-soft); }
</style>
