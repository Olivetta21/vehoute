<template>
  <LayouteHomePage :items="adminItens" />
</template>

<script>
import LayouteHomePage from './LayouteHomePage.vue';

export default {
  name: 'AdminHomePage',
  components: {
    LayouteHomePage
  },
  data() {
    return {
      adminItens: [
        { id: 1, name: 'Usuários do Sistema', icon: '/api/imagens/card_users.png', routeName: 'sysusers'},
        { id: 2, name: 'Rastreadores Do Sistema', icon: '/api/imagens/card_systracker.png', routeName: 'systrackers'},
        { id: 3, name: 'Permissões de Usuário', icon: '/api/imagens/card_userspermissions.png', routeName: 'userperms'},
        { id: 4, name: 'Permissões de Rastreador', icon: '/api/imagens/card_trackerpermissions.png', routeName: 'trackerperms'},
        { id: 5, name: 'Auditoria', icon: '/api/imagens/card_sysaudit.png', routeName: 'audit'},
        { id: 6, name: 'Logs', icon: '/api/imagens/card_syslogs.png', routeName: 'logs'},
      ]
    };
  }
}
</script>

<style scoped>
</style>
