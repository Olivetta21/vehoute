<template>
    <AuthCont titulo="Login">
        <form @submit.prevent="fazerLogin()" class="blue-formulary">
            <input v-model="login" type="text" placeholder="Login" required />
        <PasswordInput v-model="senha" placeholder="Senha" autocomplete="current-password" required />
        <button type="submit">Entrar</button>
        <div id="opcoes">
            <button type="button" class="option-button btn-ghost btn-sm" @click="$router.push({ name: 'recuperacao-senha' })">Recuperar senha</button>
            <button type="button" class="option-button btn-ghost btn-sm" @click="$router.push({ name: 'cadastro' })">Fazer cadastro</button>
        </div>
        </form>
    </AuthCont>
</template>


<script>
import router from '../../router';
import Login from '../../scripts/LoginPage/Login';
import AuthCont from './AuthCont.vue';
import PasswordInput from '../utils/PasswordInput.vue';

export default {
    name: 'LoginPage',
    components: {
        AuthCont,
        PasswordInput
    },
    data() {
        return {
            login: '',
            senha: ''
        }
    },
    methods: {
        async fazerLogin() {
            if (await Login.fazerLogin(this.login, this.senha)) {
                const destino = String(this.$route.query.redirect || '');
                // somente caminhos internos
                router.push(/^\/(?!\/)/.test(destino) ? destino : { name: 'home' });
            }
        }
    }
}
</script>

<style scoped>
#opcoes { display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-1); }
</style>
