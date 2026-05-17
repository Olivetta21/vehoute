<template>
    <div id="pagesframe">
        <aside :class="['sidebar', { 'is-collapsed': isCollapsed }]">
            <div class="sidebar-header">
                <div class="header-content" v-if="!isCollapsed">
                    <span class="logo-text">Vehoute</span>
                </div>
                <button class="toggle-btn" @click="toggleSidebar" :class="{'rotated': isCollapsed}">
                    <LeftArrow />
                </button>
            </div>

            <div class="sidebar-nav">
                <div class="nav-section">{{ isCollapsed ? '' : 'Acesso rápido' }}</div>
                <ul class="nav-list">
                    <li v-for="item in menuItems" :key="item.id" 
                        class="nav-link" 
                        :class="{ 'active': isActive(item) }"
                        @click="navigateTo(item)">
                        <div class="icon-wrapper">
                            <img :src="item.icon" :alt="item.name" />
                        </div>
                        <span class="link-text" v-if="!isCollapsed">{{ item.name }}</span>
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
import LeftArrow from './utils/LeftArrow.vue';

export default {
    name: 'PagesFrame',
    components: {
        LeftArrow
    },
    data() {
        return {
            isCollapsed: false,
            menuItems: [
                { id: 1, name: 'Página inicial', icon: '/api/imagens/icon_blackhome64.png', routeName: 'home'}
            ]
        }
    },
    methods: {
        toggleSidebar() {
            this.isCollapsed = !this.isCollapsed;
        },
        navigateTo(item) {
            this.$router.push({ name: item.routeName});
        },
        isActive(item) {
            return this.$route.name === item.routeName;
        }
    }
}
</script>

<style scoped>
    #pagesframe {
        display: flex;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
        background-color: var(--colorA1);
    }
    
    /* Sidebar Base */
    .sidebar {
        position: absolute;
        background-color: var(--colorD1);
        color: var(--colorA1);
        width: 260px;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: width 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
        z-index: 10;
        overflow: hidden;
    }

    .sidebar.is-collapsed {
        width: 40px;
    }

    /* Header */
    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px;
        height: 80px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar.is-collapsed .sidebar-header {
        justify-content: center;
        padding: 0;
    }

    .logo-text {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--colorA1);
        white-space: nowrap;
        letter-spacing: 0.5px;
    }

    /* Toggle Button */
    .toggle-btn {
        background: rgba(255, 255, 255, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .toggle-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }

    .toggle-btn.rotated svg {
        transform: rotate(180deg);
    }

    /* Navigation */
    .sidebar-nav {
        padding: 20px 10px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .sidebar.is-collapsed .sidebar-nav {
        padding: 0;
    }

    .nav-section {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--colorA2);
        margin: 10px 0 10px 10px;
        white-space: nowrap;
        font-weight: 600;
    }

    /* Links */
    .nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        cursor: pointer;
        color: rgba(255, 255, 255, 0.6);
        padding: 12px;
        border-radius: 12px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .sidebar.is-collapsed .nav-link {
        justify-content: center;
        padding: 0;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .nav-link.active {
        color: white;
        box-shadow: 0 4px 15px rgb(17, 98, 230);
    }

    .icon-wrapper {
        min-width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-wrapper img {
        width: 20px;
        height: 20px;
        filter: brightness(0) invert(1);
        opacity: 0.5;
        transition: all 0.3s ease;
    }

    .nav-link:hover .icon-wrapper img,
    .nav-link.active .icon-wrapper img {
        opacity: 1;
        transform: scale(1.2);
    }

    .link-text {
        margin-left: 16px;
        font-size: 1rem;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Main Content */
    .page-content-wrapper {
        flex: 1;
        overflow-y: auto;
        margin-left: 40px;
        background-color: #f7f9fc;
    }
</style>