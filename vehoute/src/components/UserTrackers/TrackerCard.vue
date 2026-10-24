<template>
    <div class="tracker-card">
        <div class="tracker-principal" @click="(trackerVModel) ? null :detailed = !detailed">
            <div class="tracker-header">
                <span class="tracker-id"> #{{ tracker.ur_id ?? '~' }}</span>

                <h3 class="tracker-name">{{ (trackerVModel) ? '' : tracker.ur_nome || 'Rastreador sem nome' }}</h3>
                
                <span v-if="!trackerVModel" @click="startEditingTracker"> ✏️ </span>
                <template v-else>
                    <button v-if="someInfoIsEdited" type="button" class="action-accept" @click="saveAllChanges"> ✓ </button>
                    <button type="button" class="action-decline" @click="cancelEditingTracker"> ✕ </button>
                </template>
                
                <span class="expand-indicator" @click.stop="detailed = !detailed">{{ detailed ? '−' : '+' }}</span>
            </div>

            <input v-if="trackerVModel" type="text" v-model="trackerVModel.ur_nome" placeholder="Nome do rastreador">
            <div class="tracker-image-container">
                <div class="tracker-image"
                    :style="{backgroundImage: `url('${tracker.r_imagem || '/api/imagens/card_systracker.png'}')`}"
                ></div>
            </div>

            <div class="tracker-info">
                <div class="tracker-info-row">
                    <span class="info-label">Vínculo</span>
                    <div class="info-value">
                        <span class="status-indicator"
                            :style="{backgroundColor:ur_statuses.find(s => s.id === tracker.ur_status)?.color || 'gray'}"
                        ></span>
                        <span class="status-text">{{ur_statuses.find(s => s.id === tracker.ur_status)?.for_ouvinte || 'Indefinido'}}</span>
                    </div>
                </div>

                <div class="tracker-info-row">
                    <span class="info-label">Equipamento</span>
                    <div class="info-value">
                        <template v-if="trackerVModel">
                            <SelectInput
                                :data="r_statuses"
                                :selectedId="trackerVModel.r_status"
                                @update:value="trackerVModel.r_status = $event?.id || null"
                            />
                        </template>
                        <template v-else>
                            <span class="status-indicator"
                                :style="{backgroundColor:r_statuses.find(s => s.id === tracker.r_status)?.color || 'gray'}"
                            ></span>
                            <span class="status-text"> {{r_statuses.find(s => s.id === tracker.r_status)?.nome || tracker.r_status || 'Indefinido'}}</span>
                        </template>
                    </div>
                </div>

                <div class="tracker-info-row">
                    <span class="info-label">Proprietário</span>
                    <span class="owner-name"> {{ tracker.dono_nome || 'Não informado' }} </span>
                </div>
            </div>

            <div class="tracker-token">
                <span class="info-label">Token público</span>
                <input v-if="trackerVModel" type="text" v-model="trackerVModel.r_token_publico" placeholder="Token público">
                <span v-else class="token-value">{{ tracker.r_token_publico || 'Não informado' }}</span>
            </div>
            
            <div class="tracker-token">
                <span class="info-label">Senha</span>
                <input v-if="trackerVModel" type="text" v-model="trackerVModel.r_senha" placeholder="Nova senha">
                <span v-else class="token-value"> ********** </span>
            </div>
        </div>

        <div :class="{'tracker-detailed': true,'hide': !detailed}">
            <div class="tracker-detailed-actions">
                <button type="button" @click="$emit('ouvintes')"> Ouvintes </button>
                <button type="button" @click="$emit('lococulta')"> Localização oculta </button>
                <button type="button" @click="$emit('rastrear')"> Rastrear </button>
            </div>

            <div class="tracker-ouvinte-actions">
                <template v-if="tracker.ur_status === 4">
                    <button type="button" class="action-accept" aria-label="Aceitar vínculo" @click="$emit('accept', tracker.ur_id)"> ✓ </button>
                    <button type="button" class="action-decline" aria-label="Recusar vínculo" @click="$emit('decline', tracker.ur_id)"> ✕ </button>
                </template>

                <template v-else-if="tracker.ur_status === 5">
                    <button type="button" class="action-accept" aria-label="Aceitar transferência" @click="$emit('accept-transfer', tracker.ur_id)"> ✓ </button>
                    <button type="button" class="action-decline" aria-label="Recusar transferência" @click="$emit('decline-transfer', tracker.ur_id)" > ✕ </button>
                </template>

                <button v-if="tracker.ur_status !== 5" type="button" class="action-delete" aria-label="Excluir vínculo" @click="$emit('delete', tracker.ur_id)"> Excluir vínculo </button>
            </div>
        </div>
    </div>
