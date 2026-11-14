<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />        
        <p v-for="(notificacao, index) in Notification.lista_notificacoes" :key="index">
            {{ notificacao }}
        </p>
</template>

<script>
import Notification from '../../scripts/NotificationPage/Notification';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';

export default {
    name: 'NotificationPage',
    data() {
        return {
            PagesRoutes,
            Notification
        };
    },
    components: {
        HeaderTelas
    },
    methods: {
        testAdd() {
            this.Notification.insertNotification("Nova Notificação");
        }
    }
};
</script>

<style scoped>
p {
    background-color: #f0f0f0;
    padding: 10px;
    margin: 5px 0;
    border-radius: 5px;
}
</style>