import Usuario from "./LoginPage/Usuario";

const statics_routes = [
    // Páginas principais
    { pageName: 'Página inicial', name: 'home', path: 'inicio',
        sidebarIcon: '/api/imagens/icon_blackhome64.png'
    },
    { pageName: 'Mapa', name: 'map', path: 'mapa',
        home_cardIcon: '/api/imagens/card_map.png',
        sidebarIcon: '/api/imagens/icon_blackmap64.png'
    },
    { pageName: 'Seus Rastreadores', name: 'owntracker', path: 'seus-rastreadores',
        home_cardIcon: '/api/imagens/card_seus_rastreadores.png',
        sidebarIcon: '/api/imagens/icon_blacktracker64.png'
    },
        { pageName: 'Ouvintes do Rastreador', name: 'trackerouvintes', path: 'ouvintes-do-rastreador'},
        { pageName: 'Localização Oculta do Rastreador', name: 'trackerlococulta', path: 'localizacao-oculta-do-rastreador'},

    { pageName: 'Perfil', name: 'perfil', path: 'perfil',
        home_cardIcon: '/api/imagens/card_perfil.png',
        sidebarIcon: '/api/imagens/icon_blackperfil64.png'
    }, 
    { pageName: 'Configurações', name: 'settings', path: 'configuracoes',
        home_cardIcon: '/api/imagens/card_engrenagem.png',
        sidebarIcon: '/api/imagens/icon_blackengrenagem64.png'
    },
    { pageName: 'Notificações', name: 'notifications', path: 'notificacoes',
        home_cardIcon: '/api/imagens/card_sino.png',
        sidebarIcon: '/api/imagens/icon_blacknotification64.png'
    },
    { pageName: 'Administrativo', name: 'adminhome', path: 'administrativo',
        home_cardIcon: '/api/imagens/card_admin.png', 
        sidebarIcon: '/api/imagens/icon_blackadminhome64.png',
        permissions: ['Administrador']
    },

    // Subpáginas do Administrativo
    { pageName: 'Usuários do Sistema', name: 'sysusers', path: 'usuariosdosistema',
        admin_cardIcon: '/api/imagens/card_users.png',
        sidebarIcon: '/api/imagens/icon_blackusers64.png',
        permissions: ['Administrador']
    },
    { pageName: 'Rastreadores do Sistema', name: 'systrackers', path: 'rastreadoresdosistema',
        admin_cardIcon: '/api/imagens/card_systracker.png',
        sidebarIcon: '/api/imagens/icon_blacksystrackers64.png',
        permissions: ['Administrador']
    },
    { pageName: 'Usuarios e Permissões', name: 'userandperms', path: 'userandpermissions',
        admin_cardIcon: '/api/imagens/card_userspermissions.png',
        sidebarIcon: '/api/imagens/icon_blackuserperms64.png',
        permissions: ['Administrador']
    },
        { pageName: 'Grupo de Usuários', name: 'usergroups', path: 'gruposdeusuario',
            userandperms_cardIcon: '/api/imagens/card_users.png',
        permissions: ['Administrador']
        },
        { pageName: 'Permissoes de Usuário', name: 'userperms', path: 'permissoesdeusuario',
            userandperms_cardIcon: '/api/imagens/card_userspermissions.png',
        permissions: ['Administrador']
        },
    { pageName: 'Rastreadores e Permissões', name: 'trackerandperms', path: 'trackerandpermissions',
        admin_cardIcon: '/api/imagens/card_trackerpermissions.png',
        sidebarIcon: '/api/imagens/icon_blacktrackerperms64.png',
        permissions: ['Administrador']
    },
        { pageName: 'Grupo de Rastreadores', name: 'trackergroups', path: 'gruposderastreador',
            trackerandperms_cardIcon: '/api/imagens/card_systracker.png',
        permissions: ['Administrador']
        },
        { pageName: 'Permissoes de Rastreador', name: 'trackerperms', path: 'permissoesderastreador',
            trackerandperms_cardIcon: '/api/imagens/card_trackerpermissions.png',
        permissions: ['Administrador']
        },


    { pageName: 'Auditoria', name: 'audit', path: 'auditoria',
        admin_cardIcon: '/api/imagens/card_sysaudit.png',
        sidebarIcon: '/api/imagens/icon_blackaudit64.png',
        permissions: ['Administrador']
    },
    { pageName: 'Logs', name: 'logs', path: 'logs',
        admin_cardIcon: '/api/imagens/card_syslogs.png',
        sidebarIcon: '/api/imagens/icon_blacklogs64.png',
        permissions: ['Administrador']
    },

    
    { pageName: 'Sair', name: 'login',
        sidebarIcon: '/api/imagens/icon_blacklogoff64.png'
    },
]

function getUserPermissions() {
    return Usuario.permissoes || [];
}

export default class PagesRoutes {
    static get all() {
        return statics_routes;
    }

    static byPermission() {
        return statics_routes.filter(page => {
            if (!page.permissions) return true;
            return page.permissions.map(p => p.toLowerCase()).some(perm => getUserPermissions().map(p => p.toLowerCase()).includes(perm));
        });
    }

    static byCaracteristic(characteristic) {
        return PagesRoutes.byPermission().filter(page => page[characteristic]);
    }
}