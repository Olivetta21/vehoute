<template>
    <div class="ouvinte-nome">
        <p> {{ ouvinte.u_nome }} </p>
        <div class="ouvinte-status">
            <p :style="{ 'background-color': ur_statuses.find(s => s.id === ouvinte.ur_status)?.color || 'gray' }"
                class="ouvinte-status-colour"></p>
            <p>
                {{ ur_statuses.find(s => s.id === ouvinte.ur_status)?.for_dono || ouvinte.ur_status || 'N/A' }}
            </p>
        </div>
    </div>
    <div class="ouvinte-contato">
        <p> {{ ouvinte.u_email }} </p>
        <p> {{ ouvinte.u_telefone }} </p>
    </div>
    <div class="ouvinte-locs">
        <div class="check-field">
            <SwitchButtonInput
                :value="ouvinte.ur_loc_temporeal"
                @update:value="$emit('locte', { ur_id: ouvinte.ur_id, new_value: $event })"
            />
            <p>Loc. Tempo Real</p>
        </div>
        <div class="check-field">
            <SwitchButtonInput
                :value="ouvinte.ur_loc_salvos"
                @update:value="$emit('locsa', { ur_id: ouvinte.ur_id, new_value: $event })"
            />
            <p>Loc. Salvos</p>
        </div>
    </div>
    <div class="ouvinte-actions">
        <template v-if="ouvinte.ur_status === 1 || ouvinte.ur_status === 2">
            <button v-if="ouvinte.ur_status === 1" class="btn-secondary btn-sm" @click="$emit('pause', { ur_id: ouvinte.ur_id, atual_status_id: 1 })"> Pausar </button>
            <button v-if="ouvinte.ur_status === 2" class="btn-secondary btn-sm" @click="$emit('resume', { ur_id: ouvinte.ur_id, atual_status_id: 2 })"> Retomar </button>
            <button v-if="ouvinte.u_id !== Usuario.id" class="btn-secondary btn-sm" @click="$emit('possechange', ouvinte.ur_id)"> Transferir posse </button>
            <!--button> 📍 </button-->
        </template>
        <template v-else-if="ouvinte.ur_status === 3">
            <button class="btn-sm" @click="$emit('accept', ouvinte.ur_id)"> Aceitar </button>
            <button class="btn-secondary btn-sm" @click="$emit('decline', ouvinte.ur_id)"> Recusar </button>
        </template>
        <button v-else-if="ouvinte.ur_status === 5" class="btn-secondary btn-sm" @click="$emit('cancelpossechange', ouvinte.ur_id)"> Cancelar transferência </button>
        <button v-if="ouvinte.ur_status !== 5 && ouvinte.ur_status !== 3" class="red-button btn-sm" @click="$emit('exclude', ouvinte.ur_id)"> Excluir </button>
    </div>
</template>

<script>
import Usuario from '../../scripts/LoginPage/Usuario';
import SwitchButtonInput from '../utils/SwitchButtonInput.vue';

export default {
    name: 'OuvinteCard',
    emits: ['pause', 'resume', 'accept', 'decline', 'possechange', 'cancelpossechange', 'exclude', 'hideloc', 'locte', 'locsa'],
    props: {
        ouvinte: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            Usuario,
            ur_statuses: [
                {id: 1, for_dono: 'Normal', color: 'green'},
                {id: 2, for_dono: 'Pausado', color: 'yellow'},
                {id: 3, for_dono: 'Ouvinte pendente', color: 'orange'},
                {id: 4, for_dono: 'Aguardando resposta do ouvinte', color: 'blue'},
                {id: 5, for_dono: 'Aguardando resposta do ouvinte', color: 'magenta'},
            ],
        }
    },
    components: {
        SwitchButtonInput
    }
}
</script>

<style scoped>
.ouvinte-nome,
.ouvinte-contato,
.ouvinte-locs { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; overflow-wrap: anywhere; }
.ouvinte-nome > p { font-weight: var(--weight-bold); }
.ouvinte-contato { color: var(--color-text-muted); font-size: var(--font-sm); }
.check-field { display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-sm); }
.ouvinte-status { display: flex; align-items: center; gap: var(--space-2); color: var(--color-text-muted); font-size: var(--font-sm); }
.ouvinte-status-colour { flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%; }
.ouvinte-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: var(--space-2); }
</style>
