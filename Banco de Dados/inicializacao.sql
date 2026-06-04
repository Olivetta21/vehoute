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

create table usuario_cadastrando (
    id serial primary key,
    nome varchar(100) not null,
    email varchar(100) unique not null,
    verificado boolean not null default false,
    otp varchar(256),
    expires_at timestamp with time zone not null
);

create table usuario (
    id serial primary key,
    nome varchar(100) not null,
    login varchar(100) unique not null,
    senha varchar(100) not null, --sem criptografia por enquanto
    legal_ident_id integer not null references legal_ident(id),
    ativo boolean not null default true,
    email varchar(100) unique not null,
    telefone varchar(15)
);

create table usuario_access_token(
    token varchar(256) not null primary key,
    usuario_id integer not null references usuario(id),
    created_at timestamp with time zone default now(),
    expires_at timestamp with time zone not null
);

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
    status integer not null,--
    ativo boolean not null default true,
    dono_id integer not null references usuario(id)
);

create table usuario_rastreador (
    id serial primary key,
    usuario_id integer not null references usuario(id),
    rastreador_id integer not null references rastreador(id),
    nome varchar(100) not null,
    status integer not null,--
    ativo boolean not null default true,
    loc_temporeal boolean not null default true,
    loc_salvos boolean not null default true
);

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

create table logs (
    id serial primary key,
    tipo varchar(50) not null,
    descricao text not null
)

create table auditoria (
    id serial primary key,
    usuario_id integer not null references usuario(id),
    tabela varchar(100) not null,
    acao varchar(100) not null,
    antes text,
    depois text,
    data timestamp with time zone default now()
);

