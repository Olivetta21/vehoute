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
import LeftArrow from '../utils/LeftArrow.vue';

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
#auth-container { position: relative; width: 100%; height: 100%; overflow: auto; background: var(--color-bg); }
#triangulo-background {
    position: fixed;
    inset: 0 0 auto 0;
    height: 45%;
    background: linear-gradient(var(--color-primary-soft), var(--color-bg));
}
#conteudo { position: relative; display: flex; min-height: 100%; padding: var(--space-4); }
#content-container {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    width: min(380px, 100%);
    margin: auto;
}
h1 { text-align: center; }
#auth-cont-header { position: relative; display: flex; align-items: center; justify-content: center; padding: 0 var(--space-6); cursor: pointer; }
#auth-cont-header svg { position: absolute; left: 0; }
#auth-cont-header:hover { color: var(--color-primary); }
</style>
