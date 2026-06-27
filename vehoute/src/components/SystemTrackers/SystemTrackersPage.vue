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
    <section v-if="mostrarFormulario" class="new-tracker-panel">
        <h2>Novo rastreador</h2>
        <form class="new-tracker-form" @submit.prevent="criarRastreador">
            <input v-model="novoRastreador.hardware" type="text" placeholder="Hardware" required />
            <input v-model="novoRastreador.token" type="text" placeholder="Token" required />
            <input v-model="novoRastreador.tokenPublico" type="text" placeholder="Token público" required />
            <input v-model="novoRastreador.senha" type="text" placeholder="Senha" />
            <input v-model="novoRastreador.obs" type="text" placeholder="Observações" />
            <input v-model.number="novoRastreador.status" type="number" min="1" placeholder="Status" required />
            <input v-model.number="novoRastreador.donoId" type="number" min="1" placeholder="ID do dono" required />
            <button type="submit">Adicionar</button>
        </form>
        <p v-if="mensagem" class="feedback-message">{{ mensagem }}</p>
    </section>
    <div class="tracker-cards-container">
        <div class="tracker-card" v-for="(tracker, index) in trackers" :key="index">            
            <div v-if="tracker.mostrarObservacao">
                <div class="tracker-obs-name" @click="toggleObservacao(index)">
                    <LeftArrow/>
                    <div>{{ tracker.hardware }}</div>
                </div>
                <p>{{ tracker.obs }}</p>
            </div>
            <template v-else>
                <div class="tracker-card-content">
                    <div class="tracker-card-stats">
                        <p>{{ tracker.id }}</p>
                        <p>{{ tracker.ativo ? '🟢' : '🔴' }}</p>
                        <p>#{{ tracker.status }}</p>
                    </div>
                    <div class="tracker-card-name">{{ tracker.hardware }}</div>
                    <div class="tracker-card-posses">
                        <p>👤 {{ tracker.nome || 'Sem dono' }}</p>
                        <p>📡 {{ tracker.qnto }}</p>
                    </div>
                </div>
                <div class="tracker-card-footer-wrapper">
                    <div class="tracker-card-footer">
                        <p>{{ tracker.token_publico }}</p>
                        <p>{{ tracker.token }}</p>
                        <button @click="toggleObservacao(index)">OBS</button>
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
                donoId: 1
            }
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
        async fetchTrackers(pesquisa = '') {
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
                    status: 1,
                    donoId: 1
                };
                this.mostrarFormulario = false;
                await this.fetchTrackers(this.termoPesquisa);
                return;
            }

            this.mensagem = response?.error ?? 'Não foi possível criar o rastreador.';
        }
    },
    components: {
        HeaderTelas,
        LeftArrow
    }
};
</script>

<style scoped>
.new-tracker-panel {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
}

.new-tracker-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 8px;
    align-items: center;
}

.feedback-message {
    font-size: 0.95rem;
}

.tracker-cards-container {
    display: flex;
    flex-wrap: wrap;
    overflow: auto;
    gap: 5px;
}

.tracker-card {
    display: flex;
    flex-direction: column;
    border: 1px solid #ccc;
    border-radius: 14px;
    padding: 10px;
    background: #fff;
    box-shadow: 0 6px 18px rgb(0 0 0 / 6%);

    width: clamp(0px, 100%, 350px);
    min-width: min-content;
}

.tracker-cards-container > :last-child {
    margin-bottom: 40px;
}


.tracker-card-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tracker-card-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.tracker-obs-name, .tracker-card-name {
    flex: 1;
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
    flex-direction: column;
    gap: 6px;
    text-align: right;
    color: #555;
}

.tracker-card-footer-wrapper {
    display: flex;
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