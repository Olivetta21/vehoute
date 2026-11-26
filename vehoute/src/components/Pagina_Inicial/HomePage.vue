<template>
  <HeaderTelas
      titulo="Página Inicial"
  />
  <LayoutePageCardsLink :items="{home_cardIcon}" />
</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes.js';
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
      home_cardIcon: PagesRoutes.byCaracteristic('home_cardIcon')
    };
  }
}
</script>

<style scoped>
</style>