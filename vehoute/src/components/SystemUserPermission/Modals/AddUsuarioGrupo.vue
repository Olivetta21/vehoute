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
                alert('Por favor, selecione um usuário.');
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