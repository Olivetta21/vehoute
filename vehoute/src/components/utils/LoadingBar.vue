<template>
    <div v-if="offline" class="aviso-offline" role="alert">Sem conexão com a internet.</div>
    <div v-if="carregando" class="carregando" role="status" aria-live="polite">
        <div class="barra"></div>
        <div class="selo"><span class="giro" aria-hidden="true"></span> Carregando…</div>
    </div>
</template>

<script>
import { estadoCarregamento } from '../../scripts/loading';

export default {
    name: 'LoadingBar',
    data() {
        return { offline: typeof navigator !== 'undefined' && navigator.onLine === false };
    },
    computed: {
        carregando() {
            return estadoCarregamento.pendentes > 0 || estadoCarregamento.navegando;
        }
    },
    mounted() {
        window.addEventListener('online', this.atualizar);
        window.addEventListener('offline', this.atualizar);
    },
    beforeUnmount() {
        window.removeEventListener('online', this.atualizar);
        window.removeEventListener('offline', this.atualizar);
    },
    methods: {
        atualizar() { this.offline = !navigator.onLine; }
    }
}
</script>

<style scoped>
.carregando { position: fixed; inset: 0 0 auto 0; z-index: 900; pointer-events: none; }
/* respostas rápidas não fazem a tela piscar */
.carregando > * { opacity: 0; animation: surgir 0.15s linear 0.15s forwards; }
.barra { height: 3px; background: linear-gradient(90deg, transparent, var(--color-primary), transparent) 0 0 / 50% 100% no-repeat var(--color-primary-soft); }
.carregando .barra { animation: surgir 0.15s linear 0.15s forwards, correr 1.1s linear infinite; }
.selo {
    position: fixed; right: var(--space-4); bottom: var(--space-4);
    display: flex; align-items: center; gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    font-size: var(--font-sm); color: var(--color-text);
    background: var(--color-surface); border: 1px solid var(--color-border);
    border-radius: var(--radius-pill); box-shadow: var(--shadow-md);
}
.giro { width: 14px; height: 14px; border: 2px solid var(--color-primary); border-right-color: transparent; border-radius: 50%; animation: girar 0.7s linear infinite; }
.aviso-offline { position: fixed; inset: auto 0 0 0; z-index: 950; padding: var(--space-2); text-align: center; font-size: var(--font-sm); color: var(--color-on-primary); background: var(--color-danger); }
@keyframes surgir { to { opacity: 1; } }
@keyframes correr { from { background-position: -100% 0; } to { background-position: 200% 0; } }
@keyframes girar { to { transform: rotate(360deg); } }
</style>
