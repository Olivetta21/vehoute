
<template>
  <div class="links">
    <template v-for="(links, key) in items">
      <div
        v-for="(link, index) in links"
        :key="`${key}-${link.name || index}`"
        class="links-card"
        :style="{
          backgroundImage: `url('${link[key]}')`
        }"
        @click="$router.push({ name: link.name })"
      >
        <p class="card-name">{{ link.pageName }}</p>
      </div>
    </template>
  </div>
</template>


<script>
export default {
  name: 'LayoutePageCardsLink',
  props: {
    items: {
      type: Object,
      /* exemplo:
      {
        home_cardIcon: [
          {
            name: 'HomePage',
            pageName: 'Página Inicial',
            home_cardIcon: require('api/home.png')
          },
          {
            name: 'AdminHomePage',
            pageName: 'Administração',
            home_cardIcon: require('api/admin.png')
          }
        ]
      }
       */
      required: true
    }
  }
}
</script>

<style scoped>
.links {
  padding: 5px;
  height: 100%;
  width: 100%;

  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: space-evenly;
  align-items: center;
  overflow: auto;
  scrollbar-width: none;
  gap: 20px;
}

.links-card {
  width: calc(100% / 4);
  max-width: 200px;
  max-height: 200px;
  aspect-ratio: 1;
  border-radius: 30px;
  overflow: hidden;

  padding: 0;
  box-shadow: 4px 4px 8px 5px rgb(0 0 0 / 18%);
  transition: all 0.3s ease;

  background-color: white;
  background-size: 75%;
  background-repeat: no-repeat;
  background-position: top;

  text-align: end;
  align-content: end;
}
.links-card:hover {
  transform: scale(1.05);
  box-shadow: 2px 2px 8px 5px rgb(0 0 0 / 12%);
}

@media (orientation: portrait) {
  .links-card {
    width: calc((100% / 2) - 14px);
    min-width: 130px;
  }
}

.card-name {
  padding: 5px;
  border-radius: 10px;
  color: var(--colorD1);
  font-weight: bold;
  background-color: rgba(255, 255, 255, 0.671);
  text-align: center;
  font-size: 1.2rem;
  font-weight: 600;
}
</style>
