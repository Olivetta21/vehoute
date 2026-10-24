<template>
    <div id="toastcontainer" v-if="ToastMessage.toasts?.length > 0">
        <transition-group name="slide" tag="div">
            <div v-for="(toast) in ToastMessage.toasts" :key="toast.id" @click.stop="ToastMessage.removeById(toast.id)" class="toast-wrapper">
                <div :class="['toast', toast.type]">
                    <div class="textToast title"> {{ toast.title }} </div>
                    <div class="textToast message"> {{ toast.message }} </div>
                </div>
            </div>
        </transition-group>
    </div>
</template>

<script>
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';

export default {
	data() {
		return {
            ToastMessage
		};
	}
}
</script>

<style scoped>

  #toastcontainer {
    position: fixed;
    top: 0;
    right: 0;

    margin: 20px 20px 20px 0;

    width: 300px;
    z-index: 1000;

    max-height: calc(100% - 40px);
    overflow-x: hidden;
    overflow-y: scroll;
    scrollbar-width: none;

    pointer-events: none;
  }
  
  .toast-wrapper {
    padding: 10px;
  }
  
  .toast {
    background-color: #070707;
    color: white;
    padding: 10px;
    border-radius: 5px;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
    cursor: pointer;
    pointer-events: all;
    border: 2px solid rgba(0, 0, 0, 0);
    backdrop-filter: blur(3px);
  }

  .toast:hover {
    border: 2px solid black;
    backdrop-filter: blur(0);
  }


  .textToast {
    white-space: pre-line;
    text-overflow: ellipsis;
    overflow: hidden;
  }

  .textToast.title {
    text-wrap: nowrap;
    margin-top: -12px;
    margin-left: -12px;
    margin-right: -12px;

    font-size: xx-small;
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 0.2em;
    text-align: center;
  }
  .textToast.message {
    font-size: small;
    text-wrap: wrap;
    text-shadow: 0px 0px 5px black;
  }

  .toast.success {
	background-color: #4caf4fb9; /* Verde para sucesso */
  }
  
  .toast.error {
	background-color: #f44336b9; /* Vermelho para erro */
  }
  
  .toast.warn {
	background-color: #ffee00b9; /* Laranja para alerta */
  }

  .toast.info {
	background-color: #002fffb9; /* Laranja para alerta */
  }


</style>