<template>
    <header class="header-telas">
        <div class="pesquisa-header-container" v-if="mostrarPesquisa || mostrarAdicionar">
            <template v-if="mostrarPesquisa">
                <input
                    type="text"
                    placeholder="Pesquisar..."
                    v-model="textoPesquisa"
                    @keyup.enter="$emit('pesquisa', textoPesquisa)"
                >
                <button @click="$emit('pesquisa', textoPesquisa)">Pesquisar</button>
            </template>

            <button v-if="mostrarAdicionar" class="ico-button" @click="$emit('adicionar')">
                +
            </button>
        </div>

        <div class="title-header-container">
            <button v-if="mostrarVoltar" class="ico-button" @click="$emit('voltar')">
                ←
            </button>
            <p>{{ titulo }}</p>
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
    display: flex;
    flex-direction: column;
    padding: 2px;
    gap: 5px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #ddd;
}
.pesquisa-header-container, .title-header-container {
    display: flex;
    gap: 2px;
}
.title-header-container {
    align-items: center;
}
input {
    padding: 2px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
button {
    padding: 4px;
}
.ico-button {
    font-size: 18px;
    font-weight: bold;
    border: none;
    border-radius: 4px;
    aspect-ratio: 1;
    height: 100%;
}

</style>