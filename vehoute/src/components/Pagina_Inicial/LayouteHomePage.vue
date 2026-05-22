<template>
  <div class="home">
    <div class="home-card" v-for="value in items" :key="value.id"
    @click="$router.push({name: value.routeName})"
    :style="{'background-image': 'url(' + value.icon + ')'}">
      <p class="card-nome">{{value.name}}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LayouteHomePage',
  props: {
    items: {
      type: Array,
      required: true
    }
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
  max-width: 200px;
  aspect-ratio: 1;
  border-radius: 30px;
  overflow: hidden;

  padding: 0;
  box-shadow: 4px 4px 8px 5px rgb(0 0 0 / 18%);
  transition: all 0.3s ease;

  background-size: 75%;
  background-repeat: no-repeat;
  background-position: top;

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
  font-weight: bold;
  background-color: rgba(255, 255, 255, 0.671);
  text-align: center;
  font-size: 1.2rem;
  font-weight: 600;
}
</style>
