<template>
    <GenericModalWindow @close="$emit('close')">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="addRastreadorNoGrupo">
                <h2>Adicionar rastreador ao Grupo: {{ groupName }}</h2>
                <SelectInput 
                    :searchFn="searchRastreadores"
                    placeholder="Selecione um rastreador"
                    searchPlaceholder="Nome rastreador"
                    @update:value="rastreador = $event"
                />
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </GenericModalWindow>
</template>

<script>
import SelectInput from '../../utils/SelectInput.vue';
import SystemTrackerPermissionGroup from '../../../scripts/SystemTrackerPermissionPage/SystemTrackerPermissionGroup.js';
import GenericModalWindow from '../../utils/GenericModalWindow.vue';

export default {
    name: 'AddRastreadorGrupo',
    props: {
        groupId: {
            type: Number,
            required: true
        },
        groupName: {
            type: String,
            required: true
        }
    },
    emits: ['close', 'newTracker'],
    data() {
        return {
            rastreador: null,
            STPG: SystemTrackerPermissionGroup
        };
    },
    methods: {
        async addRastreadorNoGrupo() {
            if (!this.rastreador) {
                alert('Por favor, selecione um rastreador.');
                return;
            }
            const newTracker = await this.STPG.addRastreadorNoGrupo(this.rastreador.id, this.groupId);
            if (newTracker) {
                this.$emit('newTracker');
            }
        },
        async searchRastreadores(searchText) {
            let rastreadores = await this.STPG.getRastreadoresDisponiveis(searchText);
            rastreadores.forEach(rastreador => {
                rastreador.nome = `${rastreador.id}: ${rastreador.nome}`;
            });
            return rastreadores;
        }
    },
    components: {
        GenericModalWindow,
        SelectInput
    },
}
</script>

<style scoped>
.modal-container {
    pointer-events: none;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-form {
    pointer-events: all;
    background: white;
    border: 1px solid var(--colorA2);
    padding: 20px;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>