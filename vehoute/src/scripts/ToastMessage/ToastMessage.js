import { ref } from "vue";

export default class ToastMessage {
    static _toasts = ref([]);
    
    static get toasts(){
        return ToastMessage._toasts.value;
    }

    static add(title, message, type, _console){
        if (!type) {
            type = "error";
            _console = true;
        }
        if (!title && !message) {
            message = "Mensagem de toast não fornecida";
            type = "error";
            _console = true;
        }
        if (!message) {
            message = title;
            title = "";
        }

        const id = Date.now();
        
        ToastMessage.toasts.unshift({id, title, message, type});

        if (_console){
            if (typeof console[type] === 'function') console[type](title, message);
            else {
                console.error("tipo de console nao reconhecido", type);
                console.log(title, message);
            }
        }

        setTimeout(() => {
            ToastMessage.removeById(id);
        }, 8000);
    }

    static removeById(id){
        ToastMessage._toasts.value = ToastMessage.toasts.filter(toast => toast.id !== id);
    }

}