<template>
  <div class="home">
    <h1>Home</h1>
    <button @click="HomePage.testFetch()"> click </button>
  </div>
</template>

<script>
import HomePage from '../scripts/HomePage/HomePage.js';

export default {
  name: 'HomePage',
  data() {
    return {
      HomePage
    };
  }
}
</script>
