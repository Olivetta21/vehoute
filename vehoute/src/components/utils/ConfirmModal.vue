<template>
    <div class="confirm-fundo" @click.self="$emit('cancelar')">
        <div class="confirm-caixa" role="alertdialog" aria-modal="true" :aria-label="titulo">
            <h2>{{ titulo }}</h2>
            <p v-if="mensagem">{{ mensagem }}</p>
            <slot></slot>
            <div class="modal-actions">
                <button ref="cancelar" type="button" class="btn-secondary" @click="$emit('cancelar')">{{ textoCancelar }}</button>
                <button type="button" :class="{ 'red-button': perigo }" data-confirmar @click="$emit('confirmar')">{{ textoConfirmar }}</button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ConfirmModal',
    props: {
        titulo: { type: String, default: 'Confirmar' },
        mensagem: { type: String, default: '' },
        textoConfirmar: { type: String, default: 'Confirmar' },
        textoCancelar: { type: String, default: 'Cancelar' },
        perigo: { type: Boolean, default: false } // destaca ações que apagam ou removem
    },
    emits: ['confirmar', 'cancelar'],
    mounted() {
        this.focoAnterior = document.activeElement;
        // o foco começa em "Cancelar": um Enter distraído não confirma nada
        this.$refs.cancelar.focus();
        // captura: o Esc fecha só a confirmação, não o modal que estiver por baixo
        window.addEventListener('keydown', this.aoTeclar, true);
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this.aoTeclar, true);
        this.focoAnterior?.focus?.();
    },
    methods: {
        aoTeclar(evento) {
            if (evento.key !== 'Escape') return;
            evento.stopImmediatePropagation();
            this.$emit('cancelar');
        }
    }
}
</script>

<style scoped>
.confirm-fundo { position: fixed; inset: 0; z-index: 800; display: flex; align-items: center; justify-content: center; padding: var(--space-4); background: var(--color-overlay); backdrop-filter: blur(2px); }
.confirm-caixa { display: flex; flex-direction: column; gap: var(--space-3); width: min(var(--modal-width), 100%); max-height: 100%; overflow-y: auto; padding: var(--space-5); background: var(--color-surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); }
h2 { font-size: var(--font-lg); }
p { color: var(--color-text-muted); font-size: var(--font-sm); white-space: pre-line; overflow-wrap: anywhere; }
</style>
