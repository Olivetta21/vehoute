<template>
    <GenericModalWindow @close="$emit('close')">
        <div class="modal-container">
            <form class="modal-form" @submit.prevent="addVinculoPermissao">
                <h2>Vincular Permissão</h2>
                <SelectInput
                    :searchFn="searchEntidades"
                    placeholder="Selecione um rastreador ou grupo"
                    searchPlaceholder="Nome rastreador ou grupo"
                    @update:value="RastreadorOuGrupo = $event"
                    :preSelectedValue="preselectedRastreadorOuGrupo"
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
import SystemTrackerPermission from '../../../scripts/SystemTrackerPermissionPage/SystemTrackerPermission.js';
import GenericModalWindow from '../../utils/GenericModalWindow.vue';

export default {
    name: 'AddVinculoPermissao',
    emits: ['close', 'newVinculo'],
    props: {
        preselectedRastreadorOuGrupo: {
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
        if (this.preselectedRastreadorOuGrupo) {
            this.RastreadorOuGrupo = this.preselectedRastreadorOuGrupo;
        }
        if (this.preselectedPermissao) {
            this.Permissao = this.preselectedPermissao;
        }
    },
    data() {
        return {
            RastreadorOuGrupo: null,
            Permissao: null,
            Negado: null,
            STP: SystemTrackerPermission
        };
    },
    methods: {
        async addVinculoPermissao() {
            if (!this.RastreadorOuGrupo || !this.Permissao) {
                alert('Por favor, selecione um rastreador ou grupo e uma permissão.');
                return;
            }
            if (!this.Negado) {
                alert('Por favor, selecione se a permissão é permitida ou negada.');
                return;
            }

            const newVinculo = await this.STP.addVinculoPermissao(this.RastreadorOuGrupo.id, this.RastreadorOuGrupo.is_tracker, this.Negado.nome === 'Negado', this.Permissao.id);
            if (newVinculo) {
                console.log('Vínculo adicionado com sucesso:', newVinculo);
                this.$emit('newVinculo');
            }
        },
        async searchEntidades(searchText) {
            let entidades = [];

            let rastreadores = await this.STP.getRastreadoresDisponiveis(searchText);
            rastreadores.forEach(rastreador => {
                rastreador.is_tracker = true;
            });

            let grupos = await this.STP.getGruposDisponiveis(searchText);
            grupos.forEach(grupo => {
                grupo.is_tracker = false;
            });

            entidades = [...rastreadores, ...grupos];
            entidades.sort((a, b) => (a.nome || '').localeCompare(b.nome || ''));
            entidades.forEach(entidade => {
                entidade.nome = `${entidade.id}: ${entidade.nome}`;
            });

            return entidades;
        },
        async searchPermissoes(searchText) {
            let permissoes = await this.STP.getPermissoesDisponiveis(searchText);
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