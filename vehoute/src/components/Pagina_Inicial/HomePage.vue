<template>
  <div class="home">
    <h1>Home</h1>
    <button @click="HomePage.testFetch()"> click </button>
  </div>
</template>

<script>
import HomePage from '../../scripts/HomePage/HomePage.js';

export default {
  name: 'HomePage',
  data() {
    return {
      HomePage
    };
  }
}
</script>


<style scoped>
.home {
  height: 100%;
  width: 100%;
  border: 1px solid red;
}

</style>