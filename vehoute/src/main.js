import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import Login from './scripts/LoginPage/Login'

const app = createApp(App)

router.beforeEach(async (to, from) => {
    console.log('Rota atual:', to.path);
    console.log('Rota anterior:', from.path);
    const fromMeta = from.matched.at(-1)?.meta
    const toMeta   = to.matched.at(-1)?.meta

    fromMeta?.class?.before_leave?.()

    if (to.meta?.requiresAuth && !(await Login.isAuthenticated())) {
        return { name: 'login' }
    }

    toMeta?.class?.before_enter?.()
})

router.afterEach((to, from) => {
    const fromMeta = from.matched.at(-1)?.meta
    const toMeta   = to.matched.at(-1)?.meta

    fromMeta?.class?.after_leave?.()
    toMeta?.class?.after_enter?.()
})

app.use(router).mount('#app')