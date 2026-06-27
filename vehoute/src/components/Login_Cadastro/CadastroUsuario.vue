<template>
    <AuthCont titulo="Cadastro" backTo="login">
        <form @submit.prevent="enviarEmailComOTP()" class="blue-formulary">
            <input v-model="nome" type="text" placeholder="Nome" required />
            <input v-model="email" type="email" placeholder="Email" required />
            <button type="submit">Enviar email</button>
        </form>
    </AuthCont>
</template>


<script>
import router from '../../router';
import AuthCont from './AuthCont.vue';
import CadastroUsuario from '../../scripts/CadastroPage/CadastroUsuario';

export default {
    name: 'CadastroUsuarioPage',
    components: {
        AuthCont
    },
    data() {
        return {
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
