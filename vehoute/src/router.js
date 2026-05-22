import { createRouter, createWebHistory } from 'vue-router'

import HomePage from './components/Pagina_Inicial/HomePage.vue'
import CadastroUsuarioPage from './components/Login_Cadastro/CadastroUsuario.vue'
import FinalizarCadastroUsuarioPage from './components/Login_Cadastro/FinalizarCadastroUsuarioPage.vue'
import LoginPage from './components/Login_Cadastro/LoginPage.vue'
import PagesFrame from './components/PagesFrame.vue'
import AdminHomePage from './components/Pagina_Inicial/AdminHomePage.vue'

import Login from './scripts/LoginPage/Login'

const routes = [
  { path: '/login', name: 'login', component: LoginPage, meta: { class: Login } },
  {
    path: '/cadastro',
    children: [
      { path: 'fazendo', name: 'cadastro', component: CadastroUsuarioPage },
      { path: 'finalizando', name: 'finalizar-cadastro', component: FinalizarCadastroUsuarioPage }
    ]
  },
  {
    path: '/site',
    meta: { requiresAuth: true },
    redirect: { name: 'home' },
    component: PagesFrame,
    children: [
      { path: 'inicio', name: 'home', component: HomePage },
      { path: 'adminstrativo', name: 'adminhome', component: AdminHomePage },
    ]
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'home', params: {} } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router