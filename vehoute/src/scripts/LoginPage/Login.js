import { fetch_ } from "../fetcher"
import Usuario from "./Usuario";
import Cookies from 'js-cookie';



export default class Login {
    static async before_enter() {
        await this.fazerLogoff();
    }

    static async fazerLogoff() {
        let resp = null;
        if (Usuario.access_token || Cookies.get('access_token')) {
            resp = await fetch_("/login_cadastro/login.php", [{"logoff": 1}]);
        }
        Usuario.clean();

        return resp;
    }

    static setLogged(resp) {
        if (resp && resp.success && resp.usuario) {
            Usuario.dados = resp.usuario;
            return true;
        }
        return false;
    }

    static async isAuthenticated() {
        if (Usuario.access_token) {
            return true;
        } else {
            const access_token = Cookies.get('access_token');
            if (!access_token) return false;

            console.log("access_token do cookie:", access_token);

            const resp = await fetch_("/login_cadastro/login.php", [{"access_token": access_token}]);
            return this.setLogged(resp);
        }
    }

    static async fazerLogin(login, senha) {
        const resp = await fetch_("/login_cadastro/login.php", [{"login":{"login": login, "senha": senha}}]);
        return this.setLogged(resp);
    }

}