create function inserirUsuarioCadastrando(
    p_nome varchar, p_email varchar, p_otp varchar
)
RETURNS TABLE (
    sucesso BOOLEAN,
    mensagem TEXT,
    detalhes TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    var_usuario_cadastrando_id INT;
BEGIN
    -- Verifica cadastro prévio
    PERFORM 1 
    FROM usuario 
    WHERE email = p_email;
    
    IF FOUND THEN
        RETURN QUERY SELECT FALSE, 'Este email já está cadastrado', p_email::TEXT;
        RETURN;
    END IF;

    -- Insere ou atualiza cadastro prévio
    INSERT INTO usuario_cadastrando (nome, email, otp, expires_at)
    VALUES (p_nome, p_email, p_otp, now() + interval '4 minutes')
    ON CONFLICT (email) DO UPDATE SET nome = p_nome, otp = p_otp, expires_at = now() + interval '4 minutes'
    RETURNING id INTO var_usuario_cadastrando_id;

    IF var_usuario_cadastrando_id IS NOT NULL THEN
        RETURN QUERY SELECT true, 'Cadastro previo realizado com sucesso', p_email::TEXT;
    ELSE
        RETURN QUERY SELECT false, 'Falha ao cadastrar usuário', p_email::TEXT;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RETURN QUERY SELECT false, 'Erro ao cadastrar usuário', SQLERRM;
END;
$$;


CREATE FUNCTION finalizarCadastroUsuario(
    p_nome varchar, p_email varchar, p_telefone varchar, p_login varchar, p_senha varchar, p_otp varchar
)
RETURNS TABLE (
    sucesso BOOLEAN,
    mensagem TEXT,
    detalhes TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    var_usuario_id INT;
    var_ja_cadastrado_info TEXT;
    var_ja_cadastrado_tipo TEXT;
BEGIN
    -- Verifica cadastro prévio
    PERFORM 1 
    FROM usuario_cadastrando 
    WHERE otp = p_otp 
      AND nome = p_nome 
      AND email = p_email
      AND verificado = true;

    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 'OTP inválido ou cadastro não encontrado', p_otp::TEXT;
        RETURN;
    END IF;

    -- Verifica se o email ou login já está cadastrado
    select (email = p_email)::TEXT, (login = p_login)::TEXT
    FROM usuario
    WHERE email = p_email OR login = p_login limit 1 into var_ja_cadastrado_info, var_ja_cadastrado_tipo;
    -- Reutiliza variaveis
    IF var_ja_cadastrado_tipo = 'true' THEN
        var_ja_cadastrado_info := p_login;
        var_ja_cadastrado_tipo := 'Login';
    ELSIF var_ja_cadastrado_info = 'true' THEN
        var_ja_cadastrado_info := p_email;
        var_ja_cadastrado_tipo := 'Email';
    END IF;
    -- Se já cadastrado, retorna erro com o dado
    IF var_ja_cadastrado_info is not null THEN
        RETURN QUERY SELECT FALSE, concat(var_ja_cadastrado_tipo, ' já está sendo utilizado'), var_ja_cadastrado_info::TEXT;
        RETURN;
    END IF;

    -- Insere usuário
    INSERT INTO usuario (legal_ident_id, nome, email, telefone, login, senha)
    VALUES (1, p_nome, p_email, p_telefone, p_login, p_senha)
    RETURNING id INTO var_usuario_id;

    IF var_usuario_id IS NULL THEN
        RETURN QUERY SELECT FALSE, 'Falha ao inserir usuário', p_nome::TEXT;
        RETURN;
    END IF;

    -- Deleta cadastro prévio
    DELETE FROM usuario_cadastrando
    WHERE otp = p_otp 
      AND nome = p_nome 
      AND email = p_email
      AND verificado = true;

    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 'Falha ao remover cadastro prévio', p_otp::TEXT;
        RETURN;
    END IF;

    -- Insere permissão
    INSERT INTO vinc_perm_usuario (usuario_id, perm_id, negado)
    VALUES (var_usuario_id, 1, false);

    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 'Falha ao inserir permissão', p_email::TEXT;
        RETURN;
    END IF;

    RETURN QUERY SELECT TRUE, 'OK', ''::TEXT;
EXCEPTION
    WHEN unique_violation THEN
        RETURN QUERY SELECT FALSE, 'Registro duplicado', SQLERRM;

    WHEN foreign_key_violation THEN
        RETURN QUERY SELECT FALSE, 'Erro de integridade referencial', SQLERRM;

    WHEN OTHERS THEN
        RETURN QUERY SELECT FALSE, 'Erro inesperado', SQLERRM;
END;
$$;


--Pegar o usuario por um token de acesso valido
create function getUsuarioByToken(
    var_token varchar
) returns table (
    id integer, nome varchar
) as $$
    select u.id, u.nome
    from usuario u
    where u.id = (
        select usuario_id from usuario_access_token where token = var_token and expires_at > now() limit 1
    )
    and u.ativo = true;
$$ language sql;


--Pegar todos os rastreadores de um usuario, ignorar os inativos
create function getRastreadoresDoUsuario(
    var_usuarios_ids integer[]
) returns table (
    ur_id integer, ur_usuario_id integer, ur_nome varchar, ur_status integer, ur_loc_temporeal boolean, ur_loc_salvos boolean,
    r_id integer, r_hardware varchar, r_token_publico varchar, r_status integer, r_dono_id integer
) as $$
    select ur.id as ur_id, ur.usuario_id as ur_usuario_id, ur.nome as ur_nome, ur.status as ur_status, ur.loc_temporeal as ur_loc_temporeal, ur.loc_salvos as ur_loc_salvos,
    r.id as r_id, r.hardware as r_hardware, r.token_publico as r_token_publico, r.status as r_status, r.dono_id as r_dono_id
    from rastreador r
    join usuario_rastreador ur on ur.rastreador_id = r.id
    where ur.usuario_id = any(var_usuarios_ids) and ur.ativo = true and r.ativo = true;
$$ language sql;

--Pegar todos os ouvintes de um rastreador, ignorar os inativos
create function getOuvintesDoRastreador(
    var_rastreadores_ids integer[]
) returns table (
    ur_id integer, ur_loc_temporeal boolean, ur_loc_salvos boolean,
    u_id integer, u_nome varchar, u_email varchar, u_telefone varchar
) as $$
    select ur.id as ur_id, ur.loc_temporeal as ur_loc_temporeal, ur.loc_salvos as ur_loc_salvos,
    u.id as u_id, u.nome as u_nome, u.email as u_email, u.telefone as u_telefone
    from usuario_rastreador ur
    join usuario u on u.id = ur.usuario_id
    where ur.rastreador_id = any(var_rastreadores_ids) and u.ativo = true;
$$ language sql;

--Pegar todas localizações ocultas de um rastreador
create function getLocOcultaDoRastreador(
    var_rastreadores_ids integer[]
) returns table (
    id integer, 
    id_inicial integer, id_final integer, data_inicial timestamp, data_final timestamp,
    rastreador_id integer, identificacao varchar, novos_ouvintes boolean
) as $$
    select id, id_inicial, id_final, data_inicial, data_final, rastreador_id, identificacao, novos_ouvintes
    from intervalo_loc_oculta
    where rastreador_id = any(var_rastreadores_ids);
$$ language sql;

--Pegar todos os usuarios de uma localizacao oculta
create function getUsuariosDaLocOculta(
    var_intervalo_loc_oculta_ids integer[]
) returns table (
    ilo_id integer, u_id integer, u_nome varchar
) as $$
    select vlour.intervalo_loc_oculta_id as ilo_id, u.id as u_id, u.nome as u_nome
    from vinc_loc_oculta_usuario_rastreador vlour
    join usuario_rastreador ur on ur.id = vlour.usuario_rastreador_id
    join usuario u on u.id = ur.usuario_id
    where vlour.intervalo_loc_oculta_id = any(var_intervalo_loc_oculta_ids) and ur.ativo = true and u.ativo = true;
$$ language sql;


--Pegar localizações do rastreador para um ouvinte, marcar as localizações ocultas para ele
create function getLocDoRastreadorParaOuvinte(
    var_rastreador_id integer,
    var_usuario_id integer
) returns table (
    l_id integer, l_rastreador_id integer, l_lat double precision, l_lng double precision, l_data timestamp, is_oculto boolean
) as $$
    select l.id as l_id, l.rastreador_id as l_rastreador_id, l.lat as l_lat, l.lng as l_lng, l.data as l_data,
    case when exists (
        select 1 from vinc_loc_oculta_usuario_rastreador vlour --filtro
        join intervalo_loc_oculta ilo on vlour.intervalo_loc_oculta_id = ilo.id
        where ilo.rastreador_id = var_rastreador_id
        and vlour.usuario_rastreador_id = (
            select id from usuario_rastreador where usuario_id = var_usuario_id and rastreador_id = var_rastreador_id limit 1
        )
        and (
            (l.data >= ilo.data_inicial and l.data <= ilo.data_final) or
            (l.id >= ilo.id_inicial and l.id <= ilo.id_final)
        )
        limit 1
    ) then true else false end as is_oculto	
    from localizacao l
    where l.rastreador_id = var_rastreador_id and l.invalida = false;
$$ language sql;



--Mostra todas as permissões do usuario, eliminando as duplicadas
create function getPermissoesDoUsuario(
    var_usuarios_ids integer[]
) returns table (
    usuario_id integer, perm_id integer, negado boolean
) as $$
	select usuario_id, perm_id, CASE WHEN COUNT(*) > 1 THEN TRUE ELSE FALSE END AS negado
	from (
		select usuario_id, perm_id, negado from (
		    select vpu.usuario_id, vpu.perm_id, vpu.negado
		    from vinc_perm_usuario vpu
			where usuario_id = any(var_usuarios_ids)
		    union all
		    select vgu.usuario_id, vpu.perm_id, vpu.negado
			from vinc_grupo_usuario vgu
			join vinc_perm_usuario vpu on vpu.grupo_id = vgu.grupo_id
            where vgu.usuario_id = any(var_usuarios_ids)
		) group by usuario_id, perm_id, negado
	)
	group by usuario_id, perm_id;
$$ language sql;


--Mostra todas as permissões do grupo de Usuario
create function getPermissoesDoGrupoUsuario(
    var_grupos_ids integer[]
) returns table (
    gu_id integer, perm_id integer, negado boolean
) as $$
    select gu.id as gu_id, vpu.perm_id as perm_id, vpu.negado as negado
    from grupo_usuario gu
    join vinc_perm_usuario vpu on vpu.grupo_id = gu.id
    where gu.id = any(var_grupos_ids);
$$ language sql;



--Mostra todas as permissões do rastreador, eliminando as duplicadas
create function getPermissoesDoRastreador(
    var_rastreadores_ids integer[]
) returns table (
    rastreador_id integer, perm_id integer, negado boolean
) as $$
    select rastreador_id, perm_id, CASE WHEN COUNT(*) > 1 THEN TRUE ELSE FALSE END AS negado
    from (
        select rastreador_id, perm_id, negado from (
            select rastreador_id, perm_id, negado
            from vinc_perm_rastreador
            where rastreador_id = any(var_rastreadores_ids)
            union all
            select vgr.rastreador_id, vpr.perm_id, vpr.negado
            from vinc_grupo_rastreador vgr
            join vinc_perm_rastreador vpr on vpr.grupo_id = vgr.grupo_id
            where vgr.rastreador_id = any(var_rastreadores_ids)
        ) group by rastreador_id, perm_id, negado
    )
    group by rastreador_id, perm_id;
$$ language sql;


--Mostra todas as permissões do grupo de Rastreador
create function getPermissoesDoGrupoRastreador(
    var_grupos_ids integer[]
) returns table (
    grupo_id integer, perm_id integer, negado boolean
) as $$
    select gr.id as grupo_id, vpr.perm_id as perm_id, vpr.negado as negado
    from grupo_rastreador gr
    join vinc_perm_rastreador vpr on vpr.grupo_id = gr.id
    where gr.id = any(var_grupos_ids);
$$ language sql;



create view vw_usuarios_do_sistema as
	select u.id, adm.id is not null as adm, u.nome, u.ativo, u.email, u.telefone, u.legal_ident_id,
		lit.id as tipo_ident, li.identidade, lit.descricao as descricao_ident,
		coalesce(qpr.count,0) as qnt_posse_rastr, coalesce(oqr.count,0) as ouvinte_qnt_rastr
	from usuario u
	left join administrador adm on adm.id = u.id
	join legal_ident li on li.id = u.legal_ident_id
	join legal_ident_tipo lit on lit.id = li.tipo_id
	left join (select dono_id, count(id) from rastreador group by dono_id) qpr on qpr.dono_id = u.id
	left join (select ur.usuario_id, count(ur.id)
			from usuario_rastreador ur
			join rastreador r on r.id = ur.rastreador_id and r.dono_id <> ur.usuario_id
			group by ur.usuario_id) oqr on oqr.usuario_id = u.id;


create view vw_rastreadores_do_sistema as
select r.id, r.hardware, r.token, r.token_publico, r.obs, r.status, r.ativo,
	u.id as u_id, u.nome,
	coalesce(qnto.count, 0) as qnto
	from rastreador r
	left join usuario u on u.id = r.dono_id
	left join (select rastreador_id, count(rastreador_id) from usuario_rastreador group by rastreador_id) qnto on qnto.rastreador_id = r.id;
    

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

insert into usuario_access_token (token, usuario_id, expires_at) values ('tk1', 1, now() + interval '10 years');
insert into usuario_access_token (token, usuario_id, expires_at) values ('tk2', 2, now() + interval '5 seconds');

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
