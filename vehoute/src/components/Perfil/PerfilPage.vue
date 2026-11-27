<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />

        <div class="perfil-page" v-if="userProfile">
            <div class="card perfil-section">
                <h1>Dados Pessoais</h1>
                <div class="codigo-usuario">
                    <div>
                        <span class="codigo-rotulo">Seu código para compartilhar</span>
                        <strong class="codigo-valor">{{ userProfile.codigo }}</strong>
                    </div>
                    <div class="codigo-acoes">
                        <button type="button" class="btn-secondary btn-sm" @click="copiarCodigo">Copiar</button>
                        <button v-if="Usuario.hasPermission('Modificar Perfil')" type="button" class="btn-secondary btn-sm botao-recarregar" title="Gerar um novo código" aria-label="Gerar um novo código" @click="novoCodigo">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 11a8 8 0 1 0-2.3 6.4" />
                                <polyline points="20 4 20 11 13 11" />
                            </svg>
                        </button>
                    </div>
                </div>
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
                        <PasswordInput id="user-pass" autocomplete="new-password" v-model="userProfile.v_model_senha" />
                    </div>
                    <div class="info" v-if="userProfile.v_model_senha">
                        <label for="user-pass-atual">Senha atual</label>
                        <PasswordInput id="user-pass-atual" autocomplete="current-password" v-model="senhaAtual" />
                    </div>
                </div>
                <div class="info">
                    <label>
                        <p>Identidade</p>
                        <SelectInput
                            :searchFn="getLegalIdentTipos"
                            :searchAll="true"
                            :selectedId="this.userProfile.liv_model_li_tipo_id"
                            @update:value="selecionarTipoIdentidade($event)"
                        />
                        <input type="text" :value="userProfile.liv_model_identidade" @input="userProfile.liv_model_identidade = mascararCampo($event, mascara)" :placeholder="mascara || 'Identidade'">
                        <p v-if="!validarRegex(userProfile.liv_model_identidade, this.userProfile.liv_model_regex)" class="regex"> inválido </p>
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
                    <p class="dica">A troca só vale depois de confirmada pelo código enviado ao novo endereço.</p>
                </div>
                <form v-if="userProfile.email_pendente" class="email-pendente" @submit.prevent="confirmarEmail">
                    <p>Aguardando confirmação de <strong>{{ userProfile.email_pendente }}</strong></p>
                    <input v-model="codigoEmail" type="text" maxlength="8" placeholder="Código recebido" required>
                    <div class="buttons">
                        <button type="button" class="btn-secondary" @click="cancelarEmail">Cancelar troca</button>
                        <button type="submit">Confirmar email</button>
                    </div>
                </form>
                <div class="info">
                    <label for="user-telefone">Telefone</label>
                    <input id="user-telefone" type="tel" v-model="userProfile.v_model_telefone">
                </div>
            </div>
            <div class="perfil-acoes card" v-if="temAlteracoes">
                <span>Há alterações não salvas</span>
                <div class="buttons">
                    <button type="button" class="btn-secondary" @click="setUserProfileInfos(userProfile)">Cancelar</button>
                    <button type="button" @click="salvarAlteracoes">Salvar</button>
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
import { confirmar } from '../../scripts/confirmacao';
import PasswordInput from '../utils/PasswordInput.vue';
import { mascararCampo } from '../../scripts/mascara';

