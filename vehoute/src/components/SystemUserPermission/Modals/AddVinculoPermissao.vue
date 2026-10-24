<template>
    <GenericModalWindow @close="$emit('close')">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="addVinculoPermissao">
                <h2>Vincular Permissão</h2>
                <SelectInput
                    :searchFn="searchEntidades"
                    placeholder="Selecione um usuário ou grupo"
                    searchPlaceholder="Nome usuário ou grupo"
                    @update:value="UsuariouOuGrupo = $event"
                    :preSelectedValue="preselectedUsuarioOuGrupo"
                />
                <SelectInput 
                    :searchFn="searchPermissoes"
                    placeholder="Selecione uma permissão"
                    searchPlaceholder="Nome permissão"
                    :preSelectedValue="preselectedPermissao"
                    @update:value="Permissao = $event"
                />
                <SelectInput 
                    :data="[{'id':1, 'nome': 'Permitido'}, {'id':0, 'nome': 'Negado'}]"
                    placeholder="Selecione se é permitido ou negado"
                    @update:value="Negado = $event"
                />
                <button type="submit">Vincular</button>
            </form>
        </div>
    </GenericModalWindow>
</template>

<script>
import SelectInput from '../../utils/SelectInput.vue';
import SystemUserPermission from '../../../scripts/SystemUserPermissionPage/SystemUserPermission.js';
import GenericModalWindow from '../../utils/GenericModalWindow.vue';
import ToastMessage from '../../../scripts/ToastMessage/ToastMessage.js';

export default {
    name: 'AddVinculoPermissao',
    emits: ['close', 'newVinculo'],
    props: {
        preselectedUsuarioOuGrupo: {
            type: Object,
            required: false,
            default: null
        },
        preselectedPermissao: {
            type: Object,
            required: false,
            default: null
        }
    },
    mounted() {
        if (this.preselectedUsuarioOuGrupo) {
            this.UsuariouOuGrupo = this.preselectedUsuarioOuGrupo;
        }
        if (this.preselectedPermissao) {
            this.Permissao = this.preselectedPermissao;
        }
    },
    data() {
        return {
            UsuariouOuGrupo: null,
            Permissao: null,
            Negado: null,
            SUP: SystemUserPermission
        };
    },
    methods: {
        async addVinculoPermissao() {
            if (!this.UsuariouOuGrupo || !this.Permissao) {
                ToastMessage.add('Por favor, selecione um usuário e uma permissão.', '', 'error', true);
                return;
            }
            if (!this.Negado) {
                ToastMessage.add('Por favor, selecione se a permissão é permitida ou negada.', '', 'error', true);
                return;
            }

            const newVinculo = await this.SUP.addVinculoPermissao(this.UsuariouOuGrupo.id, this.UsuariouOuGrupo.is_user, this.Negado.nome === 'Negado', this.Permissao.id);
            if (newVinculo) {
                console.log('Vinculo adicionado com sucesso:', newVinculo);
                this.$emit('newVinculo');
            }
        },
        async searchEntidades(searchText) {
            let entidades = [];

            let usuarios = await this.SUP.getUsuariosDisponiveis(searchText);
            usuarios.forEach(usuario => {
                usuario.is_user = true;
            });
            let grupos = await this.SUP.getGruposDisponiveis(searchText);
            grupos.forEach(grupo => {
                grupo.is_user = false;
            });

            entidades = [...usuarios, ...grupos];
            entidades.sort((a, b) => a.nome.localeCompare(b.nome));
            entidades.forEach(entidade => {
                entidade.nome = `${entidade.id}: ${entidade.nome}`;
            });

            return entidades;
        },
        async searchPermissoes(searchText) {
            let permissoes = await this.SUP.getPermissoesDisponiveis(searchText);
            permissoes.forEach(permissao => {
                permissao.nome = `${permissao.id}: ${permissao.nome}`;
            });
            return permissoes;
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