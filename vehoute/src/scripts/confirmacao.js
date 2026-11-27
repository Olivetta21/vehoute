/**
 * Substitui o confirm() do navegador pelo ConfirmModal (montado uma vez no App.vue).
 *
 *   if (!await confirmar({ mensagem: 'Excluir este item?', perigo: true })) return;
 */
import { shallowReactive } from 'vue';
import { botaoAtual, lembrarBotao } from './loading';

export const estadoConfirmacao = shallowReactive({ pedido: null });

export function responderConfirmacao(resposta) {
    const pedido = estadoConfirmacao.pedido;
    estadoConfirmacao.pedido = null;
    pedido?.resolver(resposta);
}

export async function confirmar(opcoes) {
    responderConfirmacao(false); // só uma pergunta por vez
    const origem = botaoAtual();
    const resposta = await new Promise((resolver) => {
        estadoConfirmacao.pedido = { opcoes, resolver };
    });
    // o carregamento deve aparecer no botão que iniciou a ação
    if (resposta) lembrarBotao(origem);
    return resposta;
}
