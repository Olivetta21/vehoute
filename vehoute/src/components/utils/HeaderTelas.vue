<template>
    <header class="header-telas">
        <div class="title-header-container">
            <button v-if="mostrarVoltar" class="ico-button btn-secondary" aria-label="Voltar" @click="$emit('voltar')">
                ←
            </button>
            <h1>{{ titulo }}</h1>
        </div>

        <div class="pesquisa-header-container" v-if="mostrarPesquisa || mostrarAdicionar">
            <template v-if="mostrarPesquisa">
                <input
                    type="text"
                    placeholder="Pesquisar..."
                    v-model="textoPesquisa"
                    @keyup.enter="$emit('pesquisa', textoPesquisa)"
                >
                <button class="btn-secondary" @click="$emit('pesquisa', textoPesquisa)">Pesquisar</button>
            </template>

            <button v-if="mostrarAdicionar" class="ico-button" aria-label="Adicionar" @click="$emit('adicionar')">
                +
            </button>
        </div>
    </header>
</template>

<script>
export default {
    name: 'HeaderTelas',
    props: {
        titulo: String,
        mostrarVoltar: Boolean,
        mostrarAdicionar: Boolean,
        mostrarPesquisa: Boolean
    },

    data() {
        return {
            textoPesquisa: ""
        };
    }
};
</script>

<style scoped>
.header-telas {
    position: sticky;
    top: 0;
    z-index: 5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    min-height: var(--header-height);
    padding: var(--space-3) var(--space-5);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
}
.title-header-container,
.pesquisa-header-container { display: flex; align-items: center; gap: var(--space-2); min-width: 0; }
h1 { font-size: var(--font-lg); overflow-wrap: anywhere; }
input { flex: 1; width: 16rem; min-width: 0; }
.ico-button { flex-shrink: 0; width: var(--control-height); padding: 0; font-size: var(--font-lg); }

@media (max-width: 520px) {
    .header-telas { padding: var(--space-3); }
    .pesquisa-header-container { width: 100%; justify-content: flex-end; }
}
</style>
