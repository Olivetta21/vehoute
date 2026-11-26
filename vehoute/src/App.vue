<template>
  <RouterView/>
  
        <ToastMessage />
</template>

<script>
import ToastMessage from './components/ToastMessage/ToastMessage.vue';
import FullScreenController from './scripts/PagesFrame/FullScreenController.js';
import './Styles/global.css'
  export default {
    name: 'App',
    components: {
      ToastMessage
    },
    mounted() {
      FullScreenController.init();
    }
  }
</script>

<style>
  #app {
    width: 100%;
    height: 100dvh;
  }
</style>