<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            @voltar="this.$router.back()"
        />
</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';

export default {
    name: 'NotificationPage',
    data() {
        return {
            PagesRoutes,
        };
    },
    components: {
        HeaderTelas
    },
};
</script>