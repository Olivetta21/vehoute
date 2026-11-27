<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            :mostrarAdicionar="true"
            @voltar="this.$router.back()"
            @pesquisa="fetchTrackers"
            @adicionar="showAddTrackerForm = true"
        />

        <GenericModalWindow v-if="this.showAddTrackerForm" @close="showAddTrackerForm = false; newTracker = {}">
            <div class="modal-container">
                <form class="modal-form" @submit.prevent>
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

        <p v-if="buscou && !trackers.length" class="empty-state">Nenhum rastreador por aqui. Use o botão + para adicionar um.</p>
            <div class="trackers-container cards-grid">
            <TrackerCard v-for="(tracker, index) in trackers" :key="tracker.ur_id ?? 'r' + tracker.r_id" :tracker="tracker"
                :conexao="conexaoDe(tracker)"
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
import { confirmar } from '../../scripts/confirmacao';
import UserTrackers from '../../scripts/UserTracker/UserTrackers';
import HeaderTelas from '../utils/HeaderTelas.vue';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import TrackerOuvintes from '../../scripts/UserTracker/TrackerOuvintes.js';
import TrackerLocOculta from '../../scripts/UserTracker/TrackerLocOculta.js';
import TrackerCard from './TrackerCard.vue';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import MainWS from '../../scripts/Websockets/Main_Websocket';
import Usuario from '../../scripts/LoginPage/Usuario';
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
            buscou: false,
            conexoes: {}, // r_id -> true | false | null (não foi possível saber)
            showAddTrackerForm: false,

            newTracker: {}
        }
    },
    mounted() {
        this.fetchTrackers();
        this.verificador = setInterval(() => {
            if (!document.hidden) this.atualizarConexoes();
        }, 20000);
    },
    beforeUnmount() {
        clearInterval(this.verificador);
    },
    methods: {
        async fetchTrackers(nome) {
            this.trackers = await UserTrackers.getRastreadores(nome);
            this.buscou = true;
            this.atualizarConexoes();
        },

        // o status só vale para o dono e para ouvintes com o vínculo operante
        podeVerConexao(t) {
            return Boolean(t.r_ativo && (t.dono_id == Usuario.id || (t.ur_status === 1 && t.ur_ativo)));
        },
        conexaoDe(t) {
            if (!this.podeVerConexao(t)) return 'oculta';
            if (!(t.r_id in this.conexoes)) return 'verificando';
            if (this.conexoes[t.r_id] === null) return 'indisponivel';
            return this.conexoes[t.r_id] ? 'conectado' : 'desconectado';
        },
        async atualizarConexoes() {
            const ids = [...new Set(this.trackers.filter(this.podeVerConexao).map(t => t.r_id))];
            const pedido = this.pedidoConexao = (this.pedidoConexao || 0) + 1;
            const conectados = await MainWS.connectedTrackers(ids);
            if (pedido !== this.pedidoConexao) return;
            this.conexoes = Object.fromEntries(ids.map(id => [id, conectados ? conectados.has(Number(id)) : null]));
        },

        async verificarRastreador() {
            const rastreador = await UserTrackers.checkRastreador(this.newTracker.token, this.newTracker.password);
            if (rastreador) {
                //id, dono_id, token_publico, status
                rastreador.checked = true;
                rastreador.validated = true;
                rastreador.senha = this.newTracker.password;
                this.newTracker = {...rastreador};
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
                // a lista do servidor substitui a linha "sem vínculo" que o dono já via
                await this.fetchTrackers('');
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
            }
        },

        async handleDeclineProposal(ur_id) {
            const ok = await UserTrackers.recusarPropostaOuvinte(ur_id);
            if (ok) {
                this.fetchTrackers('');
            }
        },

        async handleAcceptTransfer(ur_id) {
            const ok = await UserTrackers.aceitarTransferencia(ur_id);
            if (ok) {
                this.fetchTrackers('');
            }
        },

        async handleDeclineTransfer(ur_id) {
            const ok = await UserTrackers.recusarTransferencia(ur_id);
            if (ok) {
                this.fetchTrackers('');
            }
        },

        async handleDeleteTracker(ur_id) {
            if (!await confirmar({ titulo: 'Remover rastreador', mensagem: 'Remover esse rastreador da sua lista?', textoConfirmar: 'Remover', perigo: true })) return;
            const ok = await UserTrackers.excluirRastreador(ur_id);
            if (ok) {
                this.trackers = this.trackers.filter(t => t.ur_id !== ur_id);
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
