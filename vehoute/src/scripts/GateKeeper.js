export default class Gatekeeper {
    static #pendentes = new Map();

    static openFor(token, max_wait_time_ms = null) {
        return new Promise((resolve) => {
            if (Gatekeeper.#pendentes.has(token)) {
                console.warn(`Já existe uma promessa pendente para o token: ${token}`);
                resolve(null);
                return;
            }
            
            console.log('Esperando no portão pelo token:', token);
            Gatekeeper.#pendentes.set(token, resolve);

            if (max_wait_time_ms !== null) {
                setTimeout(() => {
                    if (Gatekeeper.#pendentes.has(token)) {
                        console.warn(`Tempo máximo de espera atingido para o token: ${token}.`);
                        Gatekeeper.#pendentes.delete(token);
                        resolve(null);
                    }
                }, max_wait_time_ms);
            }
        });
    }

    static openGate(token, valor) {
        const resolve = Gatekeeper.#pendentes.get(token);

        if (!resolve) return false;

        console.log('Portão aberto para o token:', token, 'com valor:', valor);
        Gatekeeper.#pendentes.delete(token);
        resolve(valor);
        return true;
    }
}

// await GateKeeper.openFor('algumacoisa');
// GateKeeper.openGate('algumacoisa', true);