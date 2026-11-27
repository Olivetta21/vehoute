import ToastMessage from './ToastMessage/ToastMessage';

export const EVENTO_SESSAO_EXPIRADA = 'vehoute:sessao-expirada';

/** Aviso único usado pelo fetch e pelo WebSocket quando o token deixa de valer. */
export function avisarSessaoExpirada() {
    document.title = 'Sessão expirada!';
    ToastMessage.add('Sessão expirada!', 'Refaça o login.', 'error', true);
}

/** O main.js ouve este evento, limpa a sessão local e leva ao login. */
export function encerrarSessaoExpirada() {
    window.dispatchEvent(new CustomEvent(EVENTO_SESSAO_EXPIRADA));
}
