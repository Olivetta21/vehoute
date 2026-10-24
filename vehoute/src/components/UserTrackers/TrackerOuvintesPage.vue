<template>
    <HeaderTelas
        :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName + ' ' + (TrackerOuvintes.tracker?.ur_nome || '')"
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
            <li v-for="ouvinte in ouvintes" :key="ouvinte.ur_id">
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
            if (!TrackerOuvintes.tracker?.r_id) {
                ToastMessage.add('Nenhum rastreador selecionado', '', 'error', true);
                return;
            }

            if (!this.newOuvinte.nome || !this.newOuvinte.usuario_id_destino) {
                ToastMessage.add('Informe o nome do ouvinte e o ID do usuário destino', '', 'error', true);
                return;
            }

            const ok = await UserTrackers.donoEnviaPropostaDeNovoOuvinte(
                TrackerOuvintes.tracker.r_id,
                this.newOuvinte.nome,
                Number(this.newOuvinte.usuario_id_destino)
            );

            if (ok) {
                this.fecharModalNovoOuvinte();
                await this.fetchOuvintes();
                ToastMessage.add('Proposta de novo ouvinte enviada', '', 'success', true);
                return;
            }

            ToastMessage.add('Erro ao enviar proposta de novo ouvinte', '', 'error', true);
        },
        async handlePause(payload) {
            const { ur_id, atual_status_id } = payload;
            const resp = await TrackerOuvintes.pauseTracking(ur_id, atual_status_id);
            if (resp) this.fetchOuvintes(); else ToastMessage.add('Erro ao pausar', '', 'error', true);
        },
        async handleResume(payload) {
            const { ur_id, atual_status_id } = payload;
            const resp = await TrackerOuvintes.resumeTracking(ur_id, atual_status_id);
            if (resp) this.fetchOuvintes(); else ToastMessage.add('Erro ao resumir', '', 'error', true);
        },
        async handleAcceptOuvinte(ur_id) {
            const ok = await TrackerOuvintes.aceitarNovoOuvinte(ur_id);
            if (ok) this.fetchOuvintes(); else ToastMessage.add('Erro ao aceitar', '', 'error', true);
        },
        async handleDeclineOuvinte(ur_id) {
            const ok = await TrackerOuvintes.recusarNovoOuvinte(ur_id);
            if (ok) this.fetchOuvintes(); else ToastMessage.add('Erro ao recusar', '', 'error', true);
        },
        async handleDeleteOuvinte(ur_id) {
            if (!confirm('Confirma excluir este ouvinte?')) return;
            const ok = await TrackerOuvintes.deletarOuvinte(ur_id);
            if (ok) this.ouvintes = this.ouvintes.filter(o => o.ur_id !== ur_id); else ToastMessage.add('Erro ao excluir', '', 'error', true);
        },
        async handlePosseTransfer(ur_id) {
            const ok = await TrackerOuvintes.donoEnviaPropostaParaTransferirPosse(ur_id);
            if (ok) ToastMessage.add('Proposta enviada', '', 'success', true); else ToastMessage.add('Erro ao enviar proposta', '', 'error', true);
        },
        async handleCancelTransfer(ur_id) {
            const ok = await TrackerOuvintes.donoCancelaTransferenciaDePosse(ur_id);
            if (ok) {
                await this.fetchOuvintes();
                ToastMessage.add('Transferência cancelada', '', 'success', true);
                return;
            }

            ToastMessage.add('Erro ao cancelar a transferência', '', 'error', true);
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
    align-items: center;
    margin-bottom: 14px;
}


</style>