<template>
  <div class="home">
    <div class="home-card" v-for="value in homeItens" :key="value.id"
    @click="$router.push({name: value.routeName})"
    :style="{'background-image': 'url(' + value.icon + ')'}">
      <p class="card-nome">{{value.name}}</p>
    </div>
  </div>
</template>

<script>
import HomePage from '../../scripts/HomePage/HomePage.js';

export default {
  name: 'HomePage',
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
.home {
  padding: 5px;
  height: 100%;
  width: 100%;
  border: 1px solid red;

  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: space-evenly;
  align-items: center;
  overflow: auto;
  scrollbar-width: none;
  gap: 20px;
}

.home-card {
  width: calc(100% / 4);
  aspect-ratio: 1;
  border-radius: 30px;

  padding: 20px;
  box-shadow: 4px 4px 8px 5px rgb(0 0 0 / 18%);
  transition: all 0.3s ease;

  background-size: cover;
  background-position: center;

  text-align: end;
  align-content: end;
}
.home-card:hover {
  transform: scale(1.05);
  box-shadow: 2px 2px 8px 5px rgb(0 0 0 / 12%);
}

@media (orientation: portrait) {
  .home-card {
    width: auto;
    height: calc(100% / 4);
  }
}

.card-nome {
  padding: 5px;
  border-radius: 10px;
  color: var(--colorD1);
  background-color: white;
  text-align: center;
  font-size: 1.2rem;
  font-weight: 600;
}
</style>