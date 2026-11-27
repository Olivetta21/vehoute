<template>
  <LoadingBar />
  <ConfirmModal
      v-if="estadoConfirmacao.pedido"
      :key="estadoConfirmacao.pedido.opcoes.mensagem"
      v-bind="estadoConfirmacao.pedido.opcoes"
      @confirmar="responderConfirmacao(true)"
      @cancelar="responderConfirmacao(false)"
  />
  <RouterView/>
  
        <ToastMessage />
</template>

<script>
import ToastMessage from './components/ToastMessage/ToastMessage.vue';
import LoadingBar from './components/utils/LoadingBar.vue';
import ConfirmModal from './components/utils/ConfirmModal.vue';
import { estadoConfirmacao, responderConfirmacao } from './scripts/confirmacao';
import FullScreenController from './scripts/PagesFrame/FullScreenController.js';
import './Styles/global.css'
  export default {
    name: 'App',
    components: {
      ToastMessage,
      LoadingBar,
      ConfirmModal
    },
    data() {
      return { estadoConfirmacao };
    },
    methods: {
      responderConfirmacao
    },
    watch: {
      // sair da tela equivale a cancelar a pergunta
      '$route.path'() {
        responderConfirmacao(false);
      }
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