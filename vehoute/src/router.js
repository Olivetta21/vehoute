import { createRouter, createWebHistory } from 'vue-router'

import HomePage from './components/Pagina_Inicial/HomePage.vue'
import CadastroUsuarioPage from './components/Login_Cadastro/CadastroUsuario.vue'
import FinalizarCadastroUsuarioPage from './components/Login_Cadastro/FinalizarCadastroUsuarioPage.vue'
import LoginPage from './components/Login_Cadastro/LoginPage.vue'
import PagesFrame from './components/PagesFrame.vue'
import MapPage from './components/MapaRastreamento/MapPage.vue'
import AdminHomePage from './components/Pagina_Inicial/AdminHomePage.vue'
import SystemUsersPage from './components/SystemUsers/SystemUsersPage.vue'
import SystemTrackersPage from './components/SystemTrackers/SystemTrackersPage.vue'
import UserTrackersPage from './components/UserTrackers/UserTrackersPage.vue'
import TrackerOuvintesPage from './components/UserTrackers/TrackerOuvintesPage.vue'

import Login from './scripts/LoginPage/Login'
import PagesRoutes from './scripts/PagesRoutes.js'
import TrackerOuvintes from './scripts/UserTracker/TrackerOuvintes.js'

function constructRoute(name, aditional) {
  //Para reutilizar o mesmo vetor
  const route = PagesRoutes.find(r => r.name === name);
  if (!route) {
    console.error("constructRoute", "Nenhuma rota encontrada com o nome: " + name);
    return null;
  }
  const mergedRoute = { ...route, ...aditional };
  return mergedRoute;  
}

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
      constructRoute('home', {component: HomePage }),
      constructRoute('map', {component: MapPage }),
      constructRoute('adminhome', {component: AdminHomePage }),
      constructRoute('sysusers', {component: SystemUsersPage }),
      constructRoute('systrackers', {component: SystemTrackersPage }),
      constructRoute('owntracker', {component : UserTrackersPage }),
        constructRoute('trackerouvintes', {component: TrackerOuvintesPage, meta: { class: TrackerOuvintes } }),
    ]
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'home', params: {} } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router