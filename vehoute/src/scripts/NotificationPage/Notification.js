//import router from "../../router";

import { ref } from "vue";

export default class Notification {
    
    static lista_notificacoes_ = ref([]);

    static get lista_notificacoes() {
        return Notification.lista_notificacoes_.value;
    }
    static set lista_notificacoes(value) {
        Notification.lista_notificacoes_.value = value;
    }

    static insertNotification(notification) {
        Notification.lista_notificacoes_.value.unshift(notification);
    }

}