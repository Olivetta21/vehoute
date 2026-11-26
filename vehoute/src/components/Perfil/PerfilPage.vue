<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <div class="perfil-page" v-if="userProfile">
            <div class="card perfil-section">
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
                        <label for="user-pass">Nova senha</label>
                        <input id="user-pass" type="password" autocomplete="new-password" v-model="userProfile.v_model_senha">
                    </div>
                    <div class="info" v-if="userProfile.v_model_senha">
                        <label for="user-pass-atual">Senha atual</label>
                        <input id="user-pass-atual" type="password" autocomplete="current-password" v-model="senhaAtual">
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
                <div class="perfil-stats">
                    <p>Rastreadores: {{ userProfile.total_rastreadores }}</p>
                    <p>Rastreadores Registrados: {{ userProfile.total_rastreadores_registrados }}</p>
                    <p>Ouvinte em: {{ userProfile.total_ouvinte }}</p>
                </div>
            </div>
            <div class="card perfil-section">
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
                    <button class="btn-secondary" @click="setUserProfileInfos(this.userProfile)">Cancelar</button>
                </div>
            </div>
        </div>

        <div v-else class="perfil-empty">Perfil não carregado</div>

</template>

<script>
import Perfil from '../../scripts/PerfilPage/Perfil';
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';
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
            userProfile: null,
            senhaAtual: ''
        };
    },
    methods: {
        setUserProfileInfos(p) {
            this.senhaAtual = '';
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

            if (valores.senha) {
                if (!this.senhaAtual) {
                    ToastMessage.add('Informe a senha atual para trocar a senha', '', 'warn');
                    return;
                }
                valores.senha_atual = this.senhaAtual;
            }

            const updatedProfile = await Perfil.atualizarPerfilUsuario(valores);
            if (updatedProfile) {
                ToastMessage.add('Perfil atualizado.', '', 'success');
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
.perfil-page { display: flex; flex-wrap: wrap; align-items: flex-start; gap: var(--space-4); width: 100%; max-width: 960px; padding: var(--space-5); }
.perfil-section { display: flex; flex: 1 1 var(--card-min-width); flex-direction: column; gap: var(--space-4); padding: var(--space-5); }
h1 { font-size: var(--font-lg); }
.info,
.info > label { display: flex; flex-direction: column; gap: var(--space-2); min-width: 0; }
.info label { color: var(--color-text-muted); font-size: var(--font-xs); font-weight: var(--weight-bold); }
.info input { width: 100%; color: var(--color-text); font-weight: normal; }
.credencial { display: flex; flex-wrap: wrap; gap: var(--space-3); }
.credencial > .info { flex: 1 1 140px; }
.perfil-stats { padding-top: var(--space-3); color: var(--color-text-muted); font-size: var(--font-sm); border-top: 1px solid var(--color-border); }
.buttons { display: flex; justify-content: flex-end; gap: var(--space-2); }
.regex { color: var(--color-danger); font-size: var(--font-xs); }
.regex.valid { color: var(--color-success); }
.perfil-empty { padding: var(--space-5); color: var(--color-text-muted); }
@media (max-width: 520px) { .perfil-page { padding: var(--space-3); } }
</style>
