<template>
  <HeaderTelas
      titulo="Administração"
      :mostrarVoltar="true"
      @voltar="this.$router.back()"
  />
  <LayouteHomePage :items="adminItens" />
</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import LayouteHomePage from './LayouteHomePage.vue';

export default {
  name: 'AdminHomePage',
  components: {
    HeaderTelas,
    LayouteHomePage
  },
  data() {
    return {
      adminItens: PagesRoutes.filter(r => r.admin_cardIcon)
    };
  }
}
</script>

<style scoped>
</style>
