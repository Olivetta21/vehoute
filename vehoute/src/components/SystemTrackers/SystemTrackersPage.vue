<template>
    <HeaderTelas
        :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchTrackers"
        @adicionar="alternarFormulario"
    />
    <GenericModalWindow v-if="mostrarFormulario" @close="alternarFormulario">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="criarRastreador">
                <h2>Novo rastreador</h2>
                <SelectInput
                    :data="statusOptions"
                    placeholder="Selecione o status"
                    @update:value="novoRastreador.status = $event?.id"
                />
                <input v-model="novoRastreador.hardware" type="text" placeholder="Hardware" required />
                <input v-model="novoRastreador.token" type="text" placeholder="Token" required />
                <input v-model="novoRastreador.tokenPublico" type="text" placeholder="Token público" required />
                <input v-model="novoRastreador.senha" type="text" placeholder="Senha" />
                <input v-model="novoRastreador.obs" type="text" placeholder="Observações" />
                <SelectInput 
                    :searchFn="searchUsuarios"
                    placeholder="Selecione um usuário"
                    @update:value="novoRastreador.donoId = $event?.id"
                />
                <button type="submit">Adicionar</button>
            </form>
        <p v-if="mensagem" class="feedback-message">{{ mensagem }}</p>
        </div>
    </GenericModalWindow>

    <div class="tracker-cards-container">
        <div class="tracker-card" v-for="(tracker, index) in trackers" :key="index">            
            <div v-if="tracker.mostrarObservacao">
                <div class="tracker-obs-name" @click="toggleObservacao(index)">
                    <LeftArrow/>
                    <div>{{ tracker.hardware }}</div>
                </div>
                <p>Token Publico: {{ tracker.token_publico }}</p>
                <p>Token Privado: {{ tracker.token }}</p>
                <p>Observações: {{ tracker.obs }}</p>
            </div>
            <template v-else>
                <div class="tracker-card-content">
                    <div class="tracker-card-stats">
                        <p>#{{ tracker.id }}</p>
                        <p>{{ tracker.ativo ? '🟢ativo' : '🔴desativado' }}</p>
                        <p>{{ statusOptions.find(s => s.id === tracker.status)?.short || 'Desconhecido' }}</p>
                    </div>
                    <div class="tracker-card-name">{{ tracker.hardware }}</div>
                    <div class="tracker-card-posses">
                        <p>👤 {{ tracker.nome || 'Sem dono' }}</p>
                        <p>📡 {{ tracker.qnto }}</p>
                    </div>
                </div>
                <div class="tracker-card-footer-wrapper">
                    <div class="tracker-card-footer">
                        <button @click="toggleObservacao(index)">Infos</button>
                        <button type="button" @click="toggleAtivo(tracker.id)">{{ tracker.ativo ? 'Desativar' : 'Ativar' }}</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import TrackerPage from '../../scripts/SystemTrackerPage/TrackerPage';
import HeaderTelas from '../utils/HeaderTelas.vue';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import LeftArrow from '../utils/LeftArrow.vue';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import SelectInput from '../utils/SelectInput.vue';

export default {
    name: 'SystemTrackersPage',
    data() {
        return {
            trackers: [],
            PagesRoutes,
            termoPesquisa: '',
            mostrarFormulario: false,
            mensagem: '',
            novoRastreador: {
                hardware: '',
                token: '',
                tokenPublico: '',
                senha: '',
                obs: '',
                status: 1,
                donoId: null
            },

            statusOptions: [
                {'id': 1, 'nome': 'Operando normalmente', 'short': 'Normal'},
                {'id': 2, 'nome': 'Novas localizações desativadas', 'short': 'Loc. desligado'},
                {'id': 3, 'nome': 'Em processo de transferência', 'short': 'Transferência'},
                {'id': 4, 'nome': 'O dono desativou', 'short': 'Desativado'}
            ]
        };
    },
    mounted() {
        this.fetchTrackers();
    },
    methods: {
        alternarFormulario() {
            this.mostrarFormulario = !this.mostrarFormulario;
            this.mensagem = '';
        },
        toggleObservacao(index) {
            this.trackers[index].mostrarObservacao = !this.trackers[index].mostrarObservacao;
        },
        async fetchTrackers(pesquisa = '%') {
            this.termoPesquisa = pesquisa ?? '';
            this.trackers = await TrackerPage.getRastreadores(this.termoPesquisa);
        },
        async toggleAtivo(id) {
            const response = await TrackerPage.toggleAtivo(id);
            this.mensagem = response?.success ? 'Status do rastreador atualizado.' : response?.error ?? 'Não foi possível atualizar o status.';
            await this.fetchTrackers(this.termoPesquisa);
        },
        async criarRastreador() {
            const response = await TrackerPage.addRastreador(
                this.novoRastreador.hardware,
                this.novoRastreador.token,
                this.novoRastreador.tokenPublico,
                this.novoRastreador.senha,
                this.novoRastreador.obs,
                this.novoRastreador.status,
                this.novoRastreador.donoId
            );

            if (response?.success) {
                this.mensagem = `Rastreador criado com ID ${response.rastreador.id}.`;
                this.novoRastreador = {
                    hardware: '',
                    token: '',
                    tokenPublico: '',
                    senha: '',
                    obs: '',
                    status: '',
                    donoId: null
                };
                this.mostrarFormulario = false;
                await this.fetchTrackers(this.termoPesquisa);
                return;
            }

            this.mensagem = response?.error ?? 'Não foi possível criar o rastreador.';
        },
        async searchUsuarios(searchText) {
            let users = await TrackerPage.getUsuariosDisponiveis(searchText);
            users.forEach(user => {
                user.nome = `${user.id}: ${user.nome}`;
            });
            return users;
        }

    },
    components: {
        HeaderTelas,
        LeftArrow,
        GenericModalWindow,
        SelectInput
    }
};
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
    width: min(470px, calc(100vw - 40px));
    max-height: calc(100vh - 48px);
    overflow-y: auto;
    background: var(--colorA1);
    border: 1px solid var(--colorA2);
    padding: 26px;
    border-radius: 16px;
    box-shadow: 0 18px 45px rgb(22 22 22 / 18%);
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.feedback-message {
    margin: 0;
    font-size: 0.95rem;
}

.tracker-cards-container {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    overflow: auto;
    gap: 18px;
    padding: 22px;
}

.tracker-card {
    display: flex;
    flex-direction: column;
    gap: 16px;
    border: 1px solid #e1e5eb;
    border-radius: 16px;
    padding: 18px;
    background: var(--colorA1);
    box-shadow: 0 8px 22px rgb(22 22 22 / 7%);

    width: clamp(240px, 100%, 360px);
    min-height: 180px;
    box-sizing: border-box;
    transition: transform 160ms ease, box-shadow 160ms ease;
}

.tracker-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgb(22 22 22 / 12%);
}

.tracker-cards-container > :last-child {
    margin-bottom: 18px;
}


.tracker-card-content {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
}

.tracker-card-stats {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: #687281;
    font-size: 0.85rem;
}

.tracker-obs-name, .tracker-card-name {
    font-weight: 700;
    font-size: 1.05rem;
}
.tracker-obs-name {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
}
.tracker-obs-name:hover {
    color: #007BFF;
}

.tracker-card-posses {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: #687281;
    font-size: 0.9rem;
}

.tracker-card-posses p {
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tracker-card-footer-wrapper {
    display: flex;
    margin-top: auto;
}

.tracker-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    overflow: auto;
    text-wrap: nowrap;
    width: 0;
    flex: 1;
}
</style>