import { createRouter, createWebHistory } from 'vue-router'

import HomePage from './components/HomePage.vue'
import CadastroUsuarioPage from './components/Login_Cadastro/CadastroUsuario.vue'
import FinalizarCadastroUsuarioPage from './components/Login_Cadastro/FinalizarCadastroUsuarioPage.vue'
import LoginPage from './components/Login_Cadastro/LoginPage.vue'
import PagesFrame from './components/PagesFrame.vue'

import Login from './scripts/LoginPage/Login'

const routes = [
  { path: '/login', name: 'login', component: LoginPage, meta: {class: Login} },
  { path: '/cadastro',
    children:[
      { path: 'fazendo', name: 'cadastro', component: CadastroUsuarioPage },
      { path: 'finalizando', name: 'finalizar-cadastro', component: FinalizarCadastroUsuarioPage }
    ]
  },
  { path: '/site',
    component: PagesFrame,
    children: [
      { path: 'inicio', name: 'home', component: HomePage, meta: { requiresAuth: true } }
    ]
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'home' } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router