<template>
    <GenericModalWindow @close="$emit('close')">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="addUsuarioNoGrupo">
                <h2>Adicionar usuario ao Grupo: {{ groupName }}</h2>
                <SelectInput 
                    :searchFn="searchUsuarios"
                    placeholder="Selecione um usuário"
                    searchPlaceholder="Nome usuário"
                    @update:value="usuario = $event"
                />
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </GenericModalWindow>
</template>

<script>
import SelectInput from '../../utils/SelectInput.vue';
import SystemUserPermissionGroup from '../../../scripts/SystemUserPermissionPage/SystemUserPermissionGroup.js';
import GenericModalWindow from '../../utils/GenericModalWindow.vue';
import ToastMessage from '../../../scripts/ToastMessage/ToastMessage.js';

export default {
    name: 'AddUsuarioGrupo',
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
    emits: ['close', 'newUser'],
    data() {
        return {
            usuario: null,
            SUPG: SystemUserPermissionGroup
        };
    },
    methods: {
        async addUsuarioNoGrupo() {
            if (!this.usuario) {
                ToastMessage.add('Por favor, selecione um usuário.', '', 'error', true);
                return;
            }
            const newUser = await this.SUPG.addUsuarioNoGrupo(this.usuario.id, this.groupId);
            if (newUser) {
                this.$emit('newUser');
            }
        },
        async searchUsuarios(searchText) {
            let users = await this.SUPG.getUsuariosDisponiveis(searchText);
            users.forEach(user => {
                user.nome = `${user.id}: ${user.nome}`;
            });
            return users;
        }
    },
    components: {
        GenericModalWindow,
        SelectInput
    },
}
</script>
