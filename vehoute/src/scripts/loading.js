/**
 * Indicador global de carregamento + bloqueio de duplo clique.
 *
 * Quem faz fetch chama iniciarCarregamento() e depois a função retornada. O botão que
 * disparou a ação (clique ou envio de formulário) fica "ocupado" até a resposta chegar,
 * sem que cada tela precise controlar isso.
 */
import { reactive } from 'vue';

export const estadoCarregamento = reactive({ pendentes: 0, navegando: false });

let ultimoBotao = null;

export function botaoAtual() {
    return ultimoBotao;
}

export function lembrarBotao(botao) {
    if (!botao) return;
    ultimoBotao = botao;
    // vale apenas para o que for disparado nesta mesma tarefa
    setTimeout(() => { if (ultimoBotao === botao) ultimoBotao = null; }, 0);
}

function bloquear(evento) {
    evento.preventDefault();
    evento.stopImmediatePropagation();
}

export function instalarBloqueioDeCliqueDuplo(doc = document) {
    doc.addEventListener('click', (e) => {
        if (e.target.closest?.('[data-busy]')) return bloquear(e);
        lembrarBotao(e.target.closest?.('button, [data-acao]'));
    }, true);

    doc.addEventListener('submit', (e) => {
        if (e.target.querySelector?.('button[data-busy]')) return bloquear(e);
        lembrarBotao(e.submitter || e.target.querySelector?.('button[type="submit"], button:not([type])'));
    }, true);
}

export function iniciarCarregamento() {
    const botao = ultimoBotao;
    estadoCarregamento.pendentes++;
    if (botao) {
        botao.__pendentes = (botao.__pendentes || 0) + 1;
        botao.setAttribute('data-busy', 'true');
        botao.setAttribute('aria-busy', 'true');
    }

    let encerrado = false;
    return function encerrar() {
        if (encerrado) return;
        encerrado = true;
        estadoCarregamento.pendentes--;
        if (botao && --botao.__pendentes <= 0) {
            botao.removeAttribute('data-busy');
            botao.removeAttribute('aria-busy');
        }
    };
}
