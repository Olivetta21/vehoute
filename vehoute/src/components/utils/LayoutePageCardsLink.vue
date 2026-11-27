
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
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(170px, 100%), 1fr));
    gap: var(--space-4);
    align-content: start;
    width: 100%;
    max-width: 1400px;
    padding: var(--space-5);
}
.links-card {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    aspect-ratio: 1;
    padding: var(--space-3);
    background-color: var(--color-surface);
    background-repeat: no-repeat;
    background-position: center 30%;
    background-size: 52%;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
}
.links-card:hover { transform: translateY(-2px); border-color: var(--color-primary); box-shadow: var(--shadow-md); }
.card-name { text-align: center; font-size: var(--font-sm); font-weight: var(--weight-bold); }

@media (max-width: 520px) {
    .links { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-3); padding: var(--space-3); }
}
</style>
