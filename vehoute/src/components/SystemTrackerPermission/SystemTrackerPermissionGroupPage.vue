<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            :mostrarAdicionar="true"
            @voltar="this.$router.back()"
            @pesquisa="fetchGruposRastreador"
            @adicionar="showAddGroupModal = true"
        />

        <AddGroup v-if="showAddGroupModal"
            @close="showAddGroupModal = false"
            @newGroup="fetchGruposRastreador"
        />  

        <AddRastreadorGrupo v-if="showAddRastreadorGrupoModal"
            :groupId="selectedGroupId"
            :groupName="selectedGroupName"
            @close="showAddRastreadorGrupoModal = false"
            @newTracker="fetchGruposRastreador"
        />

        <div class="tabela table-page">
            <div class="table-wrap">
            <table class="data-table has-actions">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="grupo in grupos" :key="grupo.id">
                        <tr class="grupo-row group-row">
                            <td>{{ grupo.id }}</td>
                            <td>{{ grupo.nome }}</td>
                            <td>
                                <button class="action-button btn-secondary btn-sm" title="Adicionar rastreador ao grupo" aria-label="Adicionar rastreador ao grupo" @click="showAddRastreadorGrupoModal = true; selectedGroupId = grupo.id; selectedGroupName = grupo.nome">+</button>
                                <button class="action-button red-button btn-sm" title="Excluir grupo" aria-label="Excluir grupo" @click="deleteGrupo(grupo)">Excluir</button>
                            </td>
                        </tr>
                        <tr class="rastreador-row" v-if="!grupo.rastreadores || grupo.rastreadores.length === 0">
                            <td></td>
                            <td colspan="2">Nenhum rastreador vinculado.</td>
                        </tr>
                        <tr class="rastreador-row" v-for="rastreador in grupo.rastreadores || []" :key="rastreador.id">
                            <td>{{ rastreador.id }}</td>
                            <td>{{ rastreador.nome }}</td>
                            <td><button class="action-button red-button btn-sm" title="Remover rastreador do grupo" aria-label="Remover rastreador do grupo" @click="removerRastreadorDoGrupo(rastreador, grupo)">−</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
</template>

<script>
import SystemTrackerPermissionGroup from '../../scripts/SystemTrackerPermissionPage/SystemTrackerPermissionGroup';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import AddGroup from './Modals/AddGroup.vue';
import AddRastreadorGrupo from './Modals/AddRastreadorGrupo.vue';

export default {
    name: 'SystemTrackerPermissionGroupPage',
    data() {
        return {
            STPG: SystemTrackerPermissionGroup,
            PagesRoutes,

            showAddGroupModal: false,
            showAddRastreadorGrupoModal: false,
            selectedGroupId: null,
            selectedGroupName: '',

            last_searched_name: '%',
            grupos: [],
        };
    },
    methods: {
        async fetchGruposRastreador(nome = '') {
            if (nome === '') {
                nome = this.last_searched_name;
            } else {
                this.last_searched_name = nome;
            }
            this.showAddGroupModal = false;
            this.showAddRastreadorGrupoModal = false;
            this.grupos = await this.STPG.getGrupos(nome);
        },
        async deleteGrupo(grupo) {
            if (!confirm(`Tem certeza que deseja excluir o grupo "${grupo.id} - ${grupo.nome}"?`)) {
                return;
            }

            await this.STPG.deleteGrupo(grupo.id);
            await this.fetchGruposRastreador();
        },
        async removerRastreadorDoGrupo(rastreador, grupo) {
            if (!confirm(`Tem certeza que deseja remover o rastreador "${rastreador.id} - ${rastreador.nome}" do grupo "${grupo.id} - ${grupo.nome}"?`)) {
                return;
            }

            await this.STPG.removerRastreadorDoGrupo(rastreador.id, grupo.id);
            await this.fetchGruposRastreador();
        }
    },
    components: {
        HeaderTelas,
        AddGroup,
        AddRastreadorGrupo
    },
};
</script>

<style scoped>
.rastreador-row { color: var(--color-text-muted); }
.data-table .rastreador-row td:nth-child(2) { padding-left: var(--space-6); }
.action-button { margin-left: var(--space-1); }
</style>
