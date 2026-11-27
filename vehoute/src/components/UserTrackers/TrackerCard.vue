<template>
    <div class="tracker-card card">
        <div class="tracker-principal" @click="(trackerVModel) ? null :detailed = !detailed">
            <div class="tracker-header">
                <span class="tracker-id"> #{{ tracker.ur_id ?? '~' }}</span>

                <h3 class="tracker-name">{{ (trackerVModel) ? '' : tracker.ur_nome || 'Rastreador sem nome' }}</h3>
                
                <template v-if="Usuario.hasPermission('Modificar Rastreador')">
                    <span v-if="!trackerVModel" class="edit-indicator" title="Editar" @click="startEditingTracker"> ✎ </span>
                    <template v-else>
                        <button v-if="someInfoIsEdited" type="button" class="action-accept" @click="saveAllChanges"> ✓ </button>
                        <button type="button" class="action-decline" @click="cancelEditingTracker"> ✕ </button>
                    </template>
                </template>
                
                <span class="expand-indicator" @click.stop="detailed = !detailed">{{ detailed ? '−' : '+' }}</span>
            </div>

            <input v-if="trackerVModel && tracker.ur_id != null" type="text" @click.stop v-model="trackerVModel.ur_nome" placeholder="Nome do rastreador">
            <div class="tracker-image-container">
                <div class="tracker-image"
                    :style="{backgroundImage: `url('${tracker.r_imagem || '/api/imagens/card_systracker.png'}')`}"
                ></div>
            </div>

            <div class="tracker-info">
                <div class="tracker-info-row" v-if="conexao !== 'oculta'">
                    <span class="info-label">Conexão</span>
                    <span :class="['badge', { 'is-success': conexao === 'conectado' }]" role="status">{{ textosConexao[conexao] }}</span>
                </div>

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
                        <template v-if="trackerVModel && tracker.dono_id == Usuario.id">
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
                <input v-if="trackerVModel && tracker.dono_id == Usuario.id" type="text" v-model="trackerVModel.r_token_publico" placeholder="Token público">
                <span v-else class="token-value">{{ tracker.r_token_publico || 'Não informado' }}</span>
            </div>
            
            <div class="tracker-token">
                <span class="info-label">Senha</span>
                <input v-if="trackerVModel && tracker.dono_id == Usuario.id" type="password" autocomplete="new-password" v-model="trackerVModel.r_senha" placeholder="Nova senha">
                <span v-else class="token-value"> ********** </span>
            </div>
        </div>

        <div :class="{'tracker-detailed': true,'hide': !detailed}">
            <div class="tracker-detailed-actions">
                <template v-if="tracker.dono_id == Usuario.id">
                    <button v-if="Usuario.hasPermission('Gerenciar Ouvintes')" type="button" @click="$emit('ouvintes')"> Ouvintes </button>
                    <button v-if="Usuario.hasPermission('Intervalo Oculto')" type="button" @click="$emit('lococulta')"> Localização oculta </button>
                </template>
                <button type="button"
                    v-if="tracker.ur_status == 1 && tracker.r_status == 1 && tracker.ur_ativo == true && tracker.r_ativo == true && (tracker.ur_loc_temporeal == true || tracker.ur_loc_salvos == true)"
                @click="$emit('rastrear')"> Rastrear </button>
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

                <button v-if="tracker.ur_status !== 5 && tracker.ur_id != null" type="button" class="action-delete" aria-label="Excluir vínculo" @click="$emit('delete', tracker.ur_id)"> Excluir vínculo </button>
            </div>
        </div>
    </div>
</template>

<script>
import Usuario from '../../scripts/LoginPage/Usuario.js';
import UserTrackers from '../../scripts/UserTracker/UserTrackers.js';
import { compararObjetos, compararVariaveis } from '../../scripts/utils.js';
import SelectInput from '../utils/SelectInput.vue';

