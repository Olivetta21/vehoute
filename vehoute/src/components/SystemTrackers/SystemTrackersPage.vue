<template>
    <HeaderTelas
        :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
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

    <div class="tracker-cards-container cards-grid">
        <div class="tracker-card card" v-for="(tracker, index) in trackers" :key="index">            
            <div v-if="tracker.mostrarObservacao" class="tracker-obs">
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
                        <p class="tracker-id">#{{ tracker.id }}</p>
                        <p class="badge" :class="tracker.ativo ? 'is-success' : 'is-danger'">{{ tracker.ativo ? 'Ativo' : 'Desativado' }}</p>
                        <p class="badge">{{ statusOptions.find(s => s.id === tracker.status)?.short || 'Desconhecido' }}</p>
                    </div>
                    <div class="tracker-card-name">{{ tracker.hardware }}</div>
                    <div class="tracker-card-posses">
                        <p>Dono: {{ tracker.nome || 'Sem dono' }}</p>
                        <p>Ouvintes: {{ tracker.qnto }}</p>
                    </div>
                </div>
                <div class="tracker-card-footer-wrapper">
                    <div class="tracker-card-footer">
                        <button class="btn-secondary btn-sm" @click="toggleObservacao(index)">Infos</button>
                        <button type="button" class="btn-secondary btn-sm" @click="toggleAtivo(tracker.id)">{{ tracker.ativo ? 'Desativar' : 'Ativar' }}</button>
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
.feedback-message { pointer-events: all; padding: var(--space-2) var(--space-4); font-size: var(--font-sm); background: var(--color-surface); border-radius: var(--radius-md); box-shadow: var(--shadow-md); }
.modal-container .modal-form { max-height: calc(100% - var(--space-6)); }

.tracker-card { display: flex; flex-direction: column; align-self: stretch; gap: var(--space-4); padding: var(--space-4); }
.tracker-card-content { display: flex; flex-direction: column; gap: var(--space-2); }
.tracker-card-stats { display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-2); }
.tracker-id { margin-right: auto; color: var(--color-text-subtle); font-size: var(--font-xs); font-weight: var(--weight-bold); }
.tracker-obs { display: flex; flex-direction: column; gap: var(--space-2); font-size: var(--font-sm); overflow-wrap: anywhere; }
.tracker-obs-name,
.tracker-card-name { font-size: var(--font-lg); font-weight: var(--weight-bold); overflow-wrap: anywhere; }
.tracker-obs-name { display: flex; align-items: center; gap: var(--space-1); cursor: pointer; }
.tracker-obs-name:hover { color: var(--color-primary); }
.tracker-card-posses { display: flex; justify-content: space-between; gap: var(--space-3); color: var(--color-text-muted); font-size: var(--font-sm); }
.tracker-card-posses p { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tracker-card-footer-wrapper { margin-top: auto; padding-top: var(--space-3); border-top: 1px solid var(--color-border); }
.tracker-card-footer { display: flex; justify-content: flex-end; gap: var(--space-2); }
</style>
