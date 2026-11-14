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
    <div class="user-cards-container">
        <div class="user-card" v-for="user in users" :key="user.id">
            <div class="user-card-content">
                <div class="user-card-stats">
                    <p> {{ user.id }}</p>
                    <p v-if="user.adm"> 👑 </p>
                    <p> {{ user.ativo ? '🟢' : '🔴' }}</p>
                </div>
                <div class="user-card-name">{{ user.nome }}</div>
                <div class="user-card-posses">
                    <p> 🚗 {{ user.qnt_posse_rastr }}</p>
                    <p> 👂 {{ user.ouvinte_qnt_rastr }}</p>
                </div>
            </div>
            <div class="user-card-footer">
                <p>{{ user.email }}</p>
                <p>{{ user.telefone }}</p>
                <p>{{ user.identidade }}</p>
                <button type="button" @click="toggleAtivo(user.id)">{{ user.ativo ? 'Desativar' : 'Ativar' }}</button>
                <button type="button" @click="toggleAdm(user.id)">{{ user.adm ? 'Remover admin' : 'Tornar admin' }}</button>
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

.modal-container {
    pointer-events: none;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-form {
    pointer-events: all;
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: min(440px, calc(100vw - 40px));
    padding: 26px;
    background: var(--colorA1);
    border: 1px solid var(--colorA2);
    border-radius: 16px;
    box-shadow: 0 18px 45px rgb(22 22 22 / 18%);
}

.modal-heading {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.modal-heading h2 {
    margin: 0;
    color: var(--colorA3);
}

.modal-form input:not([type="checkbox"]) {
    box-sizing: border-box;
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d8dce2;
    border-radius: 8px;
}

.feedback-message {
    margin: 0;
    font-size: 0.95rem;
}

.user-cards-container {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 18px;
    padding: 22px;
    overflow: auto;
}

.user-card {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: clamp(240px, 100%, 360px);
    min-height: 190px;
    padding: 18px;
    border: 1px solid #e1e5eb;
    border-radius: 16px;
    background: var(--colorA1);
    box-shadow: 0 8px 22px rgb(22 22 22 / 7%);
    box-sizing: border-box;
    transition: transform 160ms ease, box-shadow 160ms ease;
}

.user-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgb(22 22 22 / 12%);
}

.user-card-content {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
}

.user-card-stats {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: #687281;
    font-size: 0.85rem;
}

.user-card-stats p,
.user-card-posses p,
.user-card-footer p {
    margin: 0;
}

.user-card-name {
    overflow: hidden;
    color: var(--colorA3);
    font-size: 1.15rem;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-card-posses {
    display: flex;
    gap: 14px;
    color: #687281;
    font-size: 0.9rem;
}

.user-card-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    margin-top: auto;
    color: #687281;
    font-size: 0.85rem;
}

.user-card-footer p {
    width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-card-footer button {
    flex: 1;
    min-width: 110px;
}

</style>