<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <div class="perfil-page" v-if="userProfile">
            <div>
                <h1>Dados Pessoais</h1>
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
import { compararVariaveis } from '../../scripts/utils';

export default {
    name: 'PerfilPage',
    data() {
        return {
            PagesRoutes,
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
                v_model_telefone: p.telefone
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
        }
    },
    computed: {
        userProfileInfosChanged() {
            console.log("Checking if user profile infos changed...");
            if (!this.userProfile) return false;

            return Object.keys(this.userProfile).some(key => {
                if (key.startsWith('v_model_')) {
                    const originalKey = key.replace('v_model_', '');
                    return compararVariaveis(this.userProfile[key], this.userProfile[originalKey]) != 0;
                }
                return false;
            });
        }
    },
    mounted() {
        this.fetchUserProfile();
    },
    components: {
        HeaderTelas
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
</style>