<template>
  <HeaderTelas
      titulo="Página Inicial"
  />
  <LayouteHomePage :items="homeItens" />
</template>

<script>
import PagesRoutes from '@/scripts/PagesRoutes.js';
import HomePage from '../../scripts/HomePage/HomePage.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import LayouteHomePage from './LayouteHomePage.vue';

export default {
  name: 'HomePage',
  components: {
    HeaderTelas,
    LayouteHomePage
  },
  data() {
    return {
      HomePage,
      homeItens: PagesRoutes.filter(r => r.home_cardIcon)
    };
  }
}
</script>

<style scoped>
</style>