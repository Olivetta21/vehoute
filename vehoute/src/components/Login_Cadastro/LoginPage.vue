<template>
    <AuthCont titulo="Login">
        <form @submit.prevent="fazerLogin()" class="blue-formulary">
            <input v-model="login" type="text" placeholder="Login" required />
        <input v-model="senha" type="password" placeholder="senha" required />
        <button type="submit">Entrar</button>
        <div id="opcoes">
            <button type="button" class="option-button">Recuperar senha</button>
            <button type="button" class="option-button" @click="$router.push({ name: 'cadastro' })">Fazer cadastro</button>
        </div>
        </form>
    </AuthCont>
</template>


<script>
import router from '../../router';
import Login from '../../scripts/LoginPage/Login';
import AuthCont from './AuthCont.vue';

export default {
    name: 'LoginPage',
    components: {
        AuthCont
    },
    data() {
        return {
            login: 'donoexemplo',
            senha: '123'
        }
    },
    methods: {
        async fazerLogin() {
            if (await Login.fazerLogin(this.login, this.senha)) {
                router.push({ name: 'home' });
            }
        }
    }
}
</script>
