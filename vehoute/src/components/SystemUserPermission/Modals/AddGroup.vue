<template>
    <GenericModalWindow @close="$emit('close')">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="addGroup">
                <h2>Adicionar Grupo</h2>
                <input v-model="groupName" placeholder="Nome do Grupo" />
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </GenericModalWindow>
</template>

<script>
import ToastMessage from '../../../scripts/ToastMessage/ToastMessage.js';
import SystemUserPermissionGroup from '../../../scripts/SystemUserPermissionPage/SystemUserPermissionGroup.js';
import GenericModalWindow from '../../utils/GenericModalWindow.vue';

export default {
    name: 'AddGroup',
    emits: ['close', 'newGroup'],
    data() {
        return {
            groupName: '',
            SUPG: SystemUserPermissionGroup
        };
    },
    methods: {
        async addGroup() {
            if (this.groupName.trim() === '') {
                ToastMessage.add('O nome do grupo não pode estar vazio.', '', 'error', true);
                return;
            }
            const newGroup = await this.SUPG.addGrupo(this.groupName);
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
