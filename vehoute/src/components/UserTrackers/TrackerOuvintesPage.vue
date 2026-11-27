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
                <p>Peça à pessoa o código que aparece no perfil dela e envie a proposta de inclusão.</p>
                <input v-model.trim="newOuvinte.codigo" type="text" maxlength="8" autocomplete="off" autocapitalize="characters" placeholder="Código do usuário" required />
                <input v-model.trim="newOuvinte.nome" type="text" maxlength="100" placeholder="Nome do rastreador para essa pessoa (opcional)" />
                <div class="modal-actions">
                    <button type="submit">Enviar proposta</button>
                    <button type="button" class="btn-secondary" @click="fecharModalNovoOuvinte">Cancelar</button>
                </div>
            </form>
        </div>
    </GenericModalWindow>
    <div class="ouvintes-page-content">
        <div v-if="!donoVinculado && Usuario.hasPermission('Gerenciar Ouvintes')" class="auto-inclusao card">
            <p>Você é o dono deste rastreador, mas ainda não é um ouvinte dele, por isso não pode acompanhá-lo.</p>
            <button type="button" @click="incluirMe">Incluir-me como ouvinte</button>
        </div>
        <p v-if="buscou && !ouvintes.length" class="empty-state">Nenhum ouvinte encontrado.</p>
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
import { confirmar } from '../../scripts/confirmacao';
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import TrackerOuvintes from '../../scripts/UserTracker/TrackerOuvintes.js';
import OuvinteCard from './OuvinteCard.vue';
import GenericModalWindow from '../utils/GenericModalWindow.vue';
import UserTrackers from '../../scripts/UserTracker/UserTrackers';
import Usuario from '../../scripts/LoginPage/Usuario.js';
import ToastMessage from '../../scripts/ToastMessage/ToastMessage.js';


export default {
    name: 'TrackerOuvintesPage',
    data() {
        return {
            PagesRoutes,
            TrackerOuvintes,
            showAddOuvinteForm: false,
            Usuario,
            newOuvinte: { codigo: '', nome: '' },
            ouvintes: [],
            donoVinculado: true,
            buscou: false
        }
    },
    mounted() {
        this.fetchOuvintes();
    },
    methods: {
        async fetchOuvintes(name = '') {
            if (!TrackerOuvintes.tracker) return;
            const r = await TrackerOuvintes.getOuvintes(name);
            this.ouvintes = r.ouvintes;
            this.donoVinculado = r.dono_vinculado !== false;
            this.buscou = true;
        },
        async incluirMe() {
            const tracker = TrackerOuvintes.tracker;
            if (await TrackerOuvintes.incluirMe(tracker.r_id, tracker.ur_nome)) {
                ToastMessage.add('Você agora é ouvinte deste rastreador', '', 'success');
                await this.fetchOuvintes();
            }
        },
        async handleAdicionar() {
            this.showAddOuvinteForm = true;
            this.newOuvinte = { codigo: '', nome: '' };
        },
        fecharModalNovoOuvinte() {
            this.showAddOuvinteForm = false;
            this.newOuvinte = { codigo: '', nome: '' };
        },
        async salvarNovoOuvinte() {
            if (!TrackerOuvintes.tracker?.r_id) {
                ToastMessage.add('Nenhum rastreador selecionado', '', 'error', true);
                return;
            }

            if (!this.newOuvinte.codigo) {
                ToastMessage.add('Informe o código do usuário', '', 'error', true);
                return;
            }

            const ok = await UserTrackers.donoEnviaPropostaDeNovoOuvinte(
                TrackerOuvintes.tracker.r_id,
                this.newOuvinte.codigo.toUpperCase(),
                this.newOuvinte.nome || TrackerOuvintes.tracker.ur_nome
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
            if (!await confirmar({ titulo: 'Excluir ouvinte', mensagem: 'Este ouvinte deixará de acompanhar o rastreador.', textoConfirmar: 'Excluir', perigo: true })) return;
            const ok = await TrackerOuvintes.deletarOuvinte(ur_id);
            if (ok) this.ouvintes = this.ouvintes.filter(o => o.ur_id !== ur_id);
        },
        async handlePosseTransfer(ur_id) {
            if (!await confirmar({ titulo: 'Transferir posse', mensagem: 'Enviar a proposta de transferência de posse para este ouvinte?', textoConfirmar: 'Enviar proposta' })) return;
            const ok = await TrackerOuvintes.donoEnviaPropostaParaTransferirPosse(ur_id);
            if (ok) {
                await this.fetchOuvintes();
                ToastMessage.add('Proposta enviada', '', 'success', true);
            }
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
ul { display: flex; flex-direction: column; gap: var(--space-3); list-style: none; }
.auto-inclusao { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-3); margin-bottom: var(--space-3); padding: var(--space-4); font-size: var(--font-sm); background: var(--color-primary-soft); }
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
