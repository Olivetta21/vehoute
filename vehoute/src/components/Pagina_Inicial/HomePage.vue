<template>
  <HeaderTelas
      titulo="Página Inicial"
  />
  <LayoutePageCardsLink :items="{home_cardIcon}" />
</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HomePage from '../../scripts/HomePage/HomePage.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import LayoutePageCardsLink from '../utils/LayoutePageCardsLink.vue';

export default {
  name: 'HomePage',
  components: {
    HeaderTelas,
    LayoutePageCardsLink
  },
  data() {
    return {
      HomePage,
      home_cardIcon: PagesRoutes.filter(r => r.home_cardIcon)
    };
  }
}
</script>

<style scoped>
</style>