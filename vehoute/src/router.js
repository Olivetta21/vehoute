import { createRouter, createWebHistory } from 'vue-router'


const routes = [
  { path: '/login', name: 'login'},
  { path: '/home', name: 'home' },
  { path: '/:pathMatch(.*)*', redirect: '/login' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router