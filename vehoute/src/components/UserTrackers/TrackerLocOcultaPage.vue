<template>
    <HeaderTelas
        :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName + ' ' + (TrackerLocOculta.tracker?.ur_nome || '')"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchGet"
        @adicionar="handleAdicionar"
    />
    <GenericModalWindow v-if="showAddForm" @close="fecharModalAddNew">
        <div class="add-elemento-form-container">
            <form class="add-elemento-form" @submit.prevent="salvarNew">
                <h2>Nova Localização Oculta</h2>
                <p>Digite o intervalo de IDs e datas para ocultar localizações.</p>
                <input v-model="newElemento.identificacao" type="text" placeholder="Identificação"  />
                <input v-model="newElemento.id_inicial" type="text" placeholder="ID Inicial"  />
                <input v-model="newElemento.id_final" type="text" placeholder="ID Final"  />
                <input v-model="newElemento.data_inicial" type="datetime-local" placeholder="Data Inicial" step="1" />
                <input v-model="newElemento.data_final" type="datetime-local" placeholder="Data Final" step="1" />
                <div class="add-elemento-actions">
                    <button type="submit">Adicionar</button>
                    <button type="button" @click="fecharModalAddNew">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>
    
    <GenericModalWindow v-if="OuvinteDaLocOculta.show" @close="OuvinteDaLocOculta.close()">
        <div class="add-elemento-form-container">
            <form class="add-elemento-form" @submit.prevent="OuvinteDaLocOculta.setOuvintes()">
                <h2>Inserir Ouvinte nas localizações ocultas: {{ OuvinteDaLocOculta.locsOcultaSelected() }}</h2>
                <p>Selecione os ouvintes que devem ser adicionados às localizações ocultas.</p>
                <div>
                    <button @click="OuvinteDaLocOculta.selecionarTodos()" type="button">Todos</button>
                    <button @click="OuvinteDaLocOculta.selecionarNenhum()" type="button">Nenhum</button>
                    <button @click="OuvinteDaLocOculta.inverterSelecao()" type="button">Inverter</button>
                </div>
                <div v-for="(ouvinte, index) in OuvinteDaLocOculta.ouvintesDisponiveis" :key="index">
                    <input type="checkbox" :id="'ouvinte-' + ouvinte.ur_id" v-model="ouvinte.selected" :value="'ouvinte-' + ouvinte.ur_id">
                    <label :for="'ouvinte-' + ouvinte.ur_id">{{ ouvinte.u_id }} - {{ ouvinte.u_nome }}</label>
                </div>
                <div class="add-elemento-actions">
                    <button type="submit">Salvar</button>
                    <button type="button" @click="OuvinteDaLocOculta.close()">Cancelar</button>
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
                                    <button v-if="compararVariaveis(loc.v_model_identificacao, loc.identificacao) !== 0"
                                        @click="salvarEdicao(loc, 'ident')">Salvar</button>
                                </div>
                                <div class="loc-oculta-td-actions">
                                    <button @click="deletarLocOculta([loc.id])">X</button>
                                    <button @click="loc.mostrando_ouvintes = !loc.mostrando_ouvintes"> {{  loc.mostrando_ouvintes ? 'Ocultar' : "Ouvintes (" + loc.ouvintes.length + ")"  }}</button>
                                </div>
                            </div>
                            <div class="loc-oculta-td-filter">
                                <div class="loc-oculta-td-filter-datas">
                                    <div>
                                        <p>IDs:</p>
                                        <input type="number" placeholder="ID Inicial" v-model="loc.v_model_id_inicial">
                                        <input type="number" placeholder="ID Final" v-model="loc.v_model_id_final">
                                        <button v-if="compararVariaveis(loc.v_model_id_inicial, loc.id_inicial) !== 0 || compararVariaveis(loc.v_model_id_final, loc.id_final) !== 0"
                                        @click="salvarEdicao(loc, 'id')">Salvar</button>
                                    </div>
                                    <div>
                                        <p>Datas:</p>
                                        <input type="datetime-local" placeholder="Data Inicial" step="1" v-model="loc.v_model_data_inicial">
                                        <input type="datetime-local" placeholder="Data Final" step="1" v-model="loc.v_model_data_final">
                                        <button v-if="compararDatas(loc.v_model_data_inicial, loc.data_inicial) !== 0 || compararDatas(loc.v_model_data_final, loc.data_final) !== 0"
                                        @click="salvarEdicao(loc, 'data')">Salvar</button>
                                    </div>
                                </div>
                                <div class="loc-oculta-td-filter-actions">
                                    <p>Aplica a Novos Ouvintes?: </p>
                                    <button @click="salvarEdicao(loc, 'novo_ouvinte')" > {{ loc.novos_ouvintes ? 'Sim' : 'Não' }} </button>
                                </div>
                            </div>
                            <div class="loc-oculta-td-ouvintes" v-if="loc.mostrando_ouvintes">
                                <div>
                                    Ouvintes:
                                    <button @click="OuvinteDaLocOculta.open([loc])">✏️</button>
                                </div>
                                <div v-for="(ouvinte, idx) in loc.ouvintes" :key="idx">
                                    <button @click="OuvinteDaLocOculta.excludeMe(loc, ouvinte)">X</button>
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
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import TrackerLocOculta from '../../scripts/UserTracker/TrackerLocOculta.js';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import { compararDatas, compararVariaveis } from '../../scripts/utils.js';


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
            if (!confirm('Deseja deletar localizações ocultas: ' + locs_ids.join(', '))) {
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
        GenericModalWindow
    }
}

</script>



<style scoped>
.add-elemento-form-container {
    pointer-events: none;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.add-elemento-form {
    pointer-events: all;
    background: white;
    border: 1px solid var(--colorA2);
    padding: 20px;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: min(420px, calc(100vw - 60px));
}

.add-elemento-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.elementos-page-content {
    width: 100%;
    height: 100%;
    padding: 4px;
    overflow-x: auto;
}

.loc-oculta-table {
    width: 100%;
    border-collapse: collapse;
}

.loc-oculta-checksome{
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    padding: 5px;
    width: max-content;
    gap: 10px;
}

.loc-oculta-td {
    padding-bottom: 30px;
}

.loc-oculta-td-content {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 5px;
    border-radius: 5px;
    border: 1px solid var(--colorC4);
    box-shadow: 0 0 5px #00000061;
}

.loc-oculta-td-title, .loc-oculta-td-actions, .loc-oculta-td-filter, .loc-oculta-td-filter-actions{
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
}

.loc-oculta-td-identificacao > input {
    border: none;
    border-bottom: 3px solid var(--colorA2);
    border-radius: 0;
}

.loc-oculta-td-filter-datas > div {
    display: flex;
    flex-direction: row;
    gap: 2px;
}


.loc-oculta-td-ouvintes {
    padding: 5px;
    border-top: 1px solid var(--colorA2);
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.loc-oculta-td-ouvintes > div {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 5px;
}


button {
    border: 1px solid var(--colorC4);
    color: var(--colorC4);
    background: none;
    padding: 2px;
    min-width: 2rem;
}

button:hover, button:focus {
    color: var(--colorA1);
    background: var(--colorC2);
}

</style>