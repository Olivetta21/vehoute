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
                <input :value="novoUsuario.identidade" @input="novoUsuario.identidade = mascararCampo($event, mascara)" type="text" :placeholder="mascara || 'Identidade'" required />
                <SelectInput
                    :searchFn="getLegalIdentTipos"
                    :searchAll="true"
                    placeholder="Tipo de identidade"
                    @update:value="selecionarTipo($event)"
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
    <GenericModalWindow v-if="credenciaisNovas" @close="credenciaisNovas = null">
        <div class="modal-container">
            <div class="modal-form">
                <h2>Usuário criado</h2>
                <p>Anote e entregue ao usuário. A senha não será exibida novamente.</p>
                <p>Login: <strong>{{ credenciaisNovas.login }}</strong></p>
                <p>Senha provisória: <strong>{{ credenciaisNovas.senha }}</strong></p>
                <div class="modal-actions">
                    <button type="button" @click="copiarCredenciais">Copiar</button>
                    <button type="button" class="btn-secondary" @click="credenciaisNovas = null">Fechar</button>
                </div>
            </div>
        </div>
    </GenericModalWindow>
    <p v-if="!users.length" class="empty-state">Nenhum usuário encontrado.</p>
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
                <button type="button" class="btn-secondary btn-sm" @click="toggleAtivo(user)">{{ user.ativo ? 'Desativar' : 'Ativar' }}</button>
                <button type="button" class="btn-secondary btn-sm" @click="toggleAdm(user)">{{ user.adm ? 'Remover admin' : 'Tornar admin' }}</button>
            </div>
            
        </div>
    </div>
</template>

<script>
import { confirmar } from '../../scripts/confirmacao';
import { aplicarMascara, mascararCampo } from '../../scripts/mascara';
import SystemUser from '../../scripts/SystemUserPage/SystemUser';
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';
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
            credenciaisNovas: null,
            mascara: null,
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
        mascararCampo,
        selecionarTipo(tipo) {
            this.novoUsuario.tipoIdent = tipo?.id || null;
            this.mascara = tipo?.mascara || null;
            this.novoUsuario.identidade = aplicarMascara(this.novoUsuario.identidade, this.mascara);
        },
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
        async toggleAtivo(user) {
            if (!await confirmar({ titulo: user.ativo ? 'Desativar usuário' : 'Ativar usuário', mensagem: `${user.ativo ? 'Desativar' : 'Ativar'} ${user.nome}?`, perigo: user.ativo })) return;
            const response = await SystemUser.toggleAtivo(user.id);
            if (response.success) ToastMessage.add('Status do usuário atualizado.', '', 'success');
            await this.fetchUsers(this.termoPesquisa);
        },
        async toggleAdm(user) {
            if (!await confirmar({ titulo: 'Administrador', mensagem: `Confirma ${user.adm ? 'remover o acesso de administrador de' : 'tornar administrador'} ${user.nome}?`, perigo: user.adm })) return;
            const response = await SystemUser.toggleAdm(user.id);
            if (response.success) ToastMessage.add('Permissão de administrador atualizada.', '', 'success');
            await this.fetchUsers(this.termoPesquisa);
        },
        async copiarCredenciais() {
            const { login, senha } = this.credenciaisNovas;
            try {
                await navigator.clipboard.writeText(`Login: ${login}\nSenha: ${senha}`);
                ToastMessage.add('Credenciais copiadas.', '', 'success');
            } catch {
                ToastMessage.add('Não foi possível copiar.', 'Anote os dados manualmente.', 'warn');
            }
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
                this.credenciaisNovas = response.credenciais;
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
.empty-state { padding: var(--space-5); color: var(--color-text-muted); }
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