</template>

<script>
import UserTrackers from '../../scripts/UserTracker/UserTrackers.js';
import { compararObjetos, compararVariaveis } from '../../scripts/utils.js';
import SelectInput from '../utils/SelectInput.vue';

export default {
    name: 'TrackerCard',
    emits: ['ouvintes', 'rastrear', 'lococulta', 'accept', 'decline', 'accept-transfer', 'decline-transfer', 'delete', 'update:tracker'],
    data() {
        return {
            UserTrackers,
            detailed: false,
            ur_statuses: [
                {id: 1, for_ouvinte: 'Operante', color: 'green'},
                {id: 2, for_ouvinte: 'Rastreador pausado', color: 'yellow'},
                {id: 3, for_ouvinte: 'Aguardando resposta do dono', color: 'orange'},
                {id: 4, for_ouvinte: 'Proposta de Inclusão Pendente', color: 'blue'},
                {id: 5, for_ouvinte: 'Proposta para ser o dono', color: 'magenta'},
            ],
            r_statuses: [
                {id: 1, nome: 'Operante', color: 'green'},
                {id: 2, nome: 'Localização desativada', color: 'yellow'},
                {id: 3, nome: 'Em transferência', color: 'red'},
                {id: 4, nome: 'Desativado', color: 'black'}
            ],

            trackerVModel: null
        }
    },
    methods: {
        extractVModelOfTracker() {
            let v = {};
            v.ur_nome = this.tracker.ur_nome;
            v.r_status = this.tracker.r_status;
            v.r_token_publico = this.tracker.r_token_publico;
            v.r_senha = null;
            return v;
        },
        startEditingTracker() {
            this.trackerVModel = this.extractVModelOfTracker();
        },
        cancelEditingTracker() {
            this.trackerVModel = null;
        },
        async saveUrChanges() {
            if (compararVariaveis(this.trackerVModel.ur_nome, this.tracker.ur_nome) === 0) {
                return;
            }
            const r = await UserTrackers.atualizaVinculoUsuarioRastreador(this.tracker.ur_id, this.tracker.ur_status, {ur_nome: this.trackerVModel.ur_nome});
            if (r) {
                let updatedTracker = {...this.tracker};
                updatedTracker.ur_nome = r.nome;
                this.$emit('update:tracker', updatedTracker);
            }
        },
        async saveRChanges() {
            let new_values = {};
            if (compararVariaveis(this.trackerVModel.r_status, this.tracker.r_status) !== 0) {
                new_values.status = this.trackerVModel.r_status;
            }
            if (compararVariaveis(this.trackerVModel.r_token_publico, this.tracker.r_token_publico) !== 0) {
                new_values.token_publico = this.trackerVModel.r_token_publico;
            }
            if (compararVariaveis(this.trackerVModel.r_senha, this.tracker.r_senha) !== 0) {
                new_values.senha = this.trackerVModel.r_senha;
            }

            if (Object.keys(new_values).length === 0) {
                return;
            }

            const r = await UserTrackers.atualizarRastreador(this.tracker.r_id, this.tracker.r_token_publico, this.tracker.r_status, new_values);
            if (r) {
                let updatedTracker = {...this.tracker};
                updatedTracker.r_status = r.status;
                updatedTracker.r_token_publico = r.token_publico;
                this.trackerVModel.r_senha = null;
                this.$emit('update:tracker', updatedTracker);
            }
        },
        saveAllChanges() {
            this.saveUrChanges();
            this.saveRChanges();
        }
    },
    props: {
        tracker: {
            type: Object,
            required: true
        }
    },
    components: {
        SelectInput
    },
    computed: {
        someInfoIsEdited() {
            if (!this.trackerVModel) return false;
            return (
                compararObjetos(this.trackerVModel, this.extractVModelOfTracker()) !== 0
            );
        }
    }
}

</script>


