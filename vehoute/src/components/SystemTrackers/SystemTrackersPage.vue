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
                    :data="statusNovos"
                    :selectedId="novoRastreador.status"
                    placeholder="Selecione o status"
                    @update:value="novoRastreador.status = $event?.id"
                />
                <input v-model="novoRastreador.hardware" type="text" placeholder="Hardware" required />
                <input v-model.trim="novoRastreador.token" type="text" maxlength="32" pattern="[0-9a-fA-F]{32}" title="32 caracteres hexadecimais" placeholder="Token privado (32 hexadecimais)" required />
                <input v-model.trim="novoRastreador.tokenPublico" type="text" minlength="8" maxlength="32" placeholder="Token público (8 a 32 caracteres)" required />
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

    <GenericModalWindow v-if="edicao" @close="edicao = null">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="salvarEdicao">
                <h2>Editar rastreador #{{ edicao.original.id }}</h2>
                <label class="campo">Hardware
                    <input v-model.trim="edicao.hardware" type="text" maxlength="100" required />
                </label>
                <label class="campo">Token privado
                    <input v-model.trim="edicao.token" type="text" maxlength="100" required />
                </label>
                <label class="campo">Token público
                    <input v-model.trim="edicao.token_publico" type="text" maxlength="100" required />
                </label>
                <label class="campo">Nova senha
                    <input v-model="edicao.senha" type="text" minlength="6" maxlength="72" autocomplete="off" placeholder="Deixe vazio para manter" />
                </label>
                <label class="campo">Observações
                    <textarea v-model="edicao.obs" maxlength="200" rows="2"></textarea>
                </label>
                <label class="campo">Status
                    <select v-model.number="edicao.status">
                        <option v-for="s in statusEdicao" :key="s.id" :value="s.id">{{ s.nome }}</option>
                    </select>
                </label>

                <p class="dono-atual">Proprietário atual: <strong>{{ edicao.original.nome || 'Sem dono' }}</strong></p>
                <SelectInput
                    :searchFn="searchUsuarios"
                    :required="false"
                    placeholder="Trocar proprietário (opcional)"
                    @update:value="edicao.novoDono = $event?.id ? $event : null"
                />
                <template v-if="trocaDeDono">
                    <label v-if="edicao.original.u_id" class="opcao">
                        <input v-model="edicao.removerAnterior" type="checkbox" />
                        Remover o vínculo de ouvinte do proprietário anterior
                    </label>
                    <p class="aviso">
                        O novo proprietário passa a ser ouvinte ativo, tendo ou não vínculo anterior.
                        {{ edicao.original.status === 3 ? 'A transferência pendente será cancelada.' : '' }}
                    </p>
                </template>

                <div class="modal-actions">
                    <button type="submit">Salvar</button>
                    <button type="button" class="btn-secondary" @click="edicao = null">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>

    <p v-if="buscou && !trackers.length" class="empty-state">Nenhum rastreador encontrado.</p>
    <div class="tracker-cards-container cards-grid">
        <div class="tracker-card card" v-for="(tracker, index) in trackers" :key="tracker.id">
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
                        <button type="button" class="btn-secondary btn-sm" @click="toggleObservacao(index)">Infos</button>
                        <button type="button" class="btn-secondary btn-sm" @click="abrirEdicao(tracker)">Editar</button>
                        <button type="button" class="btn-secondary btn-sm" @click="toggleAtivo(tracker)">{{ tracker.ativo ? 'Desativar' : 'Ativar' }}</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import { confirmar } from '../../scripts/confirmacao';
import TrackerPage from '../../scripts/SystemTrackerPage/TrackerPage';
import HeaderTelas from '../utils/HeaderTelas.vue';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';
import LeftArrow from '../utils/LeftArrow.vue';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import SelectInput from '../utils/SelectInput.vue';

export default {
    name: 'SystemTrackersPage',
    data() {
        return {
            trackers: [],
            buscou: false,
            edicao: null,
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
    computed: {
        trocaDeDono() {
            return Boolean(this.edicao?.novoDono && this.edicao.novoDono.id !== this.edicao.original.u_id);
        },
        // "em transferência" só nasce de uma proposta do dono; aparece apenas se já for o estado atual
        statusEdicao() {
            return this.statusOptions.filter(s => s.id !== 3 || this.edicao?.original.status === 3);
        },
        statusNovos() {
            return this.statusOptions.filter(s => s.id !== 3);
        }
    },
    mounted() {
        this.fetchTrackers();
    },
    methods: {
        abrirEdicao(tracker) {
            this.edicao = {
                original: { ...tracker },
                hardware: tracker.hardware || '',
                token: tracker.token || '',
                token_publico: tracker.token_publico || '',
                senha: '',
                obs: tracker.obs || '',
                status: tracker.status,
                novoDono: null,
                removerAnterior: false
            };
        },
        async salvarEdicao() {
            const { original } = this.edicao;
            // envia somente o que mudou
            const campos = {};
            for (const nome of ['hardware', 'token', 'token_publico', 'obs', 'status']) {
                if (this.edicao[nome] !== (original[nome] ?? '')) campos[nome] = this.edicao[nome];
            }
            if (this.edicao.senha) campos.senha = this.edicao.senha;
            if (this.trocaDeDono) {
                if (!await confirmar({ titulo: 'Trocar proprietário', mensagem: `Transferir "${original.hardware}" para ${this.edicao.novoDono.nome}?`, textoConfirmar: 'Transferir' })) return;
                campos.dono_id = this.edicao.novoDono.id;
                campos.remover_dono_anterior = this.edicao.removerAnterior;
            }
            if (!Object.keys(campos).length) {
                this.edicao = null;
                return;
            }

            const response = await TrackerPage.editRastreador(original.id, campos);
            if (response?.success) {
                ToastMessage.add('Rastreador atualizado.', '', 'success');
                this.edicao = null;
                await this.fetchTrackers(this.termoPesquisa);
            }
        },
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
            this.buscou = true;
        },
        async toggleAtivo(tracker) {
            if (!await confirmar({ titulo: tracker.ativo ? 'Desativar rastreador' : 'Ativar rastreador', mensagem: `${tracker.ativo ? 'Desativar' : 'Ativar'} o rastreador ${tracker.hardware}?`, perigo: tracker.ativo })) return;
            const response = await TrackerPage.toggleAtivo(tracker.id);
            if (response?.success) ToastMessage.add('Status do rastreador atualizado.', '', 'success');
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
.modal-container .modal-form { max-height: calc(100% - var(--space-6)); overflow-y: auto; }
.campo { display: flex; flex-direction: column; gap: var(--space-1); color: var(--color-text-muted); font-size: var(--font-xs); font-weight: var(--weight-bold); text-align: left; }
.campo input,
.campo select,
.campo textarea { width: 100%; font-weight: normal; }
.opcao { display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-sm); text-align: left; }
.dono-atual,
.aviso { font-size: var(--font-sm); }
.aviso { color: var(--color-text-muted); }

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
