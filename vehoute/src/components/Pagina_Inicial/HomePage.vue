<template>
  <LayouteHomePage :items="homeItens" />
</template>

<script>
import HomePage from '../../scripts/HomePage/HomePage.js';
import LayouteHomePage from './LayouteHomePage.vue';

export default {
  name: 'HomePage',
  components: {
    LayouteHomePage
  },
  data() {
    return {
      HomePage,
      homeItens: [
        { id: 1, name: 'Mapa', icon: '/api/imagens/card_map.png', routeName: 'map'},
        { id: 2, name: 'Seus Rastreadores', icon: '/api/imagens/card_seus_rastreadores.png', routeName: 'owntracker'},
        { id: 3, name: 'Perfil', icon: '/api/imagens/card_perfil.png', routeName: 'perfil'},
        { id: 4, name: 'Configurações', icon: '/api/imagens/card_engrenagem.png', routeName: 'settings'},
        { id: 5, name: 'Notificações', icon: '/api/imagens/card_sino.png', routeName: 'notifications'},
        { id: 6, name: 'Administrativo', icon: '/api/imagens/card_admin.png', routeName: 'adminhome'}
      ]
    };
  }
}
</script>

<style scoped>
</style>