
export async function fetch_(endereco, arrayValores) {
    //[{"head":"body"}]
    if (arrayValores && !Array.isArray(arrayValores)) {
        console.log("fetch_", "o parametro: " + arrayValores + " não é um array!", "error");
        return null;
    }

    try {
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

        const dest_api = process.env.VUE_APP_BACKEND_ADDRESS;

        const response = await fetch(dest_api + endereco, {
            method: 'POST',
            body: formData
        });

        try {
            const result = await response.json();
            return result;
        }
        catch (error) {
            console.error("fetch_", "Error parsing JSON response:", response);
        }
        
    } catch (error) {
        console.error("fetch_", "Error fetching data:", error);
    }

    return null;
}