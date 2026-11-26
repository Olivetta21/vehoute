<template>
    <HeaderTelas
        :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName + ' ' + (TrackerOuvintes.tracker?.ur_nome || '')"
        :mostrarVoltar="true"
        :mostrarPesquisa="true"
        :mostrarAdicionar="true"
        @voltar="this.$router.back()"
        @pesquisa="fetchOuvintes"
        @adicionar="handleAdicionar"
    />
    <GenericModalWindow v-if="showAddOuvinteForm" @close="fecharModalNovoOuvinte">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="salvarNovoOuvinte">
                <h2>Novo ouvinte</h2>
                <p>Envie uma proposta de inclusão para um usuário do sistema.</p>
                <input v-model.number="newOuvinte.usuario_id_destino" type="number" min="1" placeholder="ID do usuário destino" required />
                <div class="modal-actions">
                    <button type="submit">Adicionar</button>
                    <button type="button" class="btn-secondary" @click="fecharModalNovoOuvinte">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>
    <div class="ouvintes-page-content">
            <ul>
            <li v-for="ouvinte in ouvintes" :key="ouvinte.ur_id" class="card">
                <OuvinteCard
                    :ouvinte="ouvinte"
                    @pause="handlePause"
                    @resume="handleResume"
                    @accept="handleAcceptOuvinte"
                    @decline="handleDeclineOuvinte"
                    @exclude="handleDeleteOuvinte"
                    @possechange="handlePosseTransfer"
                    @cancelpossechange="handleCancelTransfer"
                    @locte="handleLocTempoRealChange(ouvinte, $event)"
                    @locsa="handleLocSalvoChange(ouvinte, $event)"
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
import ToastMessage from '../../scripts/ToastMessage/ToastMessage.js';


export default {
    name: 'TrackerOuvintesPage',
    data() {
        return {
            PagesRoutes,
            TrackerOuvintes,
            showAddOuvinteForm: false,
            newOuvinte: {
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
                usuario_id_destino: null,
            };
        },
        fecharModalNovoOuvinte() {
            this.showAddOuvinteForm = false;
            this.newOuvinte = {
                usuario_id_destino: null,
            };
        },
        async salvarNovoOuvinte() {
            if (!TrackerOuvintes.tracker?.r_id) {
                ToastMessage.add('Nenhum rastreador selecionado', '', 'error', true);
                return;
            }

            if (!this.newOuvinte.usuario_id_destino) {
                ToastMessage.add('Informe o ID do usuário destino', '', 'error', true);
                return;
            }

            const ok = await UserTrackers.donoEnviaPropostaDeNovoOuvinte(
                TrackerOuvintes.tracker.r_id,
                Number(this.newOuvinte.usuario_id_destino)
            );

            if (ok) {
                this.fecharModalNovoOuvinte();
                await this.fetchOuvintes();
                ToastMessage.add('Proposta de novo ouvinte enviada', '', 'success', true);
                return;
            }
        },
        async handlePause(payload) {
            const { ur_id, atual_status_id } = payload;
            const resp = await TrackerOuvintes.pauseTracking(ur_id, atual_status_id);
            if (resp) this.fetchOuvintes();
        },
        async handleResume(payload) {
            const { ur_id, atual_status_id } = payload;
            const resp = await TrackerOuvintes.resumeTracking(ur_id, atual_status_id);
            if (resp) this.fetchOuvintes();
        },
        async handleAcceptOuvinte(ur_id) {
            const ok = await TrackerOuvintes.aceitarNovoOuvinte(ur_id);
            if (ok) this.fetchOuvintes();
        },
        async handleDeclineOuvinte(ur_id) {
            const ok = await TrackerOuvintes.recusarNovoOuvinte(ur_id);
            if (ok) this.fetchOuvintes();
        },
        async handleDeleteOuvinte(ur_id) {
            if (!confirm('Confirma excluir este ouvinte?')) return;
            const ok = await TrackerOuvintes.deletarOuvinte(ur_id);
            if (ok) this.ouvintes = this.ouvintes.filter(o => o.ur_id !== ur_id);
        },
        async handlePosseTransfer(ur_id) {
            const ok = await TrackerOuvintes.donoEnviaPropostaParaTransferirPosse(ur_id);
            if (ok) ToastMessage.add('Proposta enviada', '', 'success', true);
        },
        async handleCancelTransfer(ur_id) {
            const ok = await TrackerOuvintes.donoCancelaTransferenciaDePosse(ur_id);
            if (ok) {
                await this.fetchOuvintes();
                ToastMessage.add('Transferência cancelada', '', 'success', true);
                return;
            }
        },
        async handleLocTempoRealChange(ouvinte, payload) {
            const { ur_id, new_value } = payload;
            console.log('handleLocTempoRealChange', ur_id, new_value);
            
            const ok = await TrackerOuvintes.changeLocStateForOuvinte("loc_temporeal",ur_id, new_value);
            if (ok !== null) {
                ouvinte.ur_loc_temporeal = ok;
            }
        },
        async handleLocSalvoChange(ouvinte, payload) {
            const { ur_id, new_value } = payload;
            console.log('handleLocSalvoChange', ur_id, new_value);
            
            const ok = await TrackerOuvintes.changeLocStateForOuvinte("loc_salvos",ur_id, new_value);
            if (ok !== null) {
                ouvinte.ur_loc_salvos = ok;
            }
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
.ouvintes-page-content { width: 100%; padding: var(--space-5); }
ul { display: flex; flex-direction: column; gap: var(--space-3); max-width: 1080px; list-style: none; }
li {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.2fr) auto minmax(0, 1.4fr);
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4);
}
@media (max-width: 800px) {
    .ouvintes-page-content { padding: var(--space-3); }
    li { grid-template-columns: minmax(0, 1fr); gap: var(--space-3); }
    li :deep(.ouvinte-actions) { justify-content: flex-start; }
}
</style>
