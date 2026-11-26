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
        Usuario.disconnectWebSockets();
        Usuario.clean();
        Cookies.remove('access_token', { path: '/' });

        return resp;
    }

    /** Chamado quando o servidor informa que a sessão não é mais válida. */
    static encerrarSessaoLocal() {
        Usuario.disconnectWebSockets();
        Usuario.clean();
        Cookies.remove('access_token', { path: '/' });
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

            return await this.fazerLogin(null, null, access_token);
        }
    }

    static async fazerLogin(login, senha, access_token) {
        const resp = await fetch_("/login_cadastro/login.php", access_token ? [{access_token}] : [{"login":{login, senha}}]);
        const success = this.setLogged(resp);

        if (success) {
            Usuario.connectWebSockets("main");
        }

        return success;
    }

}