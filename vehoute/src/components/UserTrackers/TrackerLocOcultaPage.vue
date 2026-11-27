<template>
    <HeaderTelas
        :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName + ' ' + (TrackerLocOculta.tracker?.ur_nome || '')"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchGet"
        @adicionar="handleAdicionar"
    />
    <GenericModalWindow v-if="showAddForm" @close="fecharModalAddNew">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="salvarNew">
                <h2>Nova Localização Oculta</h2>
                <p>Digite o intervalo de IDs e datas para ocultar localizações.</p>
                <input v-model="newElemento.identificacao" type="text" placeholder="Identificação"  />
                <input v-model="newElemento.id_inicial" type="text" placeholder="ID Inicial"  />
                <input v-model="newElemento.id_final" type="text" placeholder="ID Final"  />
                <input v-model="newElemento.data_inicial" type="datetime-local" placeholder="Data Inicial" step="1" />
                <input v-model="newElemento.data_final" type="datetime-local" placeholder="Data Final" step="1" />
                <div class="modal-actions">
                    <button type="submit">Adicionar</button>
                    <button type="button" class="btn-secondary" @click="fecharModalAddNew">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>
    
    <GenericModalWindow v-if="OuvinteDaLocOculta.show" @close="OuvinteDaLocOculta.close()">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="OuvinteDaLocOculta.setOuvintes()">
                <h2>Inserir Ouvinte nas localizações ocultas: {{ OuvinteDaLocOculta.locsOcultaSelected() }}</h2>
                <p>Selecione os ouvintes que devem ser adicionados às localizações ocultas.</p>
                <div class="selection-actions">
                    <button class="btn-secondary btn-sm" @click="OuvinteDaLocOculta.selecionarTodos()" type="button">Todos</button>
                    <button class="btn-secondary btn-sm" @click="OuvinteDaLocOculta.selecionarNenhum()" type="button">Nenhum</button>
                    <button class="btn-secondary btn-sm" @click="OuvinteDaLocOculta.inverterSelecao()" type="button">Inverter</button>
                </div>
                <div class="selection-item" v-for="(ouvinte, index) in OuvinteDaLocOculta.ouvintesDisponiveis" :key="index">
                    <input type="checkbox" :id="'ouvinte-' + ouvinte.ur_id" v-model="ouvinte.selected" :value="'ouvinte-' + ouvinte.ur_id">
                    <label :for="'ouvinte-' + ouvinte.ur_id">{{ ouvinte.u_id }} - {{ ouvinte.u_nome }}</label>
                </div>
                <div class="modal-actions">
                    <button type="submit">Salvar</button>
                    <button type="button" class="btn-secondary" @click="OuvinteDaLocOculta.close()">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>

    <div class="elementos-page-content">
        <!--
        <div class="loc-oculta-checksome">
             <button>[ ]</button> <p>Ouvintes:</p> <button>Remover</button>
        </div>
        <div class="loc-oculta-checksome">
             <button>[ ]</button> <p>Localizacoes:</p> <button>Excluir</button> <button>Novos Ouvintes</button> <button>Expandir</button>
        </div>
        -->

        <table class="loc-oculta-table">
            <thead>
                <tr>
                    <th>Filtros</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(loc, index) in localizacoes_ocultas" :key="index">
                    <td class="loc-oculta-td">
                        <div class="loc-oculta-td-content">
                            <div class="loc-oculta-td-title">
                                <div class="loc-oculta-td-identificacao">
                                    <input type="text" v-model="loc.v_model_identificacao" placeholder="Identificação">
                                    <button class="btn-sm" v-if="compararVariaveis(loc.v_model_identificacao, loc.identificacao) !== 0"
                                        @click="salvarEdicao(loc, 'ident')">Salvar</button>
                                </div>
                                <div class="loc-oculta-td-actions">
                                    <button class="red-button btn-sm" title="Excluir" aria-label="Excluir" @click="deletarLocOculta([loc.id])">×</button>
                                    <button class="btn-secondary btn-sm" @click="loc.mostrando_ouvintes = !loc.mostrando_ouvintes"> {{  loc.mostrando_ouvintes ? 'Ocultar' : "Ouvintes (" + loc.ouvintes.length + ")"  }}</button>
                                </div>
                            </div>
                            <div class="loc-oculta-td-filter">
                                <div class="loc-oculta-td-filter-datas">
                                    <div>
                                        <p>IDs:</p>
                                        <input type="number" placeholder="ID Inicial" v-model="loc.v_model_id_inicial">
                                        <input type="number" placeholder="ID Final" v-model="loc.v_model_id_final">
                                        <button class="btn-sm" v-if="compararVariaveis(loc.v_model_id_inicial, loc.id_inicial) !== 0 || compararVariaveis(loc.v_model_id_final, loc.id_final) !== 0"
                                        @click="salvarEdicao(loc, 'id')">Salvar</button>
                                    </div>
                                    <div>
                                        <p>Datas:</p>
                                        <input type="datetime-local" placeholder="Data Inicial" step="1" v-model="loc.v_model_data_inicial">
                                        <input type="datetime-local" placeholder="Data Final" step="1" v-model="loc.v_model_data_final">
                                        <button class="btn-sm" v-if="compararDatas(loc.v_model_data_inicial, loc.data_inicial) !== 0 || compararDatas(loc.v_model_data_final, loc.data_final) !== 0"
                                        @click="salvarEdicao(loc, 'data')">Salvar</button>
                                    </div>
                                </div>
                                <div class="loc-oculta-td-filter-actions">
                                    <p>Aplica a Novos Ouvintes?: </p>
                                    <SwitchButtonInput
                                        :value="loc.novos_ouvintes"
                                        :label="loc.novos_ouvintes ? 'Sim' : 'Não'"
                                        @update:value="salvarEdicao(loc, 'novo_ouvinte')"
                                    />
                                </div>
                            </div>
                            <div class="loc-oculta-td-ouvintes" v-if="loc.mostrando_ouvintes">
                                <div>
                                    Ouvintes:
                                    <button class="btn-secondary btn-sm" @click="OuvinteDaLocOculta.open([loc])">Editar</button>
                                </div>
                                <div v-for="(ouvinte, idx) in loc.ouvintes" :key="idx">
                                    <button class="red-button btn-sm" title="Remover ouvinte" aria-label="Remover ouvinte" @click="OuvinteDaLocOculta.excludeMe(loc, ouvinte)">×</button>
                                    <p>{{ ouvinte.u_id }} - {{ ouvinte.u_nome }}</p>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

