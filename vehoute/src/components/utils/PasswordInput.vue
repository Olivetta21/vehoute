<template>
    <div class="campo-senha">
        <input v-bind="$attrs" :type="visivel ? 'text' : 'password'" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
        <button type="button" class="btn-secondary mostrar" :aria-pressed="visivel" :title="visivel ? 'Ocultar senha' : 'Mostrar senha'" @click="visivel = !visivel">
            {{ visivel ? 'Ocultar' : 'Mostrar' }}
        </button>
    </div>
</template>

<script>
export default {
    name: 'PasswordInput',
    inheritAttrs: false,
    props: { modelValue: { type: String, default: '' } },
    emits: ['update:modelValue'],
    data() {
        return { visivel: false };
    }
}
</script>

<style scoped>
.campo-senha { display: flex; gap: var(--space-2); width: 100%; min-width: 0; }
.campo-senha input { flex: 1; min-width: 0; width: 100%; }
.mostrar { flex-shrink: 0; padding: 0 var(--space-2); font-size: var(--font-xs); }
</style>
