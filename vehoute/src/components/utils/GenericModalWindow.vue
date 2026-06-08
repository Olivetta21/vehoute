<template>
    <div class="modal-window" @click.self="$emit('close')">
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: 'GenericModalWindow',
    emits: ['close']
}
</script>

<style scoped>

.modal-window {
    position: fixed;
    width: calc(100vw - 40px);
    height: 100vh;
    background-color: #0000007a;
    z-index: 30;
}

</style>