export default {
    name: 'PerfilPage',
    data() {
        return {
            PagesRoutes,
            validarRegex,
            Usuario,
            codigoEmail: '',
            mascara: null,
            Perfil,
            userProfile: null,
            senhaAtual: ''
        };
    },
    methods: {
        mascararCampo,
        selecionarTipoIdentidade(tipo) {
            this.userProfile.liv_model_regex = tipo?.regex || null;
            this.userProfile.liv_model_li_tipo_id = tipo?.id || null;
            this.mascara = tipo?.mascara || null;
        },
        async copiarCodigo() {
            try {
                await navigator.clipboard.writeText(this.userProfile.codigo);
                ToastMessage.add('Código copiado.', '', 'success');
            } catch {
                ToastMessage.add('Não foi possível copiar.', 'Selecione o código e copie manualmente.', 'warn');
            }
        },
        async novoCodigo() {
            const ok = await confirmar({
                titulo: 'Gerar um novo código',
                mensagem: 'O código atual deixa de funcionar imediatamente. Quem o recebeu precisará do novo.',
                textoConfirmar: 'Gerar novo código'
            });
            if (!ok) return;
            const perfil = await Perfil.novoCodigo();
            if (perfil) {
                // só o código muda: o que estiver sendo editado continua na tela
                this.userProfile.codigo = perfil.codigo;
                ToastMessage.add('Novo código gerado.', '', 'success');
            }
        },
        async confirmarEmail() {
            const perfil = await Perfil.confirmarEmail(this.codigoEmail);
            if (perfil) {
                this.codigoEmail = '';
                this.setUserProfileInfos(perfil);
                ToastMessage.add('Email alterado.', '', 'success');
            }
        },
        async cancelarEmail() {
            const perfil = await Perfil.cancelarEmail();
            if (perfil) this.setUserProfileInfos(perfil);
        },
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
        async salvarAlteracoes() {
            const identidadeMudou = this.userProfileIdentidadeChanged;
            const dadosMudaram = this.userProfileInfosChanged;

            // valida tudo antes de gravar qualquer parte
            if (dadosMudaram && this.userProfile.v_model_senha && !this.senhaAtual) {
                ToastMessage.add('Informe a senha atual para trocar a senha', '', 'warn');
                return;
            }
            if (identidadeMudou && !validarRegex(this.userProfile.liv_model_identidade, this.userProfile.liv_model_regex)) {
                ToastMessage.add('Identidade inválida para o tipo escolhido', '', 'warn');
                return;
            }

            if (identidadeMudou && !(await this.salvarIdentidadeLegal())) return;
            if (dadosMudaram) await this.atualizarPerfilUsuario();
            else if (identidadeMudou) ToastMessage.add('Perfil atualizado.', '', 'success');
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

            if (valores.senha) valores.senha_atual = this.senhaAtual;

            const updatedProfile = await Perfil.atualizarPerfilUsuario(valores);
            if (updatedProfile) {
                if (valores.email && updatedProfile.email_pendente) {
                    ToastMessage.add('Confirme o novo email', 'Enviamos um código para ' + updatedProfile.email_pendente, 'info');
                } else {
                    ToastMessage.add('Perfil atualizado.', '', 'success');
                }
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
            return Boolean(updatedProfile);
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
        temAlteracoes() {
            return this.userProfileInfosChanged || this.userProfileIdentidadeChanged;
        },
        userProfileIdentidadeChanged() {
            if (!this.userProfile) return false;

            return compararVariaveis(this.userProfile.liv_model_identidade, this.userProfile.identidade) != 0 ||
                   compararVariaveis(this.userProfile.liv_model_li_tipo_id, this.userProfile.li_tipo_id) != 0;
        }
    },
    async mounted() {
        await this.fetchUserProfile();
        const codigo = this.$route.query.codigo_email;
        if (codigo && this.userProfile?.email_pendente) {
            this.codigoEmail = String(codigo);
        }
    },
    components: {
        HeaderTelas,
        SelectInput,
        PasswordInput
    },
};
</script>

<style scoped>
.perfil-page { display: flex; flex-wrap: wrap; align-items: flex-start; gap: var(--space-4); width: 100%; padding: var(--space-5); }
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
.codigo-usuario { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3); background: var(--color-primary-soft); border-radius: var(--radius-md); }
.codigo-rotulo { display: block; color: var(--color-text-muted); font-size: var(--font-xs); }
.codigo-valor { font-family: var(--font-mono); font-size: var(--font-xl); letter-spacing: 0.12em; user-select: all; white-space: nowrap; }
.dica { color: var(--color-text-muted); font-size: var(--font-xs); font-weight: normal; }
.email-pendente { display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-3); font-size: var(--font-sm); background: var(--color-warning-soft); border-radius: var(--radius-md); overflow-wrap: anywhere; }
.codigo-acoes { display: flex; gap: var(--space-2); }
.botao-recarregar { width: var(--control-height-sm); padding: 0; }
.botao-recarregar[data-busy] svg { display: none; }
.perfil-acoes { position: sticky; bottom: var(--space-3); z-index: 2; display: flex; flex: 1 1 100%; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); font-size: var(--font-sm); box-shadow: var(--shadow-lg); }
.perfil-acoes .buttons { margin-left: auto; }
.perfil-empty { padding: var(--space-5); color: var(--color-text-muted); }
@media (max-width: 520px) { .perfil-page { padding: var(--space-3); } }
</style>
