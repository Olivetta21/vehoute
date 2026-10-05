//import router from "../../router";
import { fetch_ } from "../fetcher";

export default class Perfil {
    
    static async getPerfilUsuario() {
        const response = await fetch_('/usuario/perfil/perfil.php', [{ get: true }]);
        if (response && response.success) {
            return response.perfil;
        }
        console.error("Erro ao buscar perfil do usuário");
        return null;
    }
    
    static async atualizarPerfilUsuario(valores) {
        const response = await fetch_('/usuario/perfil/perfil.php', [{ update: valores }]);
        if (response && response.success) {
            return response.perfil;
        }
        console.error("Erro ao atualizar perfil do usuário");
        return null;
    }

}