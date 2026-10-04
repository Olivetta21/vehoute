
export default [
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
        sidebarIcon: '/api/imagens/icon_blackadminhome64.png'
    },

    // Subpáginas do Administrativo
    { pageName: 'Usuários do Sistema', name: 'sysusers', path: 'usuariosdosistema',
        admin_cardIcon: '/api/imagens/card_users.png',
        sidebarIcon: '/api/imagens/icon_blackusers64.png'
    },
    { pageName: 'Rastreadores do Sistema', name: 'systrackers', path: 'rastreadoresdosistema',
        admin_cardIcon: '/api/imagens/card_systracker.png',
        sidebarIcon: '/api/imagens/icon_blacksystrackers64.png'
    },
    { pageName: 'Permissões de Usuário', name: 'userperms', path: 'permissoesdeusuario',
        admin_cardIcon: '/api/imagens/card_userspermissions.png',
        sidebarIcon: '/api/imagens/icon_blackuserperms64.png'
    },
    { pageName: 'Permissões de Rastreador', name: 'trackerperms', path: 'permissoesderastreador',
        admin_cardIcon: '/api/imagens/card_trackerpermissions.png',
        sidebarIcon: '/api/imagens/icon_blacktrackerperms64.png'
    },
    { pageName: 'Auditoria', name: 'audit', path: 'auditoria',
        admin_cardIcon: '/api/imagens/card_sysaudit.png',
        sidebarIcon: '/api/imagens/icon_blackaudit64.png'
    },
    { pageName: 'Logs', name: 'logs', path: 'logs',
        admin_cardIcon: '/api/imagens/card_syslogs.png',
        sidebarIcon: '/api/imagens/icon_blacklogs64.png'
    }
]