export default {
    name: 'TrackerCard',
    emits: ['ouvintes', 'rastrear', 'lococulta', 'accept', 'decline', 'accept-transfer', 'decline-transfer', 'delete', 'update:tracker'],
    data() {
        return {
            UserTrackers,
            Usuario,
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

            textosConexao: {
                conectado: '● Conectado',
                desconectado: '○ Desconectado',
                verificando: 'Verificando…',
                indisponivel: 'Indisponível'
            },
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
                return true;
            }
            const r = await UserTrackers.atualizaVinculoUsuarioRastreador(this.tracker.ur_id, this.tracker.ur_status, {ur_nome: this.trackerVModel.ur_nome});
            if (r) {
                let updatedTracker = {...this.tracker};
                updatedTracker.ur_nome = r.nome;
                this.$emit('update:tracker', updatedTracker);
                await this.$nextTick();
            }
            return Boolean(r);
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
                return true;
            }

            const r = await UserTrackers.atualizarRastreador(this.tracker.r_id, this.tracker.r_token_publico, this.tracker.r_status, new_values);
            if (r) {
                let updatedTracker = {...this.tracker};
                updatedTracker.r_status = r.status;
                updatedTracker.r_token_publico = r.token_publico;
                this.trackerVModel.r_senha = null;
                this.$emit('update:tracker', updatedTracker);
            }
            return Boolean(r);
        },
        async saveAllChanges() {
            // uma de cada vez: cada resposta atualiza o cartão sem sobrescrever a outra
            const nomeOk = this.tracker.ur_id == null || await this.saveUrChanges();
            const rastreadorOk = await this.saveRChanges();
            if (nomeOk && rastreadorOk) this.trackerVModel = null;
        }
    },
    props: {
        tracker: {
            type: Object,
            required: true
        },
        conexao: {
            type: String,
            default: 'oculta'
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
    },
    mounted() {
        if (this.tracker.ur_status === 4 || this.tracker.ur_status === 5) {
            this.detailed = true;
        }
    }
}

</script>

<style scoped>
.tracker-card { overflow: hidden; }
.tracker-principal { padding: var(--space-4); cursor: pointer; }
.tracker-principal > input,
.tracker-token input { width: 100%; min-height: var(--control-height-sm); }
.tracker-principal > input { margin-bottom: var(--space-3); }

.tracker-header { display: flex; align-items: center; gap: var(--space-2); min-width: 0; margin-bottom: var(--space-3); }
.tracker-id {
    flex-shrink: 0;
    padding: 0 var(--space-2);
    color: var(--color-text-muted);
    font-size: var(--font-xs);
    font-weight: var(--weight-bold);
    background: var(--color-surface-muted);
    border-radius: var(--radius-sm);
}
.tracker-name { flex: 1; min-width: 0; font-size: var(--font-md); font-weight: var(--weight-bold); overflow-wrap: anywhere; }
.edit-indicator,
.expand-indicator {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: var(--control-height-sm);
    height: var(--control-height-sm);
    color: var(--color-text-muted);
    font-size: var(--font-lg);
    border-radius: var(--radius-sm);
}
.edit-indicator:hover,
.expand-indicator:hover { color: var(--color-primary); background: var(--color-primary-soft); }

.tracker-image-container { width: 100%; height: 140px; margin-bottom: var(--space-4); overflow: hidden; background: var(--color-surface-muted); border-radius: var(--radius-md); }
.tracker-image { width: 100%; height: 100%; background-position: center; background-repeat: no-repeat; background-size: contain; }

.tracker-info { display: flex; flex-direction: column; gap: var(--space-2); }
.tracker-info-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); min-width: 0; }
.info-label { flex-shrink: 0; color: var(--color-text-subtle); font-size: var(--font-xs); font-weight: var(--weight-medium); }
.info-value { display: flex; align-items: center; justify-content: flex-end; gap: var(--space-2); min-width: 0; }
.status-indicator { flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%; }
.status-text,
.owner-name { font-size: var(--font-sm); text-align: right; overflow-wrap: anywhere; }
.owner-name { font-weight: var(--weight-medium); }

.tracker-token { display: flex; flex-direction: column; gap: var(--space-1); margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--color-border); }
.token-value { color: var(--color-text-muted); font-family: var(--font-mono); font-size: var(--font-xs); overflow-wrap: anywhere; user-select: text; }

.tracker-detailed { display: flex; flex-direction: column; gap: var(--space-3); padding: var(--space-3) var(--space-4) var(--space-4); border-top: 1px solid var(--color-border); }
.tracker-detailed.hide { display: none; }
.tracker-detailed-actions { display: flex; flex-direction: column; gap: var(--space-2); }
.tracker-detailed-actions:empty { display: none; }
.tracker-detailed button { min-height: var(--control-height-sm); color: var(--color-text); background: var(--color-surface); border-color: var(--color-border-strong); }
.tracker-detailed button:hover { background: var(--color-surface-muted); }
.tracker-ouvinte-actions { display: flex; align-items: center; gap: var(--space-2); }

button.action-accept,
button.action-decline { width: var(--control-height-sm); min-height: var(--control-height-sm); flex-shrink: 0; padding: 0; }
.tracker-card button.action-accept { color: var(--color-success); background: var(--color-success-soft); border-color: transparent; }
.tracker-card button.action-accept:hover { border-color: var(--color-success); }
.tracker-card button.action-decline { color: var(--color-danger); background: var(--color-danger-soft); border-color: transparent; }
.tracker-card button.action-decline:hover { border-color: var(--color-danger); }
.tracker-card .tracker-ouvinte-actions .action-delete { margin-left: auto; color: var(--color-danger); border-color: var(--color-border); }
.tracker-card .tracker-ouvinte-actions .action-delete:hover { background: var(--color-danger-soft); border-color: var(--color-danger); }
</style>
