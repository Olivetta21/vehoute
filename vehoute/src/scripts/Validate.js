
import { validarRegex } from "./utils";

export default class Validate {
    static Senha(senha) {
        if (senha.length < 8) return false;
        if (!validarRegex(senha, /[A-Z]/)) return false;
        if (!validarRegex(senha, /[a-z]/)) return false;
        if (!validarRegex(senha, /\d/)) return false;
        if (!validarRegex(senha, /[#@$!%*?&]/)) return false;

        return true;
    }

    static TokenPublico(token) {
        return validarRegex(token, /^[a-zA-Z0-9._%+-]{8,}$/);
    }

    static NomeUsuario(nome) {
        return validarRegex(nome, /^[A-Za-zÀ-ÖØ-öø-ÿ]+(?:\s+[A-Za-zÀ-ÖØ-öø-ÿ]+)+$/);
    }

    static Telefone(telefone) {
        return validarRegex(telefone, /^\+?[1-9]\d{7,14}$/);
    }

    static Email(email) {
        return validarRegex(email, /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/);
    }

    static LoginUsuario(login) {
        return validarRegex(login, /^[A-Za-zÀ-ÖØ-öø-ÿ0-9 ._%+-]{8,}$/);
    }

    static OTP(otp) {
        return validarRegex(otp, /^[A-Za-z0-9]{8,8}$/);
    }
}
