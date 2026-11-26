<template>
    <div id="pagesframe">
        <aside :class="['sidebar', { 'is-collapsed': isCollapsed }]">
            <div class="sidebar-header">
                <div class="header-content" v-if="!isCollapsed">
                    <span class="logo-text">Vehoute</span>
                </div>
                <button class="toggle-btn btn-secondary" aria-label="Alternar menu" @click="toggleSidebar" :class="{'rotated': isCollapsed}">
                    <LeftArrow />
                </button>
            </div>

            <div class="sidebar-nav">
                <div class="nav-section" v-if="!isCollapsed">Acesso rápido
                    <svg v-if="!FullScreenController.is_fullscreen" class="toggle-fullscreen-btn" @click="FullScreenController.toggleFullScreen"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 3 21 3 21 9"/>
                        <polyline points="9 21 3 21 3 15"/>
                        <line x1="21" y1="3" x2="14" y2="10"/>
                        <line x1="3" y1="21" x2="10" y2="14"/>
                    </svg>
                    <svg v-else class="toggle-fullscreen-btn" @click="FullScreenController.toggleFullScreen"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="4 14 10 14 10 20"/>
                        <polyline points="20 10 14 10 14 4"/>
                        <line x1="14" y1="10" x2="21" y2="3"/>
                        <line x1="3" y1="21" x2="10" y2="14"/>
                    </svg>
                </div>
                <ul class="nav-list">
                    <li v-for="item in menuItems" :key="item.id" 
                        class="nav-link" 
                        :class="{ 'active': isActive(item) }"
                        :title="item.pageName"
                        @click="navigateTo(item)">
                        <div class="icon-wrapper">
                            <img :src="item.sidebarIcon" :alt="item.pageName" />
                        </div>
                        <span class="link-text" v-if="!isCollapsed">{{ item.pageName }}</span>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="page-content-wrapper">
            <router-view />
        </main>
    </div>
</template>

<script>
import FullScreenController from '../scripts/PagesFrame/FullScreenController.js';
import PagesRoutes from '../scripts/PagesRoutes.js';
import LeftArrow from './utils/LeftArrow.vue';

export default {
    name: 'PagesFrame',
    components: {
        LeftArrow
    },
    data() {
        return {
            FullScreenController,
            isCollapsed: false,
            menuItems: PagesRoutes.byCaracteristic('sidebarIcon')
        }
    },
    methods: {
        toggleSidebar() {
            this.isCollapsed = !this.isCollapsed;
        },
        navigateTo(item) {
            this.$router.push({ name: item.name});
        },
        isActive(item) {
            return this.$route.name === item.name;
        }
    }
}
</script>

<style scoped>
#pagesframe { display: flex; width: 100%; height: 100%; overflow: hidden; background: var(--color-bg); }

.sidebar {
    position: absolute;
    z-index: 10;
    display: flex;
    flex-direction: column;
    width: var(--sidebar-width);
    height: 100%;
    overflow: hidden;
    color: var(--sidebar-text);
    background: var(--sidebar-bg);
    border-right: 1px solid var(--color-border);
    box-shadow: var(--shadow-lg);
    transition: width var(--transition);
}
.sidebar.is-collapsed { width: var(--sidebar-collapsed); box-shadow: none; }

.sidebar-header {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    height: var(--header-height);
    padding: 0 var(--space-3) 0 var(--space-4);
    border-bottom: 1px solid var(--color-border);
}
.sidebar.is-collapsed .sidebar-header { justify-content: center; padding: 0; }
.logo-text { color: var(--color-text); font-size: var(--font-lg); font-weight: var(--weight-bold); white-space: nowrap; }

.toggle-btn { width: var(--control-height-sm); min-height: var(--control-height-sm); padding: 0; }
.toggle-btn svg { width: 18px; height: 18px; transition: transform var(--transition); }
.toggle-btn.rotated svg { transform: rotate(180deg); }

.sidebar-nav {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--space-2);
    padding: var(--space-3);
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-width: none;
}
.sidebar.is-collapsed .sidebar-nav { padding: var(--space-2); }

.nav-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 var(--space-2);
    color: var(--color-text-subtle);
    font-size: var(--font-xs);
    font-weight: var(--weight-bold);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}
.toggle-fullscreen-btn { width: 16px; height: 16px; cursor: pointer; }
.toggle-fullscreen-btn:hover { color: var(--color-primary); }

.nav-list { display: flex; flex-direction: column; gap: var(--space-1); list-style: none; }
.nav-link {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    height: var(--control-height);
    padding: 0 var(--space-2);
    overflow: hidden;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}
.sidebar.is-collapsed .nav-link { justify-content: center; padding: 0; }
.nav-link:hover { color: var(--color-text); background: var(--color-surface-muted); }
.nav-link.active { color: var(--color-primary); background: var(--color-primary-soft); }

.icon-wrapper { display: flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; }
.icon-wrapper img { width: 18px; height: 18px; filter: var(--sidebar-icon-filter); opacity: 0.5; transition: opacity var(--transition); }
.nav-link:hover img,
.nav-link.active img { opacity: 0.95; }
.link-text { margin-left: var(--space-3); font-size: var(--font-sm); font-weight: var(--weight-medium); white-space: nowrap; }

.page-content-wrapper {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-width: 0;
    height: 100%;
    margin-left: var(--sidebar-collapsed);
    overflow-y: auto;
    background: var(--color-bg);
}
.page-content-wrapper > :deep(*) { flex-shrink: 0; }
</style>
