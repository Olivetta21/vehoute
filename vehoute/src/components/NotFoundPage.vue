<template>
    <div class="nao-encontrada">
        <h1>404</h1>
        <p>A página que você procurou não existe.</p>
        <button type="button" @click="$router.push({ name: 'home' })">Ir para o início</button>
    </div>
</template>

<script>
export default { name: 'NotFoundPage' }
</script>

<style scoped>
.nao-encontrada { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: var(--space-3); height: 100%; padding: var(--space-5); text-align: center; }
</style>
