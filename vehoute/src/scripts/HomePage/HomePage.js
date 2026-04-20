import { fetch_ } from "../fetcher";

export default class HomePage {

    static async testFetch() {
        const endereco = "/testarfetch.php";
        const arrayValores = [{"soma":{"num1": 5, "num2": 10}}];
        const result = await fetch_(endereco, arrayValores);
        
        if (result && result.success) {
            console.log("Resultado da soma:", result.success);
        }
    }

}