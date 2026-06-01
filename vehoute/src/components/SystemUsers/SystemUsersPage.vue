<template>
    <HeaderTelas
        :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchUsers"
        @adicionar="alternarFormulario"
    />
    <section v-if="mostrarFormulario" class="new-user-panel">
        <h2>Novo usuário</h2>
        <form class="new-user-form" @submit.prevent="criarUsuario">
            <input v-model="novoUsuario.nome" type="text" placeholder="Nome" required />
            <input v-model="novoUsuario.email" type="email" placeholder="Email" required />
            <input v-model="novoUsuario.identidade" type="text" placeholder="Identidade" required />
            <input v-model.number="novoUsuario.tipoIdent" type="number" min="1" placeholder="Tipo de identificação" required />
            <label class="check-field">
                <input v-model="novoUsuario.adm" type="checkbox" />
                Administrador
            </label>
            <button type="submit">Adicionar</button>
        </form>
        <p v-if="mensagem" class="feedback-message">{{ mensagem }}</p>
    </section>
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
import SystemUser from '@/scripts/SystemUserPage/SystemUser';
import HeaderTelas from '../utils/HeaderTelas.vue';
import PagesRoutes from '@/scripts/PagesRoutes.js';

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
                tipoIdent: 1,
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
        async fetchUsers(pesquisa = '') {
            this.termoPesquisa = pesquisa ?? '';
            this.users = await SystemUser.getUsuarios(this.termoPesquisa);
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
                    tipoIdent: 1,
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
        HeaderTelas
    }
}

</script>

<style scoped>

.new-user-panel {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
}

.new-user-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 8px;
    align-items: center;
}

.check-field {
    display: flex;
    align-items: center;
    gap: 6px;
}

.feedback-message {
    margin: 0;
    font-size: 0.95rem;
}

.user-cards-container {
    display: flex;
    flex-direction: column;
    overflow: auto;
}

.user-card {
    display: flex;
    flex-direction: column;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 6px;
}

.user-card-content {
    display: flex;
    align-items: center;
}

.user-card-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.user-card-name {
    flex: 1;
    font-weight: bold;
}

.user-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    overflow: auto;
    text-wrap: nowrap;
}

</style>