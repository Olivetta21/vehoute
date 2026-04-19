-- Postgresql
-- versão: 0.0.1
-- em homologação


create table legal_ident_tipo   (
    id serial primary key,
    descricao varchar(100) not null,
    regex varchar(200) not null,
    invalido boolean not null default false
);

create table legal_ident (
    id serial primary key,
    tipo_id integer not null references legal_ident_tipo(id),
    identidade varchar(100) not null
);

create table usuario (
    id serial primary key,
    nome varchar(100) not null,
    login varchar(100),
    senha varchar(100), --sem criptografia por enquanto
    legal_ident_id integer not null references legal_ident(id),
    ativo boolean not null default true,
    email varchar(100),
    telefone varchar(15),
    token varchar(16)
);

create view vw_usuario as
    select * from usuario
    where ativo = true;

create view vw_usuario_completo as
    select
        u.id, u.nome, u.login, u.email, u.telefone,
        li.tipo_id, li.identidade
    from vw_usuario as u
    join legal_ident as li on li.id = u.legal_ident_id;

create table administrador (
    id integer primary key references usuario(id)
);

create table rastreador (
    id serial primary key,
    hardware varchar(100),
    token varchar(100) not null,
    token_publico varchar(100) not null,
    senha varchar(100), --sem criptografia por enquanto
    obs varchar(200),
    status integer not null,
    ativo boolean not null default true,
    dono_id integer not null references usuario(id)
);

create view vw_rastreador as
    select *
    from rastreador
    where ativo = true;

create table usuario_rastreador (
    id serial primary key,
    usuario_id integer not null references usuario(id),
    rastreador_id integer not null references rastreador(id),
    nome varchar(100) not null,
    status integer not null,
    ativo boolean not null default true,
    loc_temporeal boolean not null default true,
    loc_salvos boolean not null default true
);

create view vw_usuario_rastreador as
    select *
    from usuario_rastreador
    where ativo = true;

-- Mostra todos os rastreadores e seus ouvintes
create view vw_vinculo_rastreadores as
    select 
        ur.id as ur_id, ur.nome as ur_nome, ur.status as ur_status, ur.loc_temporeal, ur.loc_salvos,
        r.id as r_id, r.hardware, r.token_publico, r.status as r_status,
		d.id as d_id, d.nome as d_nome,
        coalesce (u.id, d.id) as u_id, u.nome as u_nome, u.email, u.telefone
        from vw_rastreador r
        join vw_usuario d on r.dono_id = d.id
        left join vw_usuario_rastreador ur on r.id = ur.rastreador_id
        left join vw_usuario u on ur.usuario_id = u.id;

select * from vw_vinculo_rastreadores where u_id = 1;

create table localizacao (
    id serial primary key,
    rastreador_id integer not null references rastreador(id),
    lat double precision not null,
    lng double precision not null,
    data timestamp not null,
    invalida boolean not null default false
);

create table intervalo_loc_oculta (
    id serial primary key,
    rastreador_id integer not null references rastreador(id),
    id_inicial integer,
    id_final integer,
    data_inicial timestamp,
    data_final timestamp,
    identificacao varchar(100),
    novos_ouvintes boolean not null default true
);

create table vinc_loc_oculta_usuario_rastreador (
    usuario_rastreador_id integer not null references usuario_rastreador(id),
    intervalo_loc_oculta_id integer not null references intervalo_loc_oculta(id),
    primary key (usuario_rastreador_id, intervalo_loc_oculta_id)
);

-- Mostra quais ouvintes cada intervalo de localizacao oculta tem 
create view vw_vinc_intervalo_loc_oculta_ouvintes as
    select
    vlour.intervalo_loc_oculta_id, ur.usuario_id, ur.rastreador_id
    from vinc_loc_oculta_usuario_rastreador vlour
    join vw_usuario_rastreador ur on vlour.usuario_rastreador_id = ur.id;

-- Mostra o filtro de localizacao oculta de cada ouvinte para qual rastreador
create view vw_intervalo_loc_oculta_ouvintes as
    select
    viloo.intervalo_loc_oculta_id, viloo.rastreador_id, viloo.usuario_id,
	ilo.id_inicial, ilo.id_final, ilo.data_inicial, ilo.data_final
    from vw_vinc_intervalo_loc_oculta_ouvintes viloo
    join intervalo_loc_oculta ilo on viloo.intervalo_loc_oculta_id = ilo.id;

