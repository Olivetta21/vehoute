<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            :mostrarAdicionar="true"
            @voltar="this.$router.back()"
            @pesquisa="fetchPermissoesTracker"
            @adicionar="openAddVinculoPermissaoModal"
        />

        <AddVinculoPermissao v-if="showAddVinculoPermissaoModal"
            @close="closeAddVinculoPermissaoModal"
            @newVinculo="fetchPermissoesTracker"
            :preselectedRastreadorOuGrupo="preSelRouG"
            :preselectedPermissao="preSelPerm"
        />

        <div>
            <SelectInput 
                :data="agrupamento_options"
                placeholder="Selecione o agrupamento"
                @update:value="agrupamento_selecionado = $event"
            />

            <div class="tabela">
                <p class="tabela-resumo">Agrupado por: <strong>{{ ultimo_agrupado_por || 'nenhum critério selecionado' }}</strong></p>
                <table>
                    <thead>
                        <tr>
                            <th>ID do vínculo</th>
                            <th>Permissão negada</th>
                            <th>{{ ultimo_agrupado_por === 'permissao' ? 'Entidade' : 'Permissão' }}</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(entidade, index) in agrupamento" :key="index">
                            <tr class="agrupamento-titulo">
                                <td colspan="3">{{ {'null': 'Permissão', 'true': 'Rastreador', 'false': 'Grupo'}[String(entidade.is_tracker)] }} {{ entidade.id }}: {{ entidade.nome }}</td>
                                <td>
                                    <button v-if="ultimo_agrupado_por === 'permissao'"
                                        @click="openAddVinculoPermissaoModal(null, entidade)"
                                        title="Adicionar entidade" aria-label="Adicionar entidade">+
                                    </button>
                                    <button v-else-if="ultimo_agrupado_por === 'entidade' || ultimo_agrupado_por === 'rastreador'"
                                        @click="openAddVinculoPermissaoModal(entidade, null)"
                                        title="Adicionar permissão" aria-label="Adicionar permissão">+
                                    </button>
                                    <button v-else title="Ação indisponível" aria-label="Ação indisponível">—</button>
                                </td>
                            </tr>
                            <tr class="agrupamento-perms" v-for="(permissao, pIndex) in entidade.permissoes" :key="pIndex">
                                <td>{{ (ultimo_agrupado_por === 'rastreador' && !permissao.is_tracker ? 'G' + permissao.x_id + ' ' : ' ') + permissao.vpr_id }}</td>
                                <td>{{ permissao.vpr_negado }}</td>
                                <td>{{ ultimo_agrupado_por === 'permissao'
                                        ? (permissao.is_tracker ? 'Rastreador ' : 'Grupo ') + permissao.x_id + ': ' + permissao.x_nome
                                        : permissao.pr_id + ' - ' + permissao.pr_nome }}</td>
                                <td>
                                    <button class="red-button" v-if="ultimo_agrupado_por === 'permissao' || ultimo_agrupado_por === 'entidade' || permissao.is_tracker"
                                        @click="removeVinculoPermissao(permissao)"
                                        title="Remover vínculo" aria-label="Remover vínculo">×
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
</template>

<script>
import SystemTrackerPermission from '../../scripts/SystemTrackerPermissionPage/SystemTrackerPermission';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import AddVinculoPermissao from './Modals/AddVinculoPermissao.vue';
import SelectInput from '../utils/SelectInput.vue';
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';

export default {
    name: 'SystemTrackerPermissionPage',
    data() {
        return {
            PagesRoutes,
            agrupamento: [],
            ultimo_agrupado_por: null,
            showAddVinculoPermissaoModal: false,
            last_searched_filter: '',
            agrupamento_options: [{'id': 1, 'nome': 'Permissão', 'x': 'permissao'}, {'id': 2, 'nome': 'Entidade', 'x': 'entidade'}, {'id': 3, 'nome': 'Rastreador', 'x': 'rastreador'}],
            agrupamento_selecionado: null,

            preSelRouG: null,
            preSelPerm: null,
            STP: SystemTrackerPermission
        };
    },
    methods: {
        async fetchPermissoesTracker(filter = '') {
            if (filter === '') {
                filter = this.last_searched_filter;
            } else {
                this.last_searched_filter = filter;
            }

            if (!this.agrupamento_selecionado) {
                ToastMessage.add('Por favor, selecione um agrupamento antes de buscar permissões.', '', 'error', true);
                return;
            }

            this.closeAddVinculoPermissaoModal();
            this.agrupamento = await this.STP.getPermissoesTracker(this.agrupamento_selecionado.x, filter);
            this.ultimo_agrupado_por = this.agrupamento_selecionado.x;
        },
        openAddVinculoPermissaoModal(preselectedTrackerOuGrupo = null, preselectedPermissao = null) {
            this.preSelRouG = preselectedTrackerOuGrupo;
            this.preSelPerm = preselectedPermissao;
            if (this.preSelRouG) this.preSelRouG.nome = `${this.preSelRouG.id}: ${this.preSelRouG.nome}`;
            if (this.preSelPerm) this.preSelPerm.nome = `${this.preSelPerm.id}: ${this.preSelPerm.nome}`;
            this.showAddVinculoPermissaoModal = true;
        },
        closeAddVinculoPermissaoModal() {
            this.showAddVinculoPermissaoModal = false;
            this.preSelRouG = null;
            this.preSelPerm = null;
        },
        async removeVinculoPermissao(permissao) {
            if (!confirm(`Tem certeza que deseja remover este vínculo de permissão?\n\nVinculo ID: ${permissao.vpr_id}\nEntidade: ${permissao.is_tracker ? 'Rastreador' : 'Grupo'} ${permissao.x_id} - ${permissao.x_nome}\nPermissão: ${permissao.pr_id} - ${permissao.pr_nome}\nNegado: ${permissao.vpr_negado}`)) {
                return;
            }

            const removed = await this.STP.removeVinculoPermissao(permissao.vpr_id, permissao.x_id, permissao.is_tracker, permissao.pr_id);
            if (removed) {
                this.fetchPermissoesTracker();
            }
        }
    },
    components: {
        AddVinculoPermissao,
        HeaderTelas,
        SelectInput
    },
};
</script>

<style scoped>
.tabela {
    padding: 12px;
    overflow-x: auto;
}

.tabela-resumo {
    margin-bottom: 10px;
    color: #666;
    font-size: 0.95rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    color: #333;
}

th,
td {
    padding: 9px 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th {
    background-color: #f5f5f5;
    color: #555;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
}

th:last-child,
td:last-child {
    width: 1%;
    white-space: nowrap;
    text-align: right;
}

.agrupamento-titulo {
    background-color: #e0e0e0;
    font-weight: bold;
}

.agrupamento-perms {
    color: #555;
}

button {
    min-width: 32px;
    padding: 5px 8px;
    font-size: 14px;
    line-height: 1;
}
</style>