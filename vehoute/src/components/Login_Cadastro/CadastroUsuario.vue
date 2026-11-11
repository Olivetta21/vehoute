<template>
    <AuthCont titulo="Cadastro" backTo="login">
        <form @submit.prevent="enviarEmailComOTP()" class="blue-formulary">
            <InputCheck :check="Validate.NomeUsuario(nome)" inv="Digite pelo menos 2 nomes, apenas letras.">
                <input v-model="nome" type="text" placeholder="Nome" required />
            </InputCheck>
            <InputCheck :check="Validate.Email(email)" inv="Email inválido">
                <input v-model="email" type="email" placeholder="Email" required />
            </InputCheck>
            <button type="submit">Enviar email</button>
        </form>
    </AuthCont>
</template>


<script>
import router from '../../router';
import AuthCont from './AuthCont.vue';
import CadastroUsuario from '../../scripts/CadastroPage/CadastroUsuario';
import InputCheck from '../utils/InputCheck.vue';
import Validate from '../../scripts/Validate';

export default {
    name: 'CadastroUsuarioPage',
    components: {
        AuthCont,
        InputCheck
    },
    data() {
        return {
            Validate,
            nome: '',
            email: ''
        }
    },
    methods: {
        async enviarEmailComOTP() {
            const resp = await CadastroUsuario.enviarEmailComOTP(this.nome, this.email);
            console.log(resp);

            if (resp.success) {
                router.push({ name: 'finalizar-cadastro' });
            }
        }
    }
}
</script>
