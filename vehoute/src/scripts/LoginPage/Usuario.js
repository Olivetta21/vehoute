
export default class Usuario {
    static id = null;
    static nome = null;
    static login = null;
    static legal_ident = null;
    static email = null;
    static telefone = null;
    static access_token = null;
    static permissoes = null;

    static set dados(dados) {
        this.id = dados.id || null;
        this.nome = dados.nome || null;
        this.login = dados.login || null;
        this.legal_ident = dados.legal_ident || null;
        this.email = dados.email || null;
        this.telefone = dados.telefone || null;
        this.access_token = dados.access_token || null;
        this.permissoes = dados.permissoes || null;
    }

    static clean() {
        this.id = null;
        this.nome = null;
        this.login = null;
        this.legal_ident = null;
        this.email = null;
        this.telefone = null;
        this.access_token = null;
        this.permissoes = null;
    }
}