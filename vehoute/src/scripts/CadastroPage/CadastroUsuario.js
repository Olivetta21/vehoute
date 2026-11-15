import { fetch_ } from "../fetcher";


export default class CadastroUsuario {

    static async enviarEmailComOTP(nome, email) {
        const response = await fetch_("/login_cadastro/cadastro.php", [
            { "mail_data": { "nome": nome, "email": email } }
        ]);
        return response;
    }

    static async testarOTP(otp) {
        const response = await fetch_("/login_cadastro/cadastro.php", [
            { "otp_check": { "otp": otp } }
        ]);
        return response;
    }

    static async finalizarCadastro(nome, email, otp, telefone, login, senha, identidade, tipo_ident_id) {
        const response = await fetch_("/login_cadastro/cadastro.php", [
            { "cadastro_data": { "nome": nome, "email": email, "otp": otp, "telefone": telefone, "login": login, "senha": senha, "identidade": identidade, "tipo_ident_id": tipo_ident_id } }
        ]);
        return response;
    }

    static async getLegalIdentTipos() {
        const response = await fetch_("/login_cadastro/cadastro.php", [{ "getLegalIdentTipos": true }]);
        if (response && response.success) {
            return response.legal_ident_tipo;
        }
        return [];        
    }

    static async enviarCodigoRecuperacao(email) {
        const response = await fetch_("/login_cadastro/cadastro.php", [
            { "recuperar_senha": email }
        ]);
        if (response && response.success) {
            return true;
        }
        return false;
    }

    static async testarCodigoRecuperacao(otp) {
        const response = await fetch_("/login_cadastro/cadastro.php", [
            { "recuperar_senha_check": otp }
        ]);
        if (response && response.success) {
            return response.recuperacao_data;
        }
        return false;
    }

    static async salvarNovaSenhaRecuperada(email, otp, senha) {
        const response = await fetch_("/login_cadastro/cadastro.php", [
            { "recuperar_senha_save": { "email": email, "otp": otp, "senha": senha } }
        ]);
        if (response && response.success) {
            return true;
        }
        return false;
    }
}