<style scoped>
.tracker-card {
    width: 100%;
    max-width: 300px;
    box-sizing: border-box;

    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;

    color: #1f2937;
    font-family: Arial, Helvetica, sans-serif;

    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);

    overflow: hidden;
    transition:
        box-shadow 0.2s ease,
        border-color 0.2s ease;
}

.tracker-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 5px 16px rgba(15, 23, 42, 0.10);
}


.tracker-principal {
    padding: 16px;
    cursor: pointer;
}


.tracker-header {
    display: flex;
    align-items: center;
    gap: 9px;

    min-width: 0;
    margin-bottom: 12px;
}

.tracker-id {
    flex-shrink: 0;

    padding: 4px 6px;
    border-radius: 5px;

    background: #f1f5f9;
    color: #64748b;

    font-size: 11px;
    font-weight: 600;
}

.tracker-name {
    flex: 1;
    min-width: 0;

    margin: 0;

    color: #1e293b;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;

    overflow-wrap: anywhere;
}

.expand-indicator {
    flex-shrink: 0;
    color: #94a3b8;
    font-size: 25px;
    border: 1px solid #94a3b8;
    border-radius: 5px;
    line-height: 1;
    aspect-ratio: 1;
}


.tracker-image-container {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 100%;
    height: 145px;

    margin-bottom: 14px;

    background: #f8fafc;
    border-radius: 8px;
    overflow: hidden;
}

.tracker-image {
    width: 100%;
    height: 100%;

    background-position: center;
    background-repeat: no-repeat;
    background-size: contain;
}


.tracker-info {
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.tracker-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-width: 0;
}

.info-label {
    flex-shrink: 0;

    color: #94a3b8;
    font-size: 11px;
    font-weight: 500;
}

.info-value {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 2px;
    gap: 6px;
    border: 1px solid transparent;
    border-radius: 6px;
    min-width: 0;
}

.status-indicator {
    flex-shrink: 0;

    width: 7px;
    height: 7px;

    border-radius: 50%;
}

.status-text {
    color: #475569;
    font-size: 12px;
    text-align: right;
    overflow-wrap: anywhere;
}

.owner-name {
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    text-align: right;
    overflow-wrap: anywhere;
}


.tracker-token {
    display: flex;
    flex-direction: column;
    gap: 5px;

    margin-top: 14px;
    padding-top: 11px;

    border-top: 1px solid #f1f5f9;
}

.token-value {
    color: #64748b;
    font-family: monospace;
    font-size: 11px;
    line-height: 1.5;

    overflow-wrap: anywhere;
    user-select: text;
}


.tracker-detailed {
    display: flex;
    flex-direction: column;
    gap: 12px;

    padding: 0 16px 16px;
    border-top: 1px solid #f1f5f9;
}

.tracker-detailed.hide {
    display: none;
}

.tracker-detailed-actions {
    display: flex;
    flex-direction: column;
    gap: 7px;

    padding-top: 12px;
}

.tracker-detailed button {
    font-family: inherit;
    font-size: 12px;
    font-weight: 500;

    border: 1px solid #e2e8f0;
    border-radius: 6px;

    padding: 9px 10px;

    background: #ffffff;
    color: #475569;

    cursor: pointer;
    transition:
        background 0.15s ease,
        border-color 0.15s ease;
}

.tracker-detailed button:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.tracker-ouvinte-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.tracker-ouvinte-actions .action-accept, .action-accept,
.tracker-ouvinte-actions .action-decline, .action-decline {
    width: 36px;
    height: 34px;
    padding: 0;

    font-size: 16px;
}

.tracker-ouvinte-actions .action-accept, .action-accept {
    color: #15803d;
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.tracker-ouvinte-actions .action-accept:hover, .action-accept:hover {
    background: #dcfce7;
}

.tracker-ouvinte-actions .action-decline, .action-decline {
    color: #b91c1c;
    border-color: #fecaca;
    background: #fef2f2;
}

.tracker-ouvinte-actions .action-decline:hover, .action-decline:hover {
    background: #fee2e2;
}

.tracker-ouvinte-actions .action-delete {
    margin-left: auto;

    color: #b91c1c;
    border-color: #fecaca;
}

.tracker-ouvinte-actions .action-delete:hover {
    background: #fef2f2;
}

.tracker-status:hover, .tracker-status:focus {
    border: 1px solid #e2e8f0;
}
</style>

