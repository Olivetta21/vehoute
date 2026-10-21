<template>
  <HeaderTelas
      titulo="Usuario e Permissões"
      :mostrarVoltar="true"
      @voltar="this.$router.back()"
  />
  <LayoutePageCardsLink :items="{userandperms_cardIcon}" />
</template>

<script>
import PagesRoutes from '../../scripts/PagesRoutes.js';
import HeaderTelas from '../utils/HeaderTelas.vue';
import LayoutePageCardsLink from '../utils/LayoutePageCardsLink.vue';

export default {
  name: 'SystemUserPermissionHome',
  components: {
    HeaderTelas,
    LayoutePageCardsLink
  },
  data() {
    return {
      userandperms_cardIcon: PagesRoutes.filter(r => r.userandperms_cardIcon)
    };
  }
}
</script>

<style scoped>
</style>
