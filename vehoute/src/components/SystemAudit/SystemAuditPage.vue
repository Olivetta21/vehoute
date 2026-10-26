<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <section class="filtros-auditoria" aria-label="Filtros de auditoria" v-if="is_searching">
            <label class="campo-filtro">
                <span>Tabela</span>
                <SelectInput
                    :searchFn="getSimpleTabelas"
                    @update:value="tabela = $event?.nome"
                />
            </label>

            <label class="campo-filtro">
                <span>Texto</span>
                <input type="text" v-model="filtro" placeholder="Ação ou conteúdo">
            </label>

            <label class="campo-filtro">
                <span>De</span>
                <input type="date" v-model="data_inicio">
            </label>

            <label class="campo-filtro">
                <span>Até</span>
                <input type="date" v-model="data_fim">
            </label>
            
            <label class="campo-filtro">
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
            <button class="botao-buscar" @click="is_searching = !is_searching">
                cancelar
            </button>
        </section>
        <section class="filtros-auditoria" aria-label="Filtros de auditoria" v-else>
            <button @click="is_searching = !is_searching">
                Filtrar auditorias
            </button>
        </section>

        <!-- "id"	"usuario_id"	"tabela"	"acao"	"antes"	"depois"	"sucesso"	"data" -->

        <div class="tabela" v-if="auditorias?.length > 0">
            <table>
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
.filtros-auditoria {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
    padding: 18px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.filtro-tabela,
.campo-filtro {
    min-width: 0;
}

.campo-filtro {
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

.auditoria-row td {
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


.valor-auditoria {
    overflow: hidden;
    color: #64748b;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.data-auditoria {
    white-space: nowrap;
}

.acao,
.resultado {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}

.acao {
    padding: 4px 9px;
    background: #e0ecff;
    color: #1d4ed8;
}

.resultado {
    padding: 3px 8px;
}

.resultado.sucesso {
    background: #dcfce7;
    color: #166534;
}

.resultado.falha {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 900px) {
    .filtros-auditoria {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filtro-tabela,
    .botao-buscar {
        grid-column: span 2;
    }
}

@media (max-width: 520px) {
    .filtros-auditoria,
    .tabela {
        padding: 14px;
    }

    .filtros-auditoria {
        grid-template-columns: 1fr;
    }

    .filtro-tabela,
    .botao-buscar {
        grid-column: auto;
    }

    .botao-buscar {
        width: 100%;
    }
}
</style>