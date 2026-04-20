import { createRouter, createWebHistory } from 'vue-router'

import HomePage from './components/HomePage.vue'


const routes = [
  { path: '/login', name: 'login'},
  { path: '/home', name: 'home', component: HomePage },
  { path: '/:pathMatch(.*)*', redirect: '/login' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router