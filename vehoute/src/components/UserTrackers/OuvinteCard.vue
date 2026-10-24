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
            <button v-if="ouvinte.ur_status === 1" @click="$emit('pause', { ur_id: ouvinte.ur_id, atual_status_id: 1 })"> ⏸️ </button>
            <button v-if="ouvinte.ur_status === 2" @click="$emit('resume', { ur_id: ouvinte.ur_id, atual_status_id: 2 })"> ▶️ </button>
            <button v-if="ouvinte.u_id !== Usuario.id" @click="$emit('possechange', ouvinte.ur_id)"> 👑 </button>
            <!--button> 📍 </button-->
        </template>
        <template v-else-if="ouvinte.ur_status === 3">
            <button @click="$emit('accept', ouvinte.ur_id)"> ✅ </button>
            <button @click="$emit('decline', ouvinte.ur_id)"> ❌ </button>
        </template>
        <button v-else-if="ouvinte.ur_status === 5" @click="$emit('cancelpossechange', ouvinte.ur_id)"> ❌ </button>
        <button v-if="ouvinte.ur_status !== 5 && ouvinte.ur_status !== 3" @click="$emit('exclude', ouvinte.ur_id)"> 🗑️ </button>
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

.ouvinte-nome, .ouvinte-contato, .ouvinte-locs {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.check-field {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ouvinte-status {
    display: flex;
    flex-direction: row;
}

.ouvinte-status-colour {    
    height: 1em;
    aspect-ratio: 1;
    border-radius: 50%;
}

.ouvinte-actions button{
    border: none;
    background: none;
    cursor: pointer;
    padding: 2px;
}

</style>