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
    margin: var(--space-3) var(--space-5) 0;
    padding: var(--space-3) var(--space-4);
    font-size: var(--font-sm);
    overflow-wrap: anywhere;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
p:last-child { margin-bottom: var(--space-5); }
</style>
