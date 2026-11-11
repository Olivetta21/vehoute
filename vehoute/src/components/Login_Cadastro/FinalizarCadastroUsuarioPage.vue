<template>
    <AuthCont titulo="Finalizar Cadastro" backTo="cadastro">
        <form v-if="!otp_succes" @submit.prevent="testOTP()" class="blue-formulary">
            <InputCheck :check="Validate.OTP(otp)" inv="Código inválido">
                <input v-model="otp" type="text" placeholder="Código do email" required />
            </InputCheck>

            <button type="submit">Verificar</button>
        </form>

        <form v-else @submit.prevent="finalizarCadastro()" class="blue-formulary">
            <input v-model="nome" type="text" placeholder="Nome" required disabled="true"/>
            <input v-model="email" type="email" placeholder="Email" required disabled="true" />
            
            <InputCheck :check="Validate.Telefone(telefone)" inv="Telefone inválido">
                <input v-model="telefone" type="text" placeholder="Telefone" required />
            </InputCheck>
            
            <InputCheck :check="Validate.LoginUsuario(login)" inv="Login precisa ser maior que 8 caracteres, apenas letras e números"> 
                <input v-model="login" type="text" placeholder="Login" required />
            </InputCheck>

            <InputCheck :check="this.tipo_ident != null" inv="Selecione uma identidade válida">
                <label>Tipo de Identidade</label>
                <SelectInput
                    :searchFn="getLegalIdentTipos"
                    :searchAll="true"
                    @update:value="this.tipo_ident = $event || null"
                />
            </InputCheck>

            <InputCheck :check="validarRegex(identidade, tipo_ident?.regex)" inv="Identidade não atende aos requisitos">
                <input v-model="identidade" type="text" placeholder="Identidade" required />
            </InputCheck>

            <InputCheck :check="Validate.Senha(senha1)" inv="A senha deve ter no mínimo 8 caracteres, uma letra maiúscula e minúscula, um número e caractere especial">
                <input v-model="senha1" type="password" placeholder="Senha" required />
            </InputCheck>

            <InputCheck :check="this.senha1 === this.senha2" inv="As senhas não coincidem">
                <input v-model="senha2" type="password" placeholder="Repita a senha" required />
            </InputCheck>
            
            <button type="submit">Cadastrar-se</button>
        </form>
    </AuthCont>
</template>


<script>
import CadastroUsuario from '../../scripts/CadastroPage/CadastroUsuario';
import AuthCont from './AuthCont.vue';
import router from '../../router';
import SelectInput from '../utils/SelectInput.vue';
import { validarRegex } from '../../scripts/utils';
import InputCheck from '../utils/InputCheck.vue';
import Validate from '../../scripts/Validate';

export default {
    name: 'FinalizarCadastroUsuarioPage',
    components: {
        AuthCont,
        SelectInput,
        InputCheck
    },
    data() {
        return {
            validarRegex,
            Validate,

            nome: '',
            email: '',
            otp: '',
            telefone: '',
            login: '',
            identidade: '',
            tipo_ident: null,
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
            const resp = await CadastroUsuario.finalizarCadastro(this.nome, this.email, this.otp, this.telefone, this.login, this.senha1, this.identidade, this.tipo_ident.id);
            if (resp.success) {
                router.push({ name: 'login' });
            }
        },
        async getLegalIdentTipos() {
            return await CadastroUsuario.getLegalIdentTipos();
        }
    },
    created() {
        this.$route.query.otp && (this.otp = this.$route.query.otp) && this.testOTP();
    },
}
</script>
