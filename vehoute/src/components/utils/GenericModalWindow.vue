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
    inset: 0 0 0 var(--sidebar-collapsed);
    z-index: 30;
    background: var(--color-overlay);
    backdrop-filter: blur(2px);
}
</style>
