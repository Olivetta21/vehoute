import { vi, describe, it, expect } from 'vitest';
import CadastroUsuario from '../../src/scripts/CadastroPage/CadastroUsuario';
import * as api from "../../src/scripts/utils";

function generateRandomString(length) {
    const characters = 'abcdefghijklmnopqrstuvwxyz';
    let result = '';
    const charactersLength = characters.length;
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

describe('Cadastro', () => {
    vi.spyOn(api, 'getBaseOfDestApi').mockReturnValue('http://localhost:8080/api');

    let nome_aleatorio = "Teste" + generateRandomString(5);
    let email_aleatorio = nome_aleatorio + "@example.com.brasil";
    let login_aleatorio = nome_aleatorio + "login";
    nome_aleatorio = nome_aleatorio + " Silva";
    let temp_otp = "";

    it('Envia email com OTP', async () => {
        let res = await CadastroUsuario.enviarEmailComOTP(nome_aleatorio, email_aleatorio);
        temp_otp = res.otp;
        res.otp = res.otp.length;

        expect(res).toStrictEqual({"success":"1", "otp":8});
    })

    it('Testa OTP', async () => {
        let res = await CadastroUsuario.testarOTP(temp_otp);

        expect(res).toStrictEqual({"success":"1", "dados":{"nome": nome_aleatorio, "email": email_aleatorio, "otp": temp_otp}});
    })

    
    it('Finalizar Cadastro', async () => {
        let res = await CadastroUsuario.finalizarCadastro(nome_aleatorio, email_aleatorio, temp_otp, '11999999999', login_aleatorio, 'senha123A#');

        expect(res).toStrictEqual({"success":"1"});
    })
});