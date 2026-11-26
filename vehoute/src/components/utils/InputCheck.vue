<template>
    <div class="input-check">
        <slot class="input-slot"></slot>
        <template v-if="!this.check">
            <input
                type="checkbox"
                class="checkbox-hidden"
                required
                tabindex="-1"
                aria-hidden="true"
                @invalid="$event.target.setCustomValidity(this.inv)"
            />
            <p>{{ inv }}</p>
        </template>
    </div>
</template>

<script>
export default {
    name: 'InputCheck',
    props: {
        check: {
            type: Boolean,
            required: false
        },
        inv: {
            type: String,
            required: false,
            default: 'Parametro inválido'
        }
    },
    emits: ['@update:value']
}
</script>

<style scoped>
.input-check { display: flex; flex-direction: column; align-items: stretch; gap: var(--space-1); text-align: left; }
p { color: var(--color-danger); font-size: var(--font-xs); }
.checkbox-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
