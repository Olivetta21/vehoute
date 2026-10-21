<template>
    <GenericModalWindow @close="$emit('close')">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="addGroup">
                <h2>Adicionar Grupo de Rastreador</h2>
                <input v-model="groupName" placeholder="Nome do Grupo" />
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </GenericModalWindow>
</template>

<script>
import SystemTrackerPermissionGroup from '../../../scripts/SystemTrackerPermissionPage/SystemTrackerPermissionGroup.js';
import GenericModalWindow from '../../utils/GenericModalWindow.vue';

export default {
    name: 'AddGroup',
    emits: ['close', 'newGroup'],
    data() {
        return {
            groupName: '',
            STPG: SystemTrackerPermissionGroup
        };
    },
    methods: {
        async addGroup() {
            if (this.groupName.trim() === '') {
                alert('O nome do grupo não pode estar vazio.');
                return;
            }
            const newGroup = await this.STPG.addGrupo(this.groupName);
            if (newGroup) {
                this.$emit('newGroup');
            }
        }
    },
    components: {
        GenericModalWindow
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