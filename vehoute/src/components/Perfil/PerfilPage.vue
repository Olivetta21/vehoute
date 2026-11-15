<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <div class="perfil-page" v-if="userProfile">
            <div>
                <h1>#{{Usuario.id}} - Dados Pessoais</h1>
                <div class="info">
                    <label for="user-nome">Nome</label>
                    <input id="user-nome" type="text" v-model="userProfile.v_model_nome">
                </div>
                <div class="credencial">
                    <div class="info">
                        <label for="user-login">Login</label>
                        <input id="user-login" type="text" v-model="userProfile.v_model_login" disabled>
                    </div>
                    <div class="info">
                        <label for="user-pass">Senha</label>
                        <input id="user-pass" type="password" v-model="userProfile.v_model_senha">
                    </div>
                </div>
                <div class="info">
                    <label>
                        <p>Identidade</p>
                        <SelectInput
                            :searchFn="getLegalIdentTipos"
                            :searchAll="true"
                            :selectedId="this.userProfile.liv_model_li_tipo_id"
                            @update:value="[this.userProfile.liv_model_regex, this.userProfile.liv_model_li_tipo_id] = [$event?.regex || null, $event?.id || null]"
                        />
                        <input type="text" v-model="userProfile.liv_model_identidade" placeholder="Identidade">
                        <p v-if="!validarRegex(userProfile.liv_model_identidade, this.userProfile.liv_model_regex)" class="regex"> inválido </p>
                        <button v-if="userProfileIdentidadeChanged" @click="salvarIdentidadeLegal">Salvar Identidade</button>
                    </label>
                </div>
                <div>
                    <p>Rastreadores: {{ userProfile.total_rastreadores }}</p>
                    <p>Rastreadores Registrados: {{ userProfile.total_rastreadores_registrados }}</p>
                    <p>Ouvinte em: {{ userProfile.total_ouvinte }}</p>
                </div>
            </div>
            <div>
                <h1>Contato</h1>
                <div class="info">
                    <label for="user-email">Email</label>
                    <input id="user-email" type="email" v-model="userProfile.v_model_email">
                </div>
                <div class="info">
                    <label for="user-telefone">Telefone</label>
                    <input id="user-telefone" type="tel" v-model="userProfile.v_model_telefone">
                </div>
                <div class="buttons" v-if="userProfileInfosChanged">
                    <button @click="atualizarPerfilUsuario">Salvar</button>
                    <button @click="setUserProfileInfos(this.userProfile)">Cancelar</button>
                </div>
            </div>
        </div>

        <div v-else>Perfil não carregado</div>

</template>

<script>
import Perfil from '../../scripts/PerfilPage/Perfil';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import { compararVariaveis, validarRegex } from '../../scripts/utils';
import SelectInput from '../utils/SelectInput.vue';
import Usuario from '../../scripts/LoginPage/Usuario';

export default {
    name: 'PerfilPage',
    data() {
        return {
            PagesRoutes,
            Usuario,
            validarRegex,
            Perfil,
            userProfile: null
        };
    },
    methods: {
        setUserProfileInfos(p) {
            this.userProfile = {
                ...p,
                v_model_nome: p.nome,
                v_model_login: p.login,
                v_model_senha: p.senha,
                v_model_email: p.email,
                v_model_telefone: p.telefone,
                liv_model_identidade: p.identidade,
                liv_model_li_tipo_id: p.li_tipo_id,
                liv_model_regex: p.regex
            };
        },
        async fetchUserProfile() {
            const userProfile = await Perfil.getPerfilUsuario();
            if (userProfile) {
                this.setUserProfileInfos(userProfile);
            }
        },
        async atualizarPerfilUsuario() {
            let valores = {};

            Object.keys(this.userProfile).forEach(key => {
                if (key.startsWith('v_model_')) {
                    const originalKey = key.replace('v_model_', '');
                    if (compararVariaveis(this.userProfile[key], this.userProfile[originalKey]) != 0) {
                        valores[originalKey] = this.userProfile[key];
                    }
                }
            });

            if (Object.keys(valores).length === 0) {
                console.log("Nenhuma alteração para atualizar.");
                return;
            }

            const updatedProfile = await Perfil.atualizarPerfilUsuario(valores);
            if (updatedProfile) {
                this.setUserProfileInfos(updatedProfile);
            }
        },
        async salvarIdentidadeLegal() {
            const updatedProfile = await Perfil.atualizarIdentidadeLegal(
                this.userProfile.liv_model_identidade,
                this.userProfile.liv_model_li_tipo_id
            );
            if (updatedProfile) {
                this.userProfile.li_tipo_id = updatedProfile.tipo_id;
                this.userProfile.identidade = updatedProfile.identidade;
            }
        },
        async getLegalIdentTipos() {
            const tipos = await Perfil.getSimpleLegalIdentTipos();
            console.log("Tipos de Identidade Legal:", tipos);
            return tipos;
        }
    },
    computed: {
        userProfileInfosChanged() {
            if (!this.userProfile) return false;

            return Object.keys(this.userProfile).some(key => {
                if (key.startsWith('v_model_')) {
                    const originalKey = key.replace('v_model_', '');
                    return compararVariaveis(this.userProfile[key], this.userProfile[originalKey]) != 0;
                }
                return false;
            });
        },
        userProfileIdentidadeChanged() {
            if (!this.userProfile) return false;

            return compararVariaveis(this.userProfile.liv_model_identidade, this.userProfile.identidade) != 0 ||
                   compararVariaveis(this.userProfile.liv_model_li_tipo_id, this.userProfile.li_tipo_id) != 0;
        }
    },
    mounted() {
        this.fetchUserProfile();
    },
    components: {
        HeaderTelas,
        SelectInput
    },
};
</script>

<style scoped>
    .perfil-page {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        padding: 20px;
        gap: 20px;
        flex-wrap: wrap;
        max-width: 800px;
    }

    .perfil-page > div {
        flex: 1;
        border-radius: 5px;
        max-width: 600px;
    }

    .info {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding: 10px;
    }

    .credencial {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        gap: 10px;
    }

    .regex {
        font-size: 0.8rem;
        color: red;
    }

    .regex.valid {
        color: green;
    }
</style>