<template>
    <div class="tracker-card">        
        <div class="tracker-principal" @click="detailed = !detailed">
            <div class="tracker-header">
                <p>{{ tracker.id ?? 'N/A' }}</p>
                <p>{{ tracker.rastreador_nome }}</p>
                <p :style="{ 'background-color': ur_statuses.find(s => s.id === tracker.ur_status)?.color || 'gray' }"
                    class="tracker-status"> </p>  
            </div>
            <div class="tracker-token">
                {{ tracker.token_publico }}
            </div>
            <div class="tracker-image-container">
                <div class="tracker-image" :style="'background-image: url(' + (tracker.imagem || '/api/imagens/card_systracker.png') + ');'"></div>
            </div>
            <div class="tracker-urstatus">
                {{ ur_statuses.find(s => s.id === tracker.ur_status)?.for_ouvinte || tracker.ur_status || 'N/A' }}
            </div>
            <div class="tracker-rstatus">
                {{ r_statuses.find(s => s.id === tracker.r_status)?.nome || tracker.r_status || 'N/A' }}
            </div>
            <div class="tracker-dono">
                {{ tracker.dono_nome }}
            </div>
        </div>
        <div :class="{'tracker-detailed': true, 'hide': !this.detailed}">
            <div class="tracker-detailed-actions">
                <button @click="$emit('ouvintes')"> Ouvintes </button>
                <button @click="$emit('rastrear')"> Rastrear </button>
            </div>
            <div class="tracker-ouvinte-actions">
                <template v-if="tracker.ur_status === 4">
                    <button @click="$emit('accept', tracker.id)"> ✅ </button>
                    <button @click="$emit('decline', tracker.id)"> ❌ </button>
                </template>
                <template v-else-if="tracker.ur_status === 5">
                    <button @click="$emit('accept-transfer', tracker.id)"> ✅ </button>
                    <button @click="$emit('decline-transfer', tracker.id)"> ❌ </button>
                </template>
                <template v-if="tracker.ur_status !== 5">
                    <button @click="$emit('delete', tracker.id)"> 🗑️ </button>
                </template>
            </div>
        </div>    
    </div>

</template>

<script>
export default {
    name: 'TrackerCard',    
    emits: ['ouvintes', 'rastrear', 'accept', 'decline', 'accept-transfer', 'decline-transfer', 'delete'],
    data() {
        return {
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
            ]
        }
    },
    props: {
        tracker: {
            type: Object,
            required: true
        }
    }
}

</script>

<style scoped>
.tracker-card {
    border: 1px solid var(--colorA2);
    box-shadow: 3px 3px 11px #00000061;
    padding: 5px;
    border-radius: 5px;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
}
.tracker-card:hover, .tracker-card:focus {
    border-color: var(--colorA1);
    box-shadow: 4px 4px 14px #00000091;
    transform: scale(1.02);
}

.tracker-principal, .tracker-detailed {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 200px;
    aspect-ratio: 1 / 1.4;
    transition: all 0.3s ease;
}

.tracker-header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 0.9rem;
}

.tracker-status {    
    height: 1em;
    aspect-ratio: 1;
    border-radius: 50%;
}

.tracker-image {
    aspect-ratio: 1;
    max-width: 140px;
    background-size: cover;
}

.tracker-detailed.hide {
    width: 0;
    padding: 0;
    overflow: hidden;
    opacity: 0;
    margin: 0;
    pointer-events: none;
}

.tracker-ouvinte-actions button {
    border: none;
    background: none;
    cursor: pointer;
    padding: 2px;
}



</style>