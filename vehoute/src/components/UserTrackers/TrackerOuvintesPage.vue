<template>
    <HeaderTelas
        :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName + ' ' + (TrackerOuvintes.tracker?.rastreador_nome || '')"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchOuvintes"
        @adicionar="handleAdicionar"
    />
    <GenericModalWindow v-if="showAddOuvinteForm" @close="fecharModalNovoOuvinte">
        <div class="add-ouvinte-form-container">
            <form class="add-ouvinte-form" @submit.prevent="salvarNovoOuvinte">
                <h2>Novo ouvinte</h2>
                <p>Envie uma proposta de inclusão para um usuário do sistema.</p>
                <input v-model.trim="newOuvinte.nome" type="text" placeholder="Nome do ouvinte" required />
                <input v-model.number="newOuvinte.usuario_id_destino" type="number" min="1" placeholder="ID do usuário destino" required />
                <div class="add-ouvinte-actions">
                    <button type="submit">Adicionar</button>
                    <button type="button" @click="fecharModalNovoOuvinte">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>
    <div class="ouvintes-page-content">
            <ul>
            <li v-for="ouvinte in ouvintes" :key="ouvinte.id">
                <OuvinteCard
                    :ouvinte="ouvinte"
                    @pause="handlePause"
                    @resume="handleResume"
                    @accept="handleAcceptOuvinte"
                    @decline="handleDeclineOuvinte"
                    @exclude="handleDeleteOuvinte"
                    @possechange="handlePosseTransfer"
                    @cancelpossechange="handleCancelTransfer"
                />
            </li>
        </ul>
    </div>

</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import TrackerOuvintes from '../../scripts/UserTracker/TrackerOuvintes.js';
import OuvinteCard from './OuvinteCard.vue';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import UserTrackers from '../../scripts/UserTracker/UserTrackers';


export default {
    name: 'TrackerOuvintesPage',
    data() {
        return {
            PagesRoutes,
            TrackerOuvintes,
            showAddOuvinteForm: false,
            newOuvinte: {
                nome: '',
                usuario_id_destino: null,
            },
            ouvintes: []
        }
    },
    mounted() {
        this.fetchOuvintes();
    },
    methods: {
        async fetchOuvintes(name = '') {
            this.ouvintes = await TrackerOuvintes.getOuvintes(name);
        },
        async handleAdicionar() {
            this.showAddOuvinteForm = true;
            this.newOuvinte = {
                nome: '',
                usuario_id_destino: null,
            };
        },
        fecharModalNovoOuvinte() {
            this.showAddOuvinteForm = false;
            this.newOuvinte = {
                nome: '',
                usuario_id_destino: null,
            };
        },
        async salvarNovoOuvinte() {
            if (!TrackerOuvintes.tracker?.rastreador_id) {
                alert('Nenhum rastreador selecionado');
                return;
            }

            if (!this.newOuvinte.nome || !this.newOuvinte.usuario_id_destino) {
                alert('Informe o nome do ouvinte e o ID do usuário destino');
                return;
            }

            const ok = await UserTrackers.donoEnviaPropostaDeNovoOuvinte(
                TrackerOuvintes.tracker.rastreador_id,
                this.newOuvinte.nome,
                Number(this.newOuvinte.usuario_id_destino)
            );

            if (ok) {
                this.fecharModalNovoOuvinte();
                await this.fetchOuvintes();
                alert('Proposta de novo ouvinte enviada');
                return;
            }

            alert('Erro ao enviar proposta de novo ouvinte');
        },
        async handlePause(payload) {
            const { ur_id, atual_status_id } = payload;
            const resp = await TrackerOuvintes.pauseTracking(ur_id, atual_status_id);
            if (resp) this.fetchOuvintes(); else alert('Erro ao pausar');
        },
        async handleResume(payload) {
            const { ur_id, atual_status_id } = payload;
            const resp = await TrackerOuvintes.resumeTracking(ur_id, atual_status_id);
            if (resp) this.fetchOuvintes(); else alert('Erro ao resumir');
        },
        async handleAcceptOuvinte(ur_id) {
            const ok = await TrackerOuvintes.aceitarNovoOuvinte(ur_id);
            if (ok) this.fetchOuvintes(); else alert('Erro ao aceitar');
        },
        async handleDeclineOuvinte(ur_id) {
            const ok = await TrackerOuvintes.recusarNovoOuvinte(ur_id);
            if (ok) this.fetchOuvintes(); else alert('Erro ao recusar');
        },
        async handleDeleteOuvinte(ur_id) {
            if (!confirm('Confirma excluir este ouvinte?')) return;
            const ok = await TrackerOuvintes.deletarOuvinte(ur_id);
            if (ok) this.ouvintes = this.ouvintes.filter(o => o.id !== ur_id); else alert('Erro ao excluir');
        },
        async handlePosseTransfer(ur_id) {
            const ok = await TrackerOuvintes.donoEnviaPropostaParaTransferirPosse(ur_id);
            if (ok) alert('Proposta enviada'); else alert('Erro ao enviar proposta');
        },
        async handleCancelTransfer(ur_id) {
            const ok = await TrackerOuvintes.donoCancelaTransferenciaDePosse(ur_id);
            if (ok) {
                await this.fetchOuvintes();
                alert('Transferência cancelada');
                return;
            }

            alert('Erro ao cancelar a transferência');
        }
    },
    components: {
        HeaderTelas,
        OuvinteCard,
        GenericModalWindow
    }
}

</script>



<style scoped>
.add-ouvinte-form-container {
    pointer-events: none;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.add-ouvinte-form {
    pointer-events: all;
    background: white;
    border: 1px solid var(--colorA2);
    padding: 20px;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: min(420px, calc(100vw - 60px));
}

.add-ouvinte-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.ouvintes-page-content {
    width: 100%;
    height: 100%;
    overflow-x: auto;
}

ul {
    width: min-content;
}

li {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    border: 1px solid var(--colorA2);
    box-shadow: 3px 3px 11px #00000061;
    padding: 10px;
    gap: 5px;
    border-radius: 5px;
    text-wrap: nowrap;
}


</style>