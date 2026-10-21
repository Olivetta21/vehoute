<template>
  <HeaderTelas
      titulo="Administração"
      :mostrarVoltar="true"
      @voltar="this.$router.back()"
  />
  <LayoutePageCardsLink :items="{admin_cardIcon}" />
</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import LayoutePageCardsLink from '../utils/LayoutePageCardsLink.vue';

export default {
  name: 'AdminHomePage',
  components: {
    HeaderTelas,
    LayoutePageCardsLink
  },
  data() {
    return {
      admin_cardIcon: PagesRoutes.filter(r => r.admin_cardIcon)
    };
  }
}
</script>

<style scoped>
</style>
