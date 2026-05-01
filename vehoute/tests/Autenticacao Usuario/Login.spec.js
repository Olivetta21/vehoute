import { vi, describe, it, expect } from 'vitest';
import Login from "../../src/scripts/LoginPage/Login";
import * as api from "../../src/scripts/utils";

describe('Login', () => {
    vi.spyOn(api, 'getBaseOfDestApi').mockReturnValue('http://localhost:8080/api');

    it('Realiza Login', async () => {
        expect(await Login.fazerLogin("donoexemplo","123")).toBe(true);
    })

    it('Realiza Login falso', async () => {
        expect(await Login.fazerLogin("donoexemplo","456")).toBe(false);
    })
    
    it('Está autenticado', async () => {
        expect(await Login.isAuthenticated()).toBe(true);
    })

    it('Fazer Logoff', async () => {
        expect(await Login.fazerLogoff()).toStrictEqual({"logoff": "1"});
    })
    
    it('Não autenticado', async () => {
        expect(await Login.isAuthenticated()).toBe(false);
    })
})