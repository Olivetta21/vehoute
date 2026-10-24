<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            :mostrarAdicionar="true"
            @voltar="this.$router.back()"
            @pesquisa="fetchTrackers"
            @adicionar="showAddTrackerForm = true"
        />

        <GenericModalWindow v-if="this.showAddTrackerForm" @close="showAddTrackerForm = false">
            <div class="add-tracker-form-container">
                <form class="add-tracker-form" @submit.prevent>
                    <template v-if="!newTracker.checked || !newTracker.validated">
                        <p>Insira o token do rastreador e a senha para verificar sua validade:</p>
                        <input type="text" v-model="newTracker.token" placeholder="Token do rastreador">
                        <input type="password" v-model="newTracker.password" placeholder="Senha">
                        <button type="submit" @click="verificarRastreador">Verificar</button>
                    </template>
                    <template v-else>
                        <p>Rastreador válido! Insira um nome para identificá-lo:</p>
                        <input type="text" v-model="newTracker.name" placeholder="Nome do rastreador">
                        <button type="submit" @click="criarRastreador">Criar rastreador</button>
                    </template>
                </form>
            </div>
        </GenericModalWindow>

            <div class="trackers-container">
            <TrackerCard v-for="(tracker, index) in trackers" :key="index" :tracker="tracker"
                @ouvintes="TrackerOuvintes.openPage(tracker)"
                @lococulta="TrackerLocOculta.openPage(tracker)"
                @rastrear="MapPagina.enterWithTracker(tracker.ur_id)"
                @accept="handleAcceptProposal"
                @decline="handleDeclineProposal"
                @accept-transfer="handleAcceptTransfer"
                @decline-transfer="handleDeclineTransfer"
                @delete="handleDeleteTracker"
                @update:tracker="(updatedTracker) => { trackers[index] = updatedTracker }"
            />
        </div>
</template>

<script>
import UserTrackers from '../../scripts/UserTracker/UserTrackers';
import HeaderTelas from '../utils/HeaderTelas.vue';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import TrackerOuvintes from '../../scripts/UserTracker/TrackerOuvintes.js';
import TrackerLocOculta from '../../scripts/UserTracker/TrackerLocOculta.js';
import TrackerCard from './TrackerCard.vue';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import MapPagina from '../../scripts/MapPage/Map.js';
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';

export default {
    name: 'UserTrackersPage',
    data() {
        return {
            PagesRoutes,
            TrackerOuvintes,
            TrackerLocOculta,
            MapPagina,
            trackers: [],
            showAddTrackerForm: false,

            newTracker: {}
        }
    },
    mounted() {
         this.fetchTrackers();
    },
    methods: {
        async fetchTrackers(nome) {
            this.trackers = await UserTrackers.getRastreadores(nome);
        },

        async verificarRastreador() {
            const rastreador = await UserTrackers.checkRastreador(this.newTracker.token, this.newTracker.password);
            if (rastreador) {
                //id, dono_id, token_publico, status
                rastreador.checked = true;
                rastreador.validated = true;
                rastreador.senha = this.newTracker.password;
                this.newTracker = {...rastreador};
            } else {
                ToastMessage.add('Token ou senha inválidos', '', 'error', true);
            }
        },

        async criarRastreador() {
            if (!this.newTracker.name) {
                ToastMessage.add('Insira um nome para o rastreador', '', 'error', true);
                return;
            }
            
            const usuario_rastreador = await UserTrackers.addRastreador(
                this.newTracker.id,
                this.newTracker.dono_id,
                this.newTracker.token_publico,
                this.newTracker.senha,
                this.newTracker.status,
                this.newTracker.name
            );

            if (usuario_rastreador) {
                this.trackers.push(usuario_rastreador);
            } else {
                ToastMessage.add('Erro ao adicionar rastreador', '', 'error', true);
            }

            this.showAddTrackerForm = false;
            this.newTracker = {};
        }
        ,
        async handleAcceptProposal(ur_id) {
            const updated = await UserTrackers.aceitarPropostaOuvinte(ur_id);
            if (updated) {
                // refresh list
                this.fetchTrackers('');
            } else {
                ToastMessage.add('Erro ao aceitar proposta', '', 'error', true);
            }
        },

        async handleDeclineProposal(ur_id) {
            const ok = await UserTrackers.recusarPropostaOuvinte(ur_id);
            if (ok) {
                this.fetchTrackers('');
            } else {
                ToastMessage.add('Erro ao recusar proposta', '', 'error', true);
            }
        },

        async handleAcceptTransfer(ur_id) {
            const ok = await UserTrackers.aceitarTransferencia(ur_id);
            if (ok) {
                this.fetchTrackers('');
            } else {
                ToastMessage.add('Erro ao aceitar transferência', '', 'error', true);
            }
        },

        async handleDeclineTransfer(ur_id) {
            const ok = await UserTrackers.recusarTransferencia(ur_id);
            if (ok) {
                this.fetchTrackers('');
            } else {
                ToastMessage.add('Erro ao recusar transferência', '', 'error', true);
            }
        },

        async handleDeleteTracker(ur_id) {
            if (!confirm('Confirma remover esse rastreador da sua lista?')) return;
            const ok = await UserTrackers.excluirRastreador(ur_id);
            if (ok) {
                this.trackers = this.trackers.filter(t => t.id !== ur_id);
            } else {
                ToastMessage.add('Erro ao excluir rastreador', '', 'error', true);
            }
        }
    },
    components: {
        HeaderTelas,
        TrackerCard,
        GenericModalWindow
    }
}

</script>


<style scoped>

.trackers-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 20px;
    align-items: flex-start;
}


.add-tracker-form-container {
    pointer-events: none;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.add-tracker-form {
    pointer-events: all;
    background: white;
    border: 1px solid var(--colorA2);
    padding: 20px;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

</style>
