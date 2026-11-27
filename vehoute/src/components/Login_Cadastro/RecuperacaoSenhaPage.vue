<template>
    <AuthCont titulo="Recuperar Senha" backTo="login">
        <form v-if="!email_sucess" @submit.prevent="enviarCodigo()" class="blue-formulary">
            <InputCheck :check="Validate.Email(email)" inv="Email inválido">
                <input v-model="email" type="email" placeholder="Email" required />
            </InputCheck>

            <p class="dica">O email também informa o seu login, caso você o tenha esquecido.</p>
            <button type="submit">Enviar código</button>
        </form>

        <form v-else-if="!otp_succes" @submit.prevent="testOTP()" class="blue-formulary">
            <InputCheck :check="Validate.OTP(otp)" inv="Código inválido">
                <input v-model="otp" type="text" placeholder="Código do email" required />
            </InputCheck>

            <button type="submit">Verificar</button>
        </form>

        <form v-else @submit.prevent="salvarNovaSenha()" class="blue-formulary">
            <InputCheck :check="Validate.Senha(senha1)" inv="A senha deve ter no mínimo 8 caracteres, uma letra maiúscula e minúscula, um número e caractere especial">
                <PasswordInput v-model="senha1" placeholder="Nova senha" autocomplete="new-password" required />
            </InputCheck>

            <InputCheck :check="this.senha1 === this.senha2" inv="As senhas não coincidem">
                <PasswordInput v-model="senha2" placeholder="Repita a senha" autocomplete="new-password" required />
            </InputCheck>
            <button type="submit">Salvar nova senha</button>
        </form>
    </AuthCont>
</template>


<script>
import CadastroUsuario from '../../scripts/CadastroPage/CadastroUsuario';
import AuthCont from './AuthCont.vue';
import router from '../../router';
import InputCheck from '../utils/InputCheck.vue';
import Validate from '../../scripts/Validate';
import PasswordInput from '../utils/PasswordInput.vue';

export default {
    name: 'RecuperacaoSenhaPage',
    components: {
        AuthCont,
        InputCheck,
        PasswordInput
    },
    data() {
        return {
            Validate,
            email: '',
            otp: '',
            senha1: '',
            senha2: '',

            email_sucess: false,
            otp_succes: false
        }
    },
    methods: {
        async enviarCodigo() {
            const resp = await CadastroUsuario.enviarCodigoRecuperacao(this.email);
            if (resp) {
                this.email_sucess = true;
            }
        },
        async testOTP() {
            const resp = await CadastroUsuario.testarCodigoRecuperacao(this.otp);
            if (resp) {
                this.email = resp.email;
                this.email_sucess = true;
                this.otp = resp.otp;
                this.otp_succes = true;
            }
        },
        async salvarNovaSenha() {
            const resp = await CadastroUsuario.salvarNovaSenhaRecuperada(this.email, this.otp, this.senha1);
            if (resp) {
                router.push({ name: 'login' });
            }
        }
    },
    async created() {
        this.$route.query.otp && (this.otp = this.$route.query.otp) && await this.testOTP();
    },
}
</script>

<style scoped>
.dica { color: var(--color-text-muted); font-size: var(--font-xs); }
</style>
