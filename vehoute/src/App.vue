<template>
  <RouterView/>
  
        <ToastMessage />
</template>

<script>
import ToastMessage from './components/ToastMessage/ToastMessage.vue';
import './Styles/global.css'
  export default {
    name: 'App',
    components: {
      ToastMessage
    }
  }
</script>

<style>
  #app {
    width: 100vw;
    height: 100dvh;
  }
</style>