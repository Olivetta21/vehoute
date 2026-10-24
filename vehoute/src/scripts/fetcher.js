import Usuario from "./LoginPage/Usuario";
import ToastMessage from "./ToastMessage/ToastMessage";
import { getBaseOfDestApi } from "./utils";

async function fetch_with_form_data(endereco, arrayValores) {

    const formData = new FormData();

    if (arrayValores != null && arrayValores.length > 0) {
        arrayValores.forEach(e => {
            let entries = Object.entries(e);
            if (entries.length !== 1) {
                console.error("fetch_", "O objeto: " + e + " tem mais de um par chave-valor!");
                return null;
            }
            const [head, body] = entries[0];
            formData.append(head, JSON.stringify(body));
        });
    }

    return await fetch(endereco, {
        method: 'POST',
        body: formData
    });

}

async function fetch_with_json_only(endereco, arrayValores) {
    
    const params = new URLSearchParams();

    arrayValores.forEach(e => {
        let entries = Object.entries(e);
        if (entries.length !== 1) {
            console.error("fetch_", "O objeto: " + e + " tem mais de um par chave-valor!");
            return null;
        }
        const [head, body] = entries[0];
        params.append(head, JSON.stringify(body));
    });

    return await fetch(endereco, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    });
    
}


export function noPermissionMsg(dequem, perm){
    if (!dequem) dequem = '';
    ToastMessage.add(dequem + " Você não tem permissão!", "Permissão exigida: " + perm, "error", true);
}

export function tratarRetornosApi(retorno, dequem) {
    if (!dequem) dequem = '';

    if (retorno['error']){
        ToastMessage.add(dequem, retorno['error'], "error", true);
    }
    else if (retorno['warn']){
        ToastMessage.add(dequem, retorno['warn'], "warn", true);
    }
    else if (retorno['nopermission']){
        noPermissionMsg(dequem, retorno['nopermission'], true);
    }
    else if (retorno['invalid_token']){
        document.title = "Sessão expirada!";
        ToastMessage.add("Sessão expirada!","Refaça o login.", "error", true);
    }
}

export async function fetch_(endereco, arrayValores, rawResponse = false) {
    //[{"head":"body"}]
    if (arrayValores && !Array.isArray(arrayValores)) {
        console.log("fetch_", "o parametro: " + arrayValores + " não é um array!", "error");
        return null;
    }

    try {        
        const dest_api = getBaseOfDestApi() + endereco;

        if (Usuario.access_token) {
            arrayValores.push({ access_token: Usuario.access_token });
        }

        const response = rawResponse ?
                await fetch_with_form_data(dest_api, arrayValores) :
                await fetch_with_json_only(dest_api, arrayValores);

        try {
            if (rawResponse){
                return response;
            }
            const result = await response.json();

            tratarRetornosApi(result, endereco.split('/').pop().split('.php')[0]);
            return result;
        }
        catch (error) {
            console.error("fetch_", "Error parsing JSON response:", error);
        }
        
    } catch (error) {
        console.error("fetch_", "Error fetching data:", error);
    }

    return null;
}
