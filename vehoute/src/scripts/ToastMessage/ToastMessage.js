import { ref } from "vue";
import Notification from "../NotificationPage/Notification";

export default class ToastMessage {
    static _toasts = ref([]);
    static _proximo_id = 1;
    
    static get toasts(){
        return ToastMessage._toasts.value;
    }

    static add(title, message, type, _console, codigo){
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

        const id = ToastMessage._proximo_id++;
        
        ToastMessage.toasts.unshift({id, title, message, type, codigo});
        Notification.insertNotification(`[${type.toUpperCase()}] ${title} - ${message}${codigo ? ` (${codigo})` : ''}`);

        if (_console){
            if (typeof console[type] === 'function') console[type](title, message, codigo || '');
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