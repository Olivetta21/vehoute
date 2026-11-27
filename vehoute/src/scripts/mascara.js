/**
 * Máscara de digitação, independente de qualquer tela.
 *   #  = dígito      A = letra      * = letra ou dígito
 * Qualquer outro caractere da máscara é fixo e inserido automaticamente.
 *
 * aplicarMascara('12345678901', '###.###.###-##') => '123.456.789-01'
 */
const TOKENS = { '#': /\d/, 'A': /[A-Za-z]/, '*': /[A-Za-z0-9]/ };

export function aplicarMascara(valor, mascara) {
    const texto = String(valor ?? '');
    if (!mascara) return texto;

    const entrada = texto.replace(/[^A-Za-z0-9]/g, '');
    let saida = '';
    let fixos = ''; // só entram quando há um caractere digitado depois deles
    let i = 0;

    for (const m of mascara) {
        if (i >= entrada.length) break;
        const token = TOKENS[m];
        if (token) {
            while (i < entrada.length && !token.test(entrada[i])) i++;
            if (i >= entrada.length) break;
            saida += fixos + entrada[i++];
            fixos = '';
        } else {
            fixos += m;
            if (entrada[i] === m) i++; // o usuário digitou o próprio caractere fixo
        }
    }
    return saida;
}

/** Para usar em @input: atualiza o campo na tela e devolve o valor mascarado. */
export function mascararCampo(evento, mascara) {
    const valor = aplicarMascara(evento.target.value, mascara);
    evento.target.value = valor;
    return valor;
}
