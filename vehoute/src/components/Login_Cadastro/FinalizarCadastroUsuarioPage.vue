<template>
    <AuthCont titulo="Finalizar Cadastro" backTo="cadastro">
        <form v-if="!otp_succes" @submit.prevent="testOTP()" class="blue-formulary">
            <input v-model="otp" type="text" placeholder="Código do email" required />
            <button type="submit">Verificar</button>
        </form>

        <form v-else @submit.prevent="finalizarCadastro()" class="blue-formulary">
            <input v-model="nome" type="text" placeholder="Nome" required disabled="true"/>
            <input v-model="email" type="email" placeholder="Email" required disabled="true" />
            <input v-model="telefone" type="text" placeholder="Telefone" required />
            <input v-model="login" type="text" placeholder="Login" required />
            <input v-model="senha1" type="password" placeholder="Senha" required />
            <input v-model="senha2" type="password" placeholder="Repita a senha" required />
            <button type="submit">Cadastrar-se</button>
        </form>
    </AuthCont>
</template>


<script>
import CadastroUsuario from '@/scripts/CadastroPage/CadastroUsuario';
import AuthCont from './AuthCont.vue';
import router from '@/router';

export default {
    name: 'FinalizarCadastroUsuarioPage',
    components: {
        AuthCont
    },
    data() {
        return {
            nome: '',
            email: '',
            otp: '',
            telefone: '',
            login: '',
            senha1: '',
            senha2: '',

            otp_succes: false
        }
    },
    methods: {
        async testOTP() {
            const resp = await CadastroUsuario.testarOTP(this.otp);
            if (resp.success) {
                this.nome = resp.dados.nome;
                this.email = resp.dados.email;
                this.otp = resp.dados.otp;
                this.otp_succes = true;
            }
        },
        async finalizarCadastro() {
            if (this.senha1 !== this.senha2) {
                console.log("As senhas não coincidem!");
                return;
            }

            const resp = await CadastroUsuario.finalizarCadastro(this.nome, this.email, this.otp, this.telefone, this.login, this.senha1);
            if (resp.success) {
                console.log("Cadastro finalizado com sucesso!");
                router.push({ name: 'login' });
            }
        }
    },
    created() {
        this.$route.query.otp && (this.otp = this.$route.query.otp) && this.testOTP();
    }
}
</script>
