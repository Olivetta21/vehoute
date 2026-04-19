import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

const app = createApp(App)

router.beforeEach(async (to, from, next) => {
    console.log('Rota atual:', to.path);
    console.log('Rota anterior:', from.path);
    const fromMeta = from.matched.at(-1)?.meta;
    const toMeta   = to.matched.at(-1)?.meta;
    
    if (typeof fromMeta?.class?.before_leave === 'function') fromMeta.class.before_leave();

    if (to.meta?.requiresAuth) {
        next('/login');
    } else {
        if (typeof toMeta?.class?.before_enter === 'function') toMeta.class.before_enter();
        next();
        if (typeof toMeta?.class?.after_enter === 'function') toMeta.class.after_enter();
    }
    
    if (typeof fromMeta?.class?.after_leave === 'function') fromMeta.class.after_leave();
    
})

app.use(router).mount('#app')