select * from vw_intervalo_loc_oculta_ouvintes;

-- Temporario, usar function posteriormente.
-- Mostra todas as localizacoes de cada ouvinte e se a localizacao esta oculta para ele
create view vw_vinc_localizacao_ouvintes as
    select l.*, ur.usuario_id,
    case when exists (
        select 1 from vw_intervalo_loc_oculta_ouvintes iloo --filtro
        where iloo.rastreador_id = l.rastreador_id
        and iloo.usuario_id = ur.usuario_id
        and (
            (l.data >= iloo.data_inicial and l.data <= iloo.data_final) or
            (l.id >= iloo.id_inicial and l.id <= iloo.id_final)
        )
        limit 1
    ) then true else false end as oculto	
    from localizacao l
    join vw_usuario_rastreador ur on l.rastreador_id = ur.rastreador_id;



-- Permissões de usuario
create table permissao_usuario (
    id serial primary key,
    nome varchar(100) not null
);
create table grupo_usuario (
    id serial primary key,
    nome varchar(100) not null
);
create table vinc_grupo_usuario (
    id serial primary key,
    usuario_id integer not null references usuario(id),
    grupo_id integer not null references grupo_usuario(id)
);
create table vinc_perm_usuario (
    id serial primary key,
    grupo_id integer references grupo_usuario(id),
    usuario_id integer references usuario(id),
    perm_id integer not null references permissao_usuario(id),
    negado boolean not null default false
);
-- Ver permissoes do grupo
create view vw_permissoes_grupo_usuario as
    select gu.id as grupo_id, vpu.perm_id, vpu.negado
    from grupo_usuario gu
    join vinc_perm_usuario vpu on vpu.grupo_id = gu.id;
-- Ver permissoes do usuario
create view vw_permissoes_usuario as
	select usuario_id, perm_id, CASE WHEN COUNT(*) > 1 THEN TRUE ELSE FALSE END AS negado
	from (
		select usuario_id, perm_id, negado from (
		    select vpu.usuario_id, vpu.perm_id, vpu.negado
		    from vinc_perm_usuario vpu
			where usuario_id is not null
		    union all
		    select vgu.usuario_id, vpu.perm_id, vpu.negado
			from vinc_grupo_usuario vgu
			join vinc_perm_usuario vpu on vpu.grupo_id = vgu.grupo_id
		) group by usuario_id, perm_id, negado
	)
	group by usuario_id, perm_id;




-- Permisões de rastreador
create table permissao_rastreador (
    id serial primary key,
    nome varchar(100) not null
);
create table grupo_rastreador (
    id serial primary key,
    nome varchar(100) not null
);
create table vinc_grupo_rastreador (
    id serial primary key,
    rastreador_id integer not null references rastreador(id),
    grupo_id integer not null references grupo_rastreador(id)
);
create table vinc_perm_rastreador (
    id serial primary key,
    grupo_id integer references grupo_rastreador(id),
    rastreador_id integer references rastreador(id),
    perm_id integer not null references permissao_rastreador(id),
    negado boolean not null default false
);
-- Permissoes do grupo rastreador
create view vw_permissoes_grupo_rastreador as
    select gr.id as grupo_id, vpr.perm_id, vpr.negado
    from grupo_rastreador gr
    join vinc_perm_rastreador vpr on vpr.grupo_id = gr.id;
-- Permissoes do rastreador
create view vw_permissoes_rastreador as
    select rastreador_id, perm_id, CASE WHEN COUNT(*) > 1 THEN TRUE ELSE FALSE END AS negado
    from (
        select rastreador_id, perm_id, negado from (
            select vpr.rastreador_id, vpr.perm_id, vpr.negado
            from vinc_perm_rastreador vpr
            where rastreador_id is not null
            union all
            select vgr.rastreador_id, vpr.perm_id, vpr.negado
            from vinc_grupo_rastreador vgr
            join vinc_perm_rastreador vpr on vpr.grupo_id = vgr.grupo_id
        ) group by rastreador_id, perm_id, negado
    )
    group by rastreador_id, perm_id;





insert into legal_ident_tipo (descricao, regex) values ('Geral', '.+');
insert into legal_ident (tipo_id, identidade) values (1, '123456789');

