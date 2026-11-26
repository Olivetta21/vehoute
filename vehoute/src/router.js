import { createRouter, createWebHistory } from 'vue-router'

import HomePage from './components/Pagina_Inicial/HomePage.vue'
import CadastroUsuarioPage from './components/Login_Cadastro/CadastroUsuario.vue'
import FinalizarCadastroUsuarioPage from './components/Login_Cadastro/FinalizarCadastroUsuarioPage.vue'
import RecuperacaoSenhaPage from './components/Login_Cadastro/RecuperacaoSenhaPage.vue'
import LoginPage from './components/Login_Cadastro/LoginPage.vue'
import PagesFrame from './components/PagesFrame.vue'
import MapPage from './components/MapaRastreamento/MapPage.vue'
import PerfilPage from './components/Perfil/PerfilPage.vue'
import ConfigPage from './components/Config/ConfigPage.vue'
import NotificationPage from './components/Notification/NotificationPage.vue'
const AdminHomePage = () => import('./components/Pagina_Inicial/AdminHomePage.vue')
const SystemUsersPage = () => import('./components/SystemUsers/SystemUsersPage.vue')
const SystemTrackersPage = () => import('./components/SystemTrackers/SystemTrackersPage.vue')

const SystemUserPermissionHome = () => import('./components/SystemUserPermission/SystemUserPermissionHome.vue')
const SystemUserPermissionGroupPage = () => import('./components/SystemUserPermission/SystemUserPermissionGroupPage.vue')
const SystemUserPermissionPage = () => import('./components/SystemUserPermission/SystemUserPermissionPage.vue')

const SystemTrackerPermissionHome = () => import('./components/SystemTrackerPermission/SystemTrackerPermissionHome.vue')
const SystemTrackerPermissionGroupPage = () => import('./components/SystemTrackerPermission/SystemTrackerPermissionGroupPage.vue')
const SystemTrackerPermissionPage = () => import('./components/SystemTrackerPermission/SystemTrackerPermissionPage.vue')

const SystemAuditPage = () => import('./components/SystemAudit/SystemAuditPage.vue')
const SystemLogPage = () => import('./components/SystemLog/SystemLogPage.vue')
import UserTrackersPage from './components/UserTrackers/UserTrackersPage.vue'
import TrackerOuvintesPage from './components/UserTrackers/TrackerOuvintesPage.vue'
import TrackerLocOcultaPage from './components/UserTrackers/TrackerLocOcultaPage.vue'

import Login from './scripts/LoginPage/Login'
import PagesRoutes from './scripts/PagesRoutes.js'
import TrackerOuvintes from './scripts/UserTracker/TrackerOuvintes.js'
import TrackerLocOculta from './scripts/UserTracker/TrackerLocOculta.js'
import MapPagina from './scripts/MapPage/Map.js'
import Perfil from './scripts/PerfilPage/Perfil.js'
import Config from './scripts/ConfigPage/Config.js'
import Notification from './scripts/NotificationPage/Notification.js'

function constructRoute(name, aditional) {
  //Para reutilizar o mesmo vetor
  const route = PagesRoutes.all.find(r => r.name === name);
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
      { path: 'finalizando', name: 'finalizar-cadastro', component: FinalizarCadastroUsuarioPage },
      { path: 'recuperacao', name: 'recuperacao-senha', component: RecuperacaoSenhaPage }
    ]
  },
  {
    path: '/site',
    meta: { requiresAuth: true },
    redirect: { name: 'home' },
    component: PagesFrame,
    children: [
      constructRoute('home', {component: HomePage }),
      constructRoute('map', {component: MapPage, meta: { class: MapPagina } }),
      constructRoute('perfil', {component: PerfilPage, meta: { class: Perfil } }),
      constructRoute('settings', {component: ConfigPage, meta: { class: Config } }),
      constructRoute('notifications', {component: NotificationPage, meta: { class: Notification } }),
      constructRoute('adminhome', {component: AdminHomePage }),
      constructRoute('sysusers', {component: SystemUsersPage }),
      constructRoute('systrackers', {component: SystemTrackersPage }),
      constructRoute('userandperms', {component: SystemUserPermissionHome }),
        constructRoute('usergroups', {component: SystemUserPermissionGroupPage }),
        constructRoute('userperms', {component: SystemUserPermissionPage }),
      constructRoute('trackerandperms', {component: SystemTrackerPermissionHome }),
        constructRoute('trackergroups', {component: SystemTrackerPermissionGroupPage }),
        constructRoute('trackerperms', {component: SystemTrackerPermissionPage }),
      constructRoute('audit', {component: SystemAuditPage }),
      constructRoute('logs', {component: SystemLogPage }),
      constructRoute('owntracker', {component : UserTrackersPage }),
        constructRoute('trackerouvintes', {component: TrackerOuvintesPage, meta: { class: TrackerOuvintes } }),
        constructRoute('trackerlococulta', {component: TrackerLocOcultaPage, meta: { class: TrackerLocOculta } }),
    ]
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'home', params: {} } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router