</template>

<script>
import { confirmar } from '../../scripts/confirmacao';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import TrackerLocOculta from '../../scripts/UserTracker/TrackerLocOculta.js';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import { compararDatas, compararVariaveis } from '../../scripts/utils.js';
import SwitchButtonInput from '../utils/SwitchButtonInput.vue';


export default {
    name: 'TrackerLocOcultaPage',
    data() {
        const comp = this;
        return {
            PagesRoutes,
            TrackerLocOculta,
            compararDatas,
            compararVariaveis,
            showAddForm: false,
            newElemento: {
                identificacao: null,
                id_inicial: null,
                id_final: null,
                data_inicial: null,
                data_final: null,
            },
            localizacoes_ocultas: [],

            OuvinteDaLocOculta: {
                show: false,
                locsOcultasSelecionadas: [],
                ouvintesDisponiveis: [],
                async open(locsOcultasSelecionadas) {
                    this.show = true;
                    this.locsOcultasSelecionadas = locsOcultasSelecionadas;

                    // recebe um array do tipo: [{ur_id: 1, u_name: 'Ouvinte 1'}]
                    this.ouvintesDisponiveis = await TrackerLocOculta.getOuvintesDisponiveis();
                    
                    this.ouvintesDisponiveis.forEach(ouvinte => {
                        ouvinte.selected = false;
                    });

                    this.locsOcultasSelecionadas.forEach(loc => {
                        loc.ouvintes.forEach(ouvinte => {
                            let ouvinteDisponivel = this.ouvintesDisponiveis.find(o => o.u_id === ouvinte.u_id);
                            if (ouvinteDisponivel) {
                                ouvinteDisponivel.selected = true;
                            }
                        });
                    });
                },
                close() {
                    this.show = false;
                    this.locsOcultasSelecionadas = [];
                    this.ouvintesDisponiveis = [];
                },
                locsOcultaSelected() {
                    return this.locsOcultasSelecionadas.map(loc => loc.identificacao).join(', ');
                },
                async setOuvintes() {
                    const locsOcultasIds = this.locsOcultasSelecionadas.map(loc => loc.id);
                    const ouvintesSelecionados = this.ouvintesDisponiveis.filter(ouvinte => ouvinte.selected).map(ouvinte => ouvinte.ur_id);
                    const ouvintes = await TrackerLocOculta.setOuvintes(locsOcultasIds, ouvintesSelecionados);
                    if (ouvintes && Array.isArray(ouvintes)) {
                        comp.localizacoes_ocultas.forEach(loc => {
                            if (locsOcultasIds.includes(loc.id)) {
                                loc.ouvintes = ouvintes;
                            }
                        });
                    }
                    this.close();
                },
                async excludeMe(loc, ouvinte) {
                    this.locsOcultasSelecionadas = [loc];
                    this.ouvintesDisponiveis = loc.ouvintes.map(o => ({
                        ur_id: o.ur_id,
                        selected: o.ur_id === ouvinte.ur_id ? false : true
                    }));
                    this.setOuvintes();
                },
                selecionarTodos() {
                    this.ouvintesDisponiveis.forEach(ouvinte => {
                        ouvinte.selected = true;
                    });
                },
                selecionarNenhum() {
                    this.ouvintesDisponiveis.forEach(ouvinte => {
                        ouvinte.selected = false;
                    });
                },
                inverterSelecao() {
                    this.ouvintesDisponiveis.forEach(ouvinte => {
                        ouvinte.selected = !ouvinte.selected;
                    });
                }
            }
        }
    },
    mounted() {
        this.fetchGet();
    },
    methods: {
        async fetchGet(search = '') {
            const _locs_ocultas = await TrackerLocOculta.getLocsOcultas(search);

            //insere vmodels para edição inline
            this.localizacoes_ocultas = _locs_ocultas.map(loc => ({
                ...loc,
                v_model_identificacao: loc.identificacao,
                v_model_id_inicial: loc.id_inicial,
                v_model_id_final: loc.id_final,
                v_model_data_inicial: loc.data_inicial,
                v_model_data_final: loc.data_final
            }));
        },
        insertOrUpdateExisting(loc) {
            const localizacao = this.localizacoes_ocultas.find(l => l.id === loc.id);
            if (localizacao) {
                Object.assign(localizacao, loc);
            } else {
                loc = {
                    ...loc,
                    v_model_identificacao: loc.identificacao,
                    v_model_id_inicial: loc.id_inicial,
                    v_model_id_final: loc.id_final,
                    v_model_data_inicial: loc.data_inicial,
                    v_model_data_final: loc.data_final
                };
                this.localizacoes_ocultas.push(loc);
            }
        },
        async handleAdicionar() {
            this.showAddForm = true;
            this.newElemento = {
                nome: '',
                usuario_id_destino: null,
            };
        },
        fecharModalAddNew() {
            this.showAddForm = false;
            this.newElemento = {
                nome: '',
                usuario_id_destino: null,
            };
        },
        async salvarNew() {
            const createdLocOculta = await TrackerLocOculta.criarLocOculta(
                this.newElemento.id_inicial,
                this.newElemento.id_final,
                this.newElemento.data_inicial,
                this.newElemento.data_final,
                this.newElemento.identificacao
            );
            this.fecharModalAddNew();
            if (createdLocOculta) {
                this.insertOrUpdateExisting(createdLocOculta);
            }
        },
        async salvarEdicao(loc, caracteristica = null) {
            let array_caracteristicas_novosvalores = {};

            if (caracteristica) {
                switch (caracteristica) {
                    case 'ident':
                        array_caracteristicas_novosvalores = { identificacao: loc.v_model_identificacao };
                        break;
                    case 'id':
                        array_caracteristicas_novosvalores = {
                            id_inicial: loc.v_model_id_inicial,
                            id_final: loc.v_model_id_final
                        };
                        break;
                    case 'data':
                        array_caracteristicas_novosvalores = {
                            data_inicial: loc.v_model_data_inicial,
                            data_final: loc.v_model_data_final
                        };
                        break;
                    case 'novo_ouvinte':
                        array_caracteristicas_novosvalores = {
                            novos_ouvintes: !loc.novos_ouvintes
                        };
                        break;
                    default:
                        console.warn('Característica desconhecida para salvarEdicao:', caracteristica);
                }
            } else {
                array_caracteristicas_novosvalores = {
                    id_inicial: loc.v_model_id_inicial,
                    id_final: loc.v_model_id_final,
                    data_inicial: loc.v_model_data_inicial,
                    data_final: loc.v_model_data_final,
                    identificacao: loc.v_model_identificacao
                };
            }

            const updatedLocOculta = await TrackerLocOculta.atualizarLocOculta(
                loc.id,
                array_caracteristicas_novosvalores
            );
            if (updatedLocOculta) {
                this.insertOrUpdateExisting(updatedLocOculta);
            }
        },
        async deletarLocOculta(locs_ids) {
            if (!await confirmar({ titulo: 'Excluir localizações ocultas', mensagem: 'Excluir as localizações ocultas: ' + locs_ids.join(', ') + '?', textoConfirmar: 'Excluir', perigo: true })) {
                return;
            }

            const success = await TrackerLocOculta.deletarLocOculta(locs_ids);
            if (success) {
                this.localizacoes_ocultas = this.localizacoes_ocultas.filter(l => locs_ids.includes(l.id) === false);
            }
        },
    },
    components: {
        HeaderTelas,
        GenericModalWindow,
        SwitchButtonInput
    }
}

