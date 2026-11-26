import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import Login from './scripts/LoginPage/Login'
import Usuario from './scripts/LoginPage/Usuario'
import PagesRoutes from './scripts/PagesRoutes'
import ToastMessage from './scripts/ToastMessage/ToastMessage'
import { EVENTO_SESSAO_EXPIRADA } from './scripts/fetcher'

const app = createApp(App)

router.beforeEach(async (to, from) => {
    if (to.matched.some(r => r.meta?.requiresAuth)) {
        if (!(await Login.isAuthenticated())) {
            return { name: 'login' }
        }
        // Telas administrativas: o backend também valida; aqui evitamos abrir a tela
        const pagina = PagesRoutes.all.find(r => r.name === to.name)
        if (pagina?.permissions && !pagina.permissions.some(p => Usuario.hasPermission(p))) {
            ToastMessage.add('Acesso negado', 'Você não tem permissão para acessar esta página.', 'warn')
            return { name: 'home' }
        }
    }

    // Os ganchos só rodam depois que a navegação foi autorizada
    from.matched.at(-1)?.meta?.class?.before_leave?.()
    to.matched.at(-1)?.meta?.class?.before_enter?.()
})

router.afterEach((to, from) => {
    from.matched.at(-1)?.meta?.class?.after_leave?.()
    to.matched.at(-1)?.meta?.class?.after_enter?.()

    const pagina = PagesRoutes.all.find(r => r.name === to.name)
    document.title = pagina && to.name !== 'login' ? `${pagina.pageName} · Vehoute` : 'Vehoute'
})

window.addEventListener(EVENTO_SESSAO_EXPIRADA, () => {
    Login.encerrarSessaoLocal()
    if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login' })
    }
})

app.config.errorHandler = (error, instance, info) => {
    console.error('Erro não tratado:', info, error)
    ToastMessage.add('Erro inesperado', 'Algo deu errado. Tente novamente.', 'error')
}

app.use(router).mount('#app')