insert into usuario (nome, login, senha, legal_ident_id) values ('Ivan Luiz', 'donoexemplo', '123', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Kelvin Garcete', 'ouvinteexemplo', '123', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Maria Silva', 'mariasilva', 'senha456', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Carlos Pereira', 'carlospereira', 'senha789', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Ana Oliveira', 'anaoliveira', 'senha321', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Kaio Guerreiro', 'kaioguerreiro', 'senha321', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Matheus', 'matheus', 'senha321', 1); --7
insert into usuario (nome, login, senha, legal_ident_id) values ('Guilherme', 'guilherme', 'senha321', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Rafael', 'rafael', 'senha321', 1);
insert into usuario (nome, login, senha, legal_ident_id) values ('Caio Durks', 'caiodurks', 'senha321', 1);

insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id) values ('Rastreador Exemplo', 'token123', 'token_publico123', 'senha123', 'Observações sobre o rastreador', 55, 1);
insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id) values ('Rastreador Alpha', 'tokenAlpha123', 'tokenPublicoAlpha123', 'senhaAlpha123', 'Rastreador de teste', 1, 2);
insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id) values ('Rastreador Beta', 'tokenBeta456', 'tokenPublicoBeta456', 'senhaBeta456', 'Monitoramento em tempo real', 2, 3);
insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id) values ('Rastreador Gamma', 'tokenGamma789', 'tokenPublicoGamma789', 'senhaGamma789', 'Acompanhamento de veículos', 2, 4);
insert into rastreador (hardware, token, token_publico, senha, obs, status, dono_id) values ('Rastreador Charlie', 'tokenCharlie789', 'tokenPublicoCharlie789', 'senhaCharlie789', 'Acompanhamento de veículos', 2, 4);

insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (1, 1, 'Meu Exemplo', 44);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (2, 1, 'Rastreador Exemplo do ivan', 44);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (2, 2, 'Meu Alpha', 12);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (1, 2, 'Rastreador Alpha do kelvin', 12);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (3, 3, 'Meu Beta', 10);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (4, 2, 'Rastreador Alpha Kervins', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (4, 4, 'Meu Gamma', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (6, 1, 'R Exe. Ivan', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (6, 2, 'R alpha. kelvin', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (6, 3, 'R beta. maria', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (1, 3, 'iR beta. maria', 9); --11
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (2, 3, 'kR beta. maria', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (7, 3, 'mR beta. maria', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (8, 3, 'gR beta. maria', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (9, 3, 'rR beta. maria', 9);
insert into usuario_rastreador (usuario_id, rastreador_id, nome, status) values (10, 3, 'cR beta. maria', 9);

insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-10-25 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-11-01 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55100, -46.634000, '2024-11-06 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55100, -46.634000, '2024-11-15 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-11-30 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-12-01 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-12-06 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-12-15 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2024-12-31 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (1, -23.55052, -46.633308, '2025-01-05 10:30:00');--
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-10-25 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-11-01 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55100, -46.634000, '2024-11-06 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55100, -46.634000, '2024-11-15 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-11-30 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-12-01 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-12-06 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-12-15 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2024-12-31 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (2, -23.55052, -46.633308, '2025-01-05 10:30:00');--
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-10-25 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-11-01 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55100, -46.634000, '2024-11-06 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55100, -46.634000, '2024-11-15 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-11-30 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-12-01 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-12-06 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-12-15 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2024-12-31 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (3, -23.55052, -46.633308, '2025-01-05 10:30:00');--
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-10-25 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-11-01 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55100, -46.634000, '2024-11-06 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55100, -46.634000, '2024-11-15 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-11-30 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-12-01 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-12-06 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-12-15 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2024-12-31 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (4, -23.55052, -46.633308, '2025-01-05 10:30:00');--
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-10-25 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-11-01 10:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55100, -46.634000, '2024-11-06 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55100, -46.634000, '2024-11-15 11:00:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-11-30 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-12-01 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-12-06 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-12-15 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2024-12-31 10:30:00');
insert into localizacao (rastreador_id, lat, lng, data) values (5, -23.55052, -46.633308, '2025-01-05 10:30:00');--

insert into intervalo_loc_oculta (rastreador_id, identificacao, id_inicial, id_final) values     (1, 'intA', 2, 3);
insert into intervalo_loc_oculta (rastreador_id, identificacao, data_inicial, data_final) values (2, 'intB', '2024-12-01 00:00:00', '2024-12-31 23:59:59');
insert into intervalo_loc_oculta (rastreador_id, identificacao, data_inicial, data_final) values (2, 'intC', '2024-11-01 00:00:00', '2024-11-15 23:59:59');
insert into intervalo_loc_oculta (rastreador_id, identificacao, data_inicial, data_final) values (3, 'intD', '2024-11-10 00:00:00', '2024-11-20 23:59:59');
insert into intervalo_loc_oculta (rastreador_id, identificacao, data_inicial, data_final) values (3, 'intE', '2024-12-01 00:00:00', '2024-12-15 23:59:59');
insert into intervalo_loc_oculta (rastreador_id, identificacao, data_inicial, data_final) values (3, 'intF', '2024-12-05 00:00:00', '2024-12-10 23:59:59');

insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (8, 1);
insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (6, 2);
insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (9, 3);
insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (11, 4);
insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (13, 5);
insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (14, 5);
insert into vinc_loc_oculta_usuario_rastreador (usuario_rastreador_id, intervalo_loc_oculta_id) values (16, 6);


insert into permissao_usuario (nome) values ('Login');
insert into permissao_usuario (nome) values ('Ver Mapa');
insert into permissao_usuario (nome) values ('Registrar Rastreador');
insert into permissao_usuario (nome) values ('Modificar Rastreador');
insert into permissao_usuario (nome) values ('Modificar Perfil');
insert into permissao_usuario (nome) values ('Transferir Posse');
insert into permissao_usuario (nome) values ('Gerenciar ouvintes');
insert into permissao_usuario (nome) values ('Rastreio Salvo');
insert into permissao_usuario (nome) values ('Rastreio T.R.');
insert into permissao_usuario (nome) values ('Quer Propostas Rastreio');
insert into permissao_usuario (nome) values ('Proposta Rastreio');
insert into permissao_usuario (nome) values ('Intervalo Oculto');
insert into permissao_usuario (nome) values ('Desativar Rastreador');
insert into permissao_rastreador (nome) values ('Conexão');
insert into permissao_rastreador (nome) values ('Enviar Localização');
insert into permissao_rastreador (nome) values ('Resgistrável');
insert into permissao_rastreador (nome) values ('Rastreável R.T');
insert into permissao_rastreador (nome) values ('Rastreável');
insert into permissao_rastreador (nome) values ('Ouvintes');
insert into grupo_usuario (nome) values ('Grupo Usuario 1');
insert into grupo_usuario (nome) values ('Grupo Usuario 2');
insert into grupo_rastreador (nome) values ('Grupo Rastreador A');
insert into grupo_rastreador (nome) values ('Grupo Rastreador B');
insert into vinc_grupo_usuario (usuario_id, grupo_id) values (1, 1); -- usuario 1 no grupo 1
insert into vinc_grupo_usuario (usuario_id, grupo_id) values (1, 2); -- usuario 1 no grupo 2
insert into vinc_grupo_usuario (usuario_id, grupo_id) values (3, 1); -- usuario 3 no grupo 1
insert into vinc_grupo_usuario (usuario_id, grupo_id) values (3, 2); -- usuario 3 no grupo 2
insert into vinc_grupo_usuario (usuario_id, grupo_id) values (5, 1); -- usuario 5 no grupo 1
insert into vinc_grupo_rastreador (rastreador_id, grupo_id) values (1, 1); -- rastreador 1 no grupo 1
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (1, 1, false);
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (1, 2, false);
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (1, 3, false);
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (1, 4, false);
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (2, 1, false);
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (2, 2, true);
insert into vinc_perm_usuario (grupo_id, perm_id, negado) values (2, 4, true);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (1, 5, false); -- usuario 1 perm de 1 a 4 do grupo mais a 5 individual
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (2, 3, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (3, 4, true); -- nega permissão 4 para usuario 3
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (4, 5, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (5, 1, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (5, 2, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (5, 3, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (5, 4, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (5, 5, false);
insert into vinc_perm_usuario (usuario_id, perm_id, negado) values (5, 6, false);
insert into vinc_perm_rastreador (grupo_id, perm_id, negado) values (1, 1, false);
