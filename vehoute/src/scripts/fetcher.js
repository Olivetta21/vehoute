import Usuario from "./LoginPage/Usuario";
import { iniciarCarregamento } from "./loading";
import ToastMessage from "./ToastMessage/ToastMessage";
import { EVENTO_SESSAO_EXPIRADA, avisarSessaoExpirada, encerrarSessaoExpirada } from "./session";
import { getBaseOfDestApi } from "./utils";

const TEMPO_LIMITE_MS = 20000;
export { EVENTO_SESSAO_EXPIRADA };

function montarParametros(destino, arrayValores) {
    for (const item of arrayValores) {
        const entries = Object.entries(item);
        if (entries.length !== 1) {
            console.error("fetch_: cada objeto deve ter exatamente um par chave-valor");
            continue;
        }
        const [head, body] = entries[0];
        destino.append(head, JSON.stringify(body));
    }
    return destino;
}

async function enviar(endereco, body) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), TEMPO_LIMITE_MS);
    try {
        return await fetch(endereco, { method: 'POST', body, signal: controller.signal });
    } finally {
        clearTimeout(timer);
    }
}

export function noPermissionMsg(dequem, perm, codigo){
    if (!dequem) dequem = '';
    ToastMessage.add(dequem + " Você não tem permissão!", "Permissão exigida: " + perm, "error", true, codigo);
}

/** Código que o usuário informa ao suporte: id do registro de erro ou, na falta dele, o timestamp. */
export function codigoDoErro(retorno = {}) {
    if (retorno.erro_id) return `#${retorno.erro_id}`;
    const data = Date.parse(retorno.erro_data) || Date.now();
    return `T${Math.floor(data / 1000)}`;
}

export function tratarRetornosApi(retorno, dequem) {
    if (!dequem) dequem = '';

    if (retorno['error']){
        ToastMessage.add(dequem, retorno['error'], "error", true, codigoDoErro(retorno));
    }
    else if (retorno['warn']){
        ToastMessage.add(dequem, retorno['warn'], "warn", true);
    }
    else if (retorno['nopermission']){
        noPermissionMsg(dequem, retorno['nopermission'], codigoDoErro(retorno));
    }
    else if (retorno['invalid_token']){
        avisarSessaoExpirada();
    }
}

/**
 * Envia [{chave: valor}] ao backend. Sempre retorna um objeto (com "error" em caso de falha),
 * exceto com rawResponse, que retorna a Response ou null.
 */
export async function fetch_(endereco, arrayValores = [], rawResponse = false) {
    const origem = endereco.split('/').pop().split('.php')[0];
    if (arrayValores && !Array.isArray(arrayValores)) {
        console.error("fetch_: o parâmetro enviado não é um array");
        return rawResponse ? null : { error: 'Requisição inválida' };
    }

    // cópia: o array do chamador não é modificado
    const valores = [...(arrayValores || [])];
    const tinhaSessao = Boolean(Usuario.access_token);
    if (tinhaSessao) {
        valores.push({ access_token: Usuario.access_token });
    }

    const encerrarCarregamento = iniciarCarregamento();
    try {
        return await executar(endereco, valores, rawResponse, tinhaSessao, origem);
    } finally {
        encerrarCarregamento();
    }
}

async function executar(endereco, valores, rawResponse, tinhaSessao, origem) {
    let response;
    try {
        const destino = rawResponse ? new FormData() : new URLSearchParams();
        response = await enviar(getBaseOfDestApi() + endereco, montarParametros(destino, valores));
    } catch (error) {
        const mensagem = error.name === 'AbortError'
            ? 'O servidor demorou demais para responder.'
            : 'Não foi possível conectar ao servidor.';
        ToastMessage.add('Falha de conexão', mensagem, 'error', true, codigoDoErro());
        return rawResponse ? null : { error: mensagem };
    }

    if (rawResponse) return response;

    try {
        const result = await response.json();
        tratarRetornosApi(result, origem);
        if (result.invalid_token && tinhaSessao) {
            encerrarSessaoExpirada();
        }
        return result;
    } catch (error) {
        const mensagem = `Resposta inválida do servidor (HTTP ${response.status}).`;
        ToastMessage.add(origem, mensagem, 'error', true, codigoDoErro());
        return { error: mensagem };
    }
}
