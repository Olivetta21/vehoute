<template>
    <div id="auth-container">
        <div id="triangulo-background"></div>
        <div id="conteudo">
            <div id="content-container" class="blue-card">
                <template v-if="backTo">
                    <div @click="$router.push({ name: backTo })" id="auth-cont-header">
                        <LeftArrow/>
                        <h1>{{ titulo }}</h1>
                    </div>
                </template>
                <h1 v-else>{{ titulo }}</h1>
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script>
import LeftArrow from '../LeftArrow.vue';

export default {
    name: 'AuthCont',
    props: {
        titulo: String,
        backTo: String
    },
    components: {
        LeftArrow
    }
}
</script>

<style scoped>

#triangulo-background {
    width: 100%;
    height: 100%;
    position: absolute;
    background: var(--colorC3);
    clip-path: polygon(100% 0, 100% 100%, 0 100%);
}

#auth-cont-header {
    display: flex;
    align-items: center;
    cursor: pointer;
}
#auth-cont-header:hover {
    color: var(--colorC4);
}

#conteudo {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);

    height: 100%;
    overflow: auto;
    scrollbar-width: auto;
    align-content: center;

    max-width: 100vw;
}

#content-container {
    min-width: max-content;
}



</style>