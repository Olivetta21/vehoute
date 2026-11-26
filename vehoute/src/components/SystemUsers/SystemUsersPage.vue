<template>
    <HeaderTelas
        :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchUsers"
        @adicionar="alternarFormulario"
    />
    <GenericModalWindow v-if="mostrarFormulario" @close="alternarFormulario">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="criarUsuario">
                <div class="modal-heading">
                    <h2>Novo usuário</h2>
                </div>
                <input v-model="novoUsuario.nome" type="text" placeholder="Nome" required />
                <input v-model="novoUsuario.email" type="email" placeholder="Email" required />
                <input v-model="novoUsuario.identidade" type="text" placeholder="Identidade" required />
                <SelectInput
                    :searchFn="getLegalIdentTipos"
                    :searchAll="true"
                    placeholder="Tipo de identidade"
                    @update:value="novoUsuario.tipoIdent = $event?.id || null"
                />
                <SwitchButtonInput
                    :value="novoUsuario.adm"
                    label="Administrador"
                    :required="false"
                    @update:value="novoUsuario.adm = $event"
                />
                <button type="submit">Adicionar usuário</button>
                <p v-if="mensagem" class="feedback-message">{{ mensagem }}</p>
            </form>
        </div>
    </GenericModalWindow>
    <div class="user-cards-container cards-grid">
        <div class="user-card card" v-for="user in users" :key="user.id">
            <div class="user-card-content">
                <div class="user-card-stats">
                    <p class="user-id">#{{ user.id }}</p>
                    <p v-if="user.adm" class="badge is-primary">Admin</p>
                    <p class="badge" :class="user.ativo ? 'is-success' : 'is-danger'">{{ user.ativo ? 'Ativo' : 'Inativo' }}</p>
                </div>
                <div class="user-card-name">{{ user.nome }}</div>
                <div class="user-card-posses">
                    <p>Rastreadores: {{ user.qnt_posse_rastr }}</p>
                    <p>Ouvinte em: {{ user.ouvinte_qnt_rastr }}</p>
                </div>
            </div>
            <div class="user-card-footer">
                <p>{{ user.email }}</p>
                <p>{{ user.telefone }}</p>
                <p>{{ user.identidade }}</p>
                <button type="button" class="btn-secondary btn-sm" @click="toggleAtivo(user.id)">{{ user.ativo ? 'Desativar' : 'Ativar' }}</button>
                <button type="button" class="btn-secondary btn-sm" @click="toggleAdm(user.id)">{{ user.adm ? 'Remover admin' : 'Tornar admin' }}</button>
            </div>
            
        </div>
    </div>
</template>

<script>
import SystemUser from '../../scripts/SystemUserPage/SystemUser';
import CadastroUsuario from '../../scripts/CadastroPage/CadastroUsuario';
import HeaderTelas from '../utils/HeaderTelas.vue';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import SelectInput from '../utils/SelectInput.vue';
import SwitchButtonInput from '../utils/SwitchButtonInput.vue';

export default {
    name: 'SystemUsersPage',
    data() {
        return {
            users: [],
            PagesRoutes,
            termoPesquisa: '',
            mostrarFormulario: false,
            mensagem: '',
            novoUsuario: {
                nome: '',
                email: '',
                identidade: '',
                tipoIdent: null,
                adm: false
            }
        }
    },
    mounted() {
        this.fetchUsers();
    },
    methods: {
        alternarFormulario() {
            this.mostrarFormulario = !this.mostrarFormulario;
            this.mensagem = '';
        },
        async fetchUsers(pesquisa = '%') {
            this.termoPesquisa = pesquisa ?? '';
            this.users = await SystemUser.getUsuarios(this.termoPesquisa);
        },
        async getLegalIdentTipos() {
            return await CadastroUsuario.getLegalIdentTipos();
        },
        async toggleAtivo(id) {
            const response = await SystemUser.toggleAtivo(id);
            this.mensagem = response?.success ? 'Status do usuário atualizado.' : response?.error ?? 'Não foi possível atualizar o status.';
            await this.fetchUsers(this.termoPesquisa);
        },
        async toggleAdm(id) {
            const response = await SystemUser.toggleAdm(id);
            this.mensagem = response?.success ? 'Permissão de administrador atualizada.' : response?.error ?? 'Não foi possível atualizar a permissão.';
            await this.fetchUsers(this.termoPesquisa);
        },
        async criarUsuario() {
            const response = await SystemUser.addUsuario(
                this.novoUsuario.nome,
                this.novoUsuario.email,
                this.novoUsuario.identidade,
                this.novoUsuario.tipoIdent,
                this.novoUsuario.adm
            );

            if (response?.success) {
                this.mensagem = `Usuário criado. Login: ${response.credenciais.login}`;
                this.novoUsuario = {
                    nome: '',
                    email: '',
                    identidade: '',
                    tipoIdent: null,
                    adm: false
                };
                this.mostrarFormulario = false;
                await this.fetchUsers(this.termoPesquisa);
                return;
            }

            this.mensagem = response?.error ?? 'Não foi possível criar o usuário.';
        }
    },
    components: {
        HeaderTelas,
        GenericModalWindow,
        SelectInput,
        SwitchButtonInput
    }
}

</script>

<style scoped>
.user-card { display: flex; flex-direction: column; align-self: stretch; gap: var(--space-3); padding: var(--space-4); }
.user-card-content { display: flex; flex-direction: column; gap: var(--space-2); }
.user-card-stats { display: flex; align-items: center; gap: var(--space-2); }
.user-id { margin-right: auto; color: var(--color-text-subtle); font-size: var(--font-xs); font-weight: var(--weight-bold); }
.user-card-name { overflow: hidden; font-size: var(--font-lg); font-weight: var(--weight-bold); text-overflow: ellipsis; white-space: nowrap; }
.user-card-posses { display: flex; flex-wrap: wrap; gap: var(--space-4); color: var(--color-text-muted); font-size: var(--font-sm); }
.user-card-footer { display: flex; flex-wrap: wrap; gap: var(--space-1) var(--space-2); margin-top: auto; padding-top: var(--space-3); color: var(--color-text-muted); font-size: var(--font-sm); border-top: 1px solid var(--color-border); }
.user-card-footer p { width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-card-footer button { flex: 1; margin-top: var(--space-2); }
</style>
