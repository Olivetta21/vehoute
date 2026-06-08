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
        <p> {{ ouvinte.email }} </p>
        <p> {{ ouvinte.telefone }} </p>
    </div>
    <div class="ouvinte-locs">
        <div class="check-field">
            <input :id="'loc_tempo_real'+ouvinte.id" type="checkbox" :checked="ouvinte.loc_temporeal" />
            <label :for="'loc_tempo_real'+ouvinte.id">Loc. Tempo Real</label>
        </div>
        <div class="check-field">
            <input :id="'loc_salvos'+ouvinte.id" type="checkbox" :checked="ouvinte.loc_salvos" />
            <label :for="'loc_salvos'+ouvinte.id">Loc. Salvos</label>
        </div>
    </div>
    <div class="ouvinte-actions">
        <template v-if="ouvinte.ur_status === 1 || ouvinte.ur_status === 2">
            <button v-if="ouvinte.ur_status === 1" @click="$emit('pause', { ur_id: ouvinte.id, atual_status_id: 1 })"> ⏸️ </button>
            <button v-if="ouvinte.ur_status === 2" @click="$emit('resume', { ur_id: ouvinte.id, atual_status_id: 2 })"> ▶️ </button>
            <button v-if="ouvinte.usuario_id !== Usuario.id" @click="$emit('possechange', ouvinte.id)"> 👑 </button>
            <!--button> 📍 </button-->
        </template>
        <template v-else-if="ouvinte.ur_status === 3">
            <button @click="$emit('accept', ouvinte.id)"> ✅ </button>
            <button @click="$emit('decline', ouvinte.id)"> ❌ </button>
        </template>
        <button v-else-if="ouvinte.ur_status === 5" @click="$emit('cancelpossechange', ouvinte.id)"> ❌ </button>
        <button v-if="ouvinte.ur_status !== 5 && ouvinte.ur_status !== 3" @click="$emit('exclude', ouvinte.id)"> 🗑️ </button>
    </div>
</template>

<script>
import Usuario from '@/scripts/LoginPage/Usuario';

export default {
    name: 'OuvinteCard',
    emits: ['pause', 'resume', 'accept', 'decline', 'possechange', 'cancelpossechange', 'exclude', 'hideloc'],
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