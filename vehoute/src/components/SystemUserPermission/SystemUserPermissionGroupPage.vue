<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            :mostrarAdicionar="true"
            @voltar="this.$router.back()"
            @pesquisa="fetchGruposUsuario"
            @adicionar="showAddGroupModal = true"
        />

        <AddGroup v-if="showAddGroupModal"
            @close="showAddGroupModal = false"
            @newGroup="fetchGruposUsuario"
        />  

        <AddUsuarioGrupo v-if="showAddUsuarioGrupoModal"
            :groupId="selectedGroupId"
            :groupName="selectedGroupName"
            @close="showAddUsuarioGrupoModal = false"
            @newUser="fetchGruposUsuario"
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
                                <button class="action-button btn-secondary btn-sm" title="Adicionar usuário ao grupo" aria-label="Adicionar usuário ao grupo" @click="showAddUsuarioGrupoModal = true; selectedGroupId = grupo.id; selectedGroupName = grupo.nome">+</button>
                                <button class="action-button red-button btn-sm" title="Excluir grupo" aria-label="Excluir grupo" @click="deleteGrupo(grupo)">Excluir</button>
                            </td>
                        </tr>
                        <tr class="usuario-row" v-if="grupo.usuarios.length === 0">
                            <td></td>
                            <td colspan="2">Nenhum usuário vinculado.</td>
                        </tr>
                        <tr class="usuario-row" v-for="usuario in grupo.usuarios" :key="usuario.id">
                            <td>{{ usuario.id }}</td>
                            <td>{{ usuario.nome }}</td>
                            <td><button class="action-button red-button btn-sm" title="Remover usuário do grupo" aria-label="Remover usuário do grupo" @click="removerUsuarioDoGrupo(usuario, grupo)">−</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
</template>

<script>
import SystemUserPermissionGroup from '../../scripts/SystemUserPermissionPage/SystemUserPermissionGroup';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import AddGroup from './Modals/AddGroup.vue';
import AddUsuarioGrupo from './Modals/AddUsuarioGrupo.vue';

export default {
    name: 'SystemUserPermissionGroupPage',
    data() {
        return {
            SUPG: SystemUserPermissionGroup,
            PagesRoutes,

            showAddGroupModal: false,
            showAddUsuarioGrupoModal: false,
            selectedGroupId: null,
            selectedGroupName: '',

            last_searched_name: '%',
            grupos: [],
        };
    },
    methods: {
        async fetchGruposUsuario(nome = '') {
            if (nome === '') {
                nome = this.last_searched_name;
            } else {
                this.last_searched_name = nome;
            }
            this.showAddGroupModal = false;
            this.showAddUsuarioGrupoModal = false;
            this.grupos = await this.SUPG.getGrupos(nome);
        },
        async deleteGrupo(grupo) {
            if (!confirm(`Tem certeza que deseja excluir o grupo "${grupo.id} - ${grupo.nome}"?`)) {
                return;
            }   

            await this.SUPG.deleteGrupo(grupo.id);
            await this.fetchGruposUsuario();
        },
        async removerUsuarioDoGrupo(usuario, grupo) {
            if (!confirm(`Tem certeza que deseja remover o usuário "${usuario.id} - ${usuario.nome}" do grupo "${grupo.id} - ${grupo.nome}"?`)) {
                return;
            }

            await this.SUPG.removerUsuarioDoGrupo(usuario.id, grupo.id);
            await this.fetchGruposUsuario();
        }
    },
    components: {
        HeaderTelas,
        AddGroup,
        AddUsuarioGrupo
    },
};
</script>

<style scoped>
.usuario-row { color: var(--color-text-muted); }
.data-table .usuario-row td:nth-child(2) { padding-left: var(--space-6); }
.action-button { margin-left: var(--space-1); }
</style>