</script>

<style scoped>
.selection-actions { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.selection-item { display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-sm); }

.elementos-page-content { width: 100%; padding: var(--space-5); }
.loc-oculta-table,
.loc-oculta-table tbody,
.loc-oculta-table tr,
.loc-oculta-table td { display: block; width: 100%; }
.loc-oculta-table thead { display: none; }
.loc-oculta-td { padding-bottom: var(--space-3); }
.loc-oculta-td-content {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}
.loc-oculta-td-title,
.loc-oculta-td-filter { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-3); }
.loc-oculta-td-identificacao,
.loc-oculta-td-actions,
.loc-oculta-td-filter-actions { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); }
.loc-oculta-td-identificacao > input { font-size: var(--font-md); font-weight: var(--weight-bold); }
.loc-oculta-td-filter-datas { display: flex; flex-direction: column; gap: var(--space-2); min-width: 0; }
.loc-oculta-td-filter-datas > div { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); }
.loc-oculta-td-filter-datas p { min-width: 3.5rem; color: var(--color-text-muted); font-size: var(--font-sm); }
.loc-oculta-td-filter-datas input { min-height: var(--control-height-sm); }
.loc-oculta-td-filter-actions { font-size: var(--font-sm); }
.loc-oculta-td-ouvintes { display: flex; flex-direction: column; gap: var(--space-2); padding-top: var(--space-3); border-top: 1px solid var(--color-border); font-size: var(--font-sm); }
.loc-oculta-td-ouvintes > div { display: flex; align-items: center; gap: var(--space-2); }

@media (max-width: 520px) {
    .elementos-page-content { padding: var(--space-3); }
}
</style>
