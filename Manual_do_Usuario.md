# 📍 MANUAL DO USUÁRIO — VEHOUTE
### Sistema Integrado de Rastreamento GPS em Tempo Real

---

## 📑 SUMÁRIO

* [1. Introdução](#1-introdução)
* [2. Funcionalidades](#2-funcionalidades)
* [3. Benefícios Esperados](#3-benefícios-esperados)
* [4. Como Acessar o Sistema?](#4-como-acessar-o-sistema)
* [5. Após Realizar o Login (Usuário e Administrador)](#5-após-realizar-o-login-usuário-e-administrador)
  * [5.1 Página Inicial](#51-página-inicial)
  * [5.2 Mapa](#52-mapa)
  * [5.3 Seus Rastreadores](#53-seus-rastreadores)
    * [5.3.1 Ouvintes do Rastreador](#531-ouvintes-do-rastreador)
    * [5.3.2 Localização Oculta do Rastreador](#532-localização-oculta-do-rastreador)
  * [5.4 Perfil](#54-perfil)
  * [5.5 Configurações](#55-configurações)
  * [5.6 Notificações](#56-notificações)
  * [5.7 Administrativo](#57-administrativo)
    * [5.7.1 Usuários do Sistema](#571-usuários-do-sistema)
    * [5.7.2 Rastreadores do Sistema](#572-rastreadores-do-sistema)
    * [5.7.3 Usuários e Permissões](#573-usuários-e-permissões)
      * [5.7.3.1 Grupo de Usuários](#5731-grupo-de-usuários)
      * [5.7.3.2 Permissões de Usuário](#5732-permissões-de-usuário)
    * [5.7.4 Rastreadores e Permissões](#574-rastreadores-e-permissões)
      * [5.7.4.1 Grupo de Rastreadores](#5741-grupo-de-rastreadores)
      * [5.7.4.2 Permissões de Rastreador](#5742-permissões-de-rastreador)
    * [5.7.5 Auditoria](#575-auditoria)
    * [5.7.6 Logs](#576-logs)
* [6. Logout do Sistema](#6-logout-do-sistema)

---

## 1. Introdução

Com a crescente necessidade de monitoramento remoto, segurança patrimonial e acompanhamento preciso de deslocamentos em tempo real, torna-se indispensável o uso de soluções integradas que conectem dispositivos embarcados a plataformas web acessíveis e confiáveis. 

Nesse contexto, foi desenvolvido o **Vehoute**, um sistema completo de rastreamento de localização via GPS em tempo real. A solução opera por meio de dispositivos rastreadores embarcados (baseados em microcontrolador Arduino com módulos GPS e GSM) que enviam suas coordenadas a um servidor-ponte responsável por armazenar os dados e transmiti-los instantaneamente para a interface web utilizada pelos usuários autorizados.

> **Objetivo do Manual:** Orientar os usuários na utilização das funcionalidades do software Vehoute, facilitando o acesso e o entendimento do sistema por meio da descrição detalhada de cada atividade e seus respectivos passos.

---

## 2. Funcionalidades

### 👤 Funcionalidades do Usuário (Dono e Ouvinte)
* **Acesso e Autenticação:** Realizar autocadastro com confirmação por código de verificação (OTP) enviado por e-mail e autenticar-se com validação de sessão por token de acesso.
* **Gestão de Rastreadores:** Registrar novos rastreadores informando token e senha do dispositivo, além de editar informações (nome, token público, senha e status do equipamento).
* **Monitoramento e Mapas:** Visualizar rastreadores no mapa (*OpenStreetMap* ou *Google Maps API*), acompanhando o histórico de posições salvas e o deslocamento em tempo real.
* **Navegação e Filtros:** Navegar pelas coordenadas registradas (anterior, próxima e última localização) e filtrar trajetos por intervalo de datas.
* **Compartilhamento (Ouvintes):** Convidar outros usuários para acompanhar um rastreador como ouvintes (proposta de inclusão), bem como aceitar ou recusar propostas de inclusão e de transferência de posse.
* **Controle de Acesso:** Gerenciar ouvintes vinculados, podendo pausar ou retomar o rastreio e habilitar/desabilitar o acesso ao tempo real ou ao histórico salvo individualmente.
* **Transferência e Vínculos:** Transferir a posse de um rastreador para outro ouvinte vinculado ou remover o próprio vínculo com um equipamento.
* **Privacidade:** Ocultar intervalos de localização (por ID ou por data) e definir quais ouvintes são afetados pela restrição.
* **Conta:** Atualizar dados do perfil pessoal (nome, senha, e-mail, telefone e documento de identidade) e ajustar configurações individuais.

### 🛠️ Funcionalidades Administrativas
* **Gestão de Contas:** Criar novos usuários no sistema, ativar/desativar cadastros e conceder ou remover privilégios de administrador.
* **Gestão de Hardware:** Cadastrar, configurar (status, hardware, tokens, senha, observações e proprietário) e ativar/desativar dispositivos rastreadores.
* **Grupos e Permissões:** Criar e gerenciar grupos de usuários e grupos de rastreadores, concedendo ou negando permissões específicas a usuários, dispositivos e grupos.
* **Monitoramento Técnico:** Consultar registros de auditoria (ações realizadas, valores anteriores e posteriores, data, usuário e status) com filtros avançados, além de logs de erros e eventos do sistema.

---

## 3. Benefícios Esperados

| Benefício | Descrição |
| :--- | :--- |
| **⏱️ Acompanhamento em tempo real** | Visualização quase instantânea dos deslocamentos no mapa, com latência máxima prevista de até **10 segundos** entre o envio pelo dispositivo e a exibição na tela. |
| **🎛️ Compartilhamento controlado** | Autonomia para o proprietário decidir quem pode visualizar seu rastreador, escolhendo entre acesso em tempo real ou apenas histórico, com pausa ou revogação imediata. |
| **🕵️ Privacidade de trajetos** | Possibilidade de ocultar intervalos específicos de localização (por período ou faixa de registros) para ouvintes selecionados. |
| **💾 Confiabilidade e integridade** | Armazenamento em fila (*FIFO*) no dispositivo embarcado em caso de perda de sinal GSM, garantindo que nenhuma localização seja perdida e tudo seja enviado em lote após a reconexão. |
| **🔒 Segurança da informação** | Criptografia de senhas, autenticação baseada em tokens com expiração de sessão e mecanismo *anti-replay* que bloqueia pacotes duplicados ou adulterados. |
| **📋 Rastreabilidade e controle** | Gestão granular de permissões individuais ou por grupo, aliada a trilhas de auditoria e logs que identificam falhas e ações executadas no sistema. |

---

## 4. Como Acessar o Sistema?

Para acessar o Vehoute, abra um navegador web (como Google Chrome, Microsoft Edge ou Mozilla Firefox) e acesse o endereço eletrônico (URL) do servidor:

🔗 **URL de Acesso:** [https://vehoute.olivetta.com.br](https://vehoute.olivetta.com.br)

Ao carregar a página sem uma sessão ativa, o sistema direciona automaticamente para a **Tela de Login**.

> 🖼️ *[Inserir imagem da tela de Login]*

### Obtenção de Conta e Primeiro Acesso

1. **Autocadastro de Usuário:** 
   * Na tela de Login, clique no botão **"Fazer cadastro"**.
   * Na primeira etapa, informe seu **Nome** (mínimo de dois nomes, contendo apenas letras) e **E-mail**, clicando em **"Enviar email"**.
   * O sistema enviará um código de verificação (OTP) de **8 caracteres alfanuméricos** com validade de **4 minutos**.
   * Na tela **"Finalizar Cadastro"**, insira o código recebido, clique em **"Verificar"** e preencha os campos obrigatórios: *Telefone*, *Login* (mínimo de 8 caracteres), *Tipo de Identidade*, *Identidade*, *Senha* e *Repita a senha*, finalizando em **"Cadastrar-se"**.
2. **Conta Criada pelo Administrador:** 
   * Administradores podem criar contas diretamente pelo módulo administrativo **"Usuários do Sistema"**, onde o sistema gera automaticamente um identificador de login (no padrão `usr` seguido de 8 caracteres) e as credenciais iniciais.
3. **Permissões de Acesso:** 
   * Ao concluir o autocadastro, a conta recebe inicialmente apenas a permissão básica de **Login**. Para utilizar o mapa, registrar rastreadores e acessar os demais recursos, um administrador deve vincular as permissões correspondentes ao usuário ou incluí-lo em um grupo autorizado.

> 🖼️ *[Inserir imagem da tela de Cadastro / Finalizar Cadastro]*

### Requisitos da Senha
Para garantir a segurança da conta, a senha cadastrada deve conter obrigatoriamente:
* No mínimo **8 caracteres**;
* Pelo menos **1 letra maiúscula**;
* Pelo menos **1 letra minúscula**;
* Pelo menos **1 número**;
* Pelo menos **1 caractere especial** dentre os símbolos permitidos: `# @ $ ! % * ? &`.

> ⚠️ **Atenção:** Caso a confirmação de senha não seja idêntica, o sistema exibirá o alerta *"As senhas não coincidem"*.

### Autenticação e Mensagens de Erro
Na tela inicial, preencha os campos **Login** e **Senha** e clique em **"Entrar"**. A sessão permanece ativa por **1 hora**; após esse período, o acesso expira automaticamente e o sistema solicita uma nova autenticação com o aviso: *"Sessão expirada! Refaça o login."*

Caso ocorra alguma falha durante a tentativa de acesso, o sistema exibirá uma notificação temporária (*toast*) no canto da tela informando o motivo:

* **`Usuario não encontrado`**: Exibida quando o login informado não existe ou a senha digitada está incorreta.
* **`Erro ao recuperar permissões`**: Exibida quando o usuário possui cadastro, mas não tem nenhuma permissão ativa vinculada ao seu perfil.
* **`<Tela> Você não tem permissão! Permissão exigida: <nome>`**: Exibida caso o usuário tente acessar uma operação não liberada para o seu perfil.

---

## 5. Após Realizar o Login (Usuário e Administrador)

Após efetuar o login com sucesso, o usuário visualiza a interface principal do sistema, composta por:
* **Menu lateral esquerdo retrátil:** Pode ser expandido ou recolhido por meio do botão de seta no topo, alternando entre a exibição completa com rótulos e a barra compacta apenas com ícones.
* **Área de conteúdo principal à direita:** Exibe as funcionalidades do módulo selecionado.

No topo do menu lateral encontra-se a seção **"Acesso rápido"**, que disponibiliza o botão para alternar o modo de tela cheia, seguida pelos atalhos de navegação para todos os módulos do sistema e o botão de logoff. No cabeçalho das telas internas, conforme a funcionalidade acessada, ficam disponíveis o botão de retorno (`←`), a barra de pesquisa por texto e o botão de adição (`+`).

> 🖼️ *[Inserir imagem da tela principal após o login destacando o menu lateral]*

---

### 5.1 Página Inicial

A **Página Inicial** funciona como um painel central de navegação rápida, apresentando cartões de atalho direto para os principais módulos do sistema: **Mapa**, **Seus Rastreadores**, **Perfil**, **Configurações**, **Notificações** e **Administrativo**.

> 🖼️ *[Inserir imagem da tela Página Inicial]*

---

### 5.2 Mapa

O módulo **Mapa** exibe a representação geográfica (via *OpenStreetMap* ou *Google Maps API*) com os pontos e trajetos registrados pelo rastreador selecionado. Na barra superior de controle, o usuário conta com:

* **Seletor de rastreador:** Lista os dispositivos vinculados (identificando com um indicador verde 🟢 aqueles com acompanhamento em tempo real ativo).
* **Seletor de localização:** Permite escolher um ponto específico pela data e identificador do registro.
* **Controles de navegação:** Botões para avançar para a próxima posição (`>`), retornar à posição anterior (`<`) ou saltar diretamente para a última localização registrada (`O`).
* **Filtro de período:** Campos **"de"** e **"a"** para delimitar a visualização de trajetos em um intervalo específico de datas.

> 💡 **Dica:** Ao abrir a tela, o sistema oferece a opção de ativar o modo de **tela cheia** para melhor visualização do trajeto. Caso o usuário não possua permissão de tempo real para o dispositivo, apenas as localizações já salvas serão carregadas.

> 🖼️ *[Inserir imagem da tela Mapa]*

---

### 5.3 Seus Rastreadores

A tela **Seus Rastreadores** lista em formato de cartões todos os dispositivos vinculados à conta do usuário (seja como proprietário ou como ouvinte), contando com barra de busca por nome e o botão `+` para vincular um novo equipamento.

* **Adicionar rastreador:** Ao clicar em `+`, abre-se uma janela modal onde o usuário informa o **Token** e a **Senha** do dispositivo e clica em **"Verificar"**. Sendo válido, basta atribuir um **Nome** e clicar em **"Criar rastreador"**.
* **Cartão do rastreador:** Exibe o número do vínculo, nome, situação do vínculo (*Operante*, *Rastreador pausado*, *Aguardando resposta do dono*, *Proposta de Inclusão Pendente* ou *Proposta para ser o dono*), situação do equipamento (*Operante*, *Localização desativada*, *Em transferência* ou *Desativado*), proprietário, token público e senha oculta.
* **Ações no cartão:** 
  * O ícone de lápis (`✏️`) permite editar nome, status, token público e senha.
  * Ao expandir o cartão (`+/−`), ficam disponíveis os botões **"Ouvintes"**, **"Localização oculta"** e **"Rastrear"** (que abre o mapa diretamente no dispositivo), além da opção **"Excluir vínculo"** e dos botões de aceite (`✓`) ou recusa (`✕`) para propostas pendentes.

> 🖼️ *[Inserir imagem da tela Seus Rastreadores]*

#### 5.3.1 Ouvintes do Rastreador

Acessada pelo botão **"Ouvintes"** dentro do cartão de um rastreador próprio, esta tela permite controlar quem tem acesso às localizações do equipamento:

* **Convidar ouvinte (`+`):** Abre o formulário para informar o nome de identificação e o ID do usuário de destino, enviando uma proposta de inclusão.
* **Controle individual:** Para cada ouvinte listado, o dono pode:
  * Ativar ou desativar separadamente as chaves **"Loc. Tempo Real"** e **"Loc. Salvos"**;
  * Pausar ou retomar o compartilhamento (`⏸️` / `▶️`);
  * Aceitar ou recusar solicitações pendentes (`✅` / `❌`);
  * Remover o ouvinte (`🗑️`);
  * Iniciar uma transferência de posse (`👑`).

> 🖼️ *[Inserir imagem da tela Ouvintes do Rastreador]*

#### 5.3.2 Localização Oculta do Rastreador

Acessada pelo botão **"Localização oculta"**, permite criar regras de privacidade para ocultar trechos específicos do rastreio aos ouvintes:

* **Criar filtro (`+`):** Permite definir uma identificação para o bloqueio, bem como o **ID inicial/final** e/ou a **data inicial/final** do intervalo a ser ocultado.
* **Gerenciamento:** Cada regra pode ser editada, excluída (`X`) ou configurada pela chave **"Aplica a Novos Ouvintes?"**. O botão **"Ouvintes (n)"** permite selecionar exatamente quais ouvintes terão aquele trecho ocultado.

> 🖼️ *[Inserir imagem da tela Localização Oculta do Rastreador]*

---

### 5.4 Perfil

A tela **Perfil** apresenta o id do usuario, o resumo de vínculos da conta (total de rastreadores, rastreadores registrados como dono e quantidade em que atua como ouvinte) e permite editar os dados pessoais (*Nome*, *Senha*, *Tipo e Número de Identidade*, *E-mail* e *Telefone*). 

O campo **Login** é exibido apenas para leitura. Ao modificar qualquer informação, os botões **"Salvar"** e **"Cancelar"** são habilitados.

> 🖼️ *[Inserir imagem da tela Perfil]*

---

### 5.5 Configurações

O módulo **Configurações** reúne as preferências pessoais do usuário organizadas por categoria, com barra de pesquisa por nome. Opções booleanas são exibidas como chaves de alternância (ativado/desativado) e parâmetros personalizados como campos de texto, habilitando os botões **"Salvar Alterações"** e **"Cancelar"** sempre que houver modificações.

> 🖼️ *[Inserir imagem da tela Configurações]*

---

### 5.6 Notificações

A tela **Notificações** centraliza a área destinada aos avisos e alertas relacionados às interações do usuário no sistema, complementando os alertas instantâneos (*toasts*) exibidos durante o uso.

> 🖼️ *[Inserir imagem da tela Notificações]*

---

### 5.7 Administrativo

O módulo **Administrativo** apresenta um painel com cartões de atalho para as ferramentas de gestão do sistema: **Usuários do Sistema**, **Rastreadores do Sistema**, **Usuários e Permissões**, **Rastreadores e Permissões**, **Auditoria** e **Logs**.

> 🖼️ *[Inserir imagem da tela Administrativo]*

#### 5.7.1 Usuários do Sistema
Exibe todos os usuários cadastrados em cartões informativos (com ID, indicador de administrador `👑`, status ativo/inativo `🟢`/`🔴`, nome, quantidade de rastreadores próprios e como ouvinte, e-mail, telefone e identidade). O administrador pode buscar usuários pelo nome, cadastrar novas contas pelo botão `+`, ativar/desativar cadastros e conceder ou remover o papel de administrador.

> 🖼️ *[Inserir imagem da tela Usuários do Sistema]*

#### 5.7.2 Rastreadores do Sistema
Lista todos os dispositivos cadastrados no sistema, exibindo status operacional (*Normal*, *Loc. desligado*, *Transferência* ou *Desativado*), hardware, proprietário vinculado e total de vínculos. Pelo botão `+`, o administrador registra novos equipamentos definindo status, hardware, tokens público e privado, senha, observações e dono. Cada cartão permite visualizar detalhes técnicos em **"Infos"** e ativar ou desativar o dispositivo.

> 🖼️ *[Inserir imagem da tela Rastreadores do Sistema]*

#### 5.7.3 Usuários e Permissões
Tela intermediária que dá acesso à gestão de grupos e permissões de acesso dos usuários.

> 🖼️ *[Inserir imagem da tela Usuários e Permissões]*

##### 5.7.3.1 Grupo de Usuários
Permite criar e excluir grupos de usuários, bem como adicionar (`+`) ou remover (`−`) membros de cada grupo para facilitar a atribuição coletiva de privilégios.

> 🖼️ *[Inserir imagem da tela Grupo de Usuários]*

##### 5.7.3.2 Permissões de Usuário
Permite conceder ou negar permissões específicas (como *Login*, *Ver Mapa*, *Registrar Rastreador*, *Modificar Rastreador*, *Transferir Posse*, *Gerenciar ouvintes*, *Rastreio Salvo*, *Rastreio T.R.*, *Intervalo Oculto*, entre outras) diretamente a um usuário individual ou a um grupo, com opção de agrupamento por **Permissão**, **Entidade** ou **Usuário**.

> 🖼️ *[Inserir imagem da tela Permissões de Usuário]*

#### 5.7.4 Rastreadores e Permissões
Tela intermediária que dá acesso à organização de grupos e regras de permissão dos dispositivos rastreadores.

> 🖼️ *[Inserir imagem da tela Rastreadores e Permissões]*

##### 5.7.4.1 Grupo de Rastreadores
Permite criar e excluir grupos de dispositivos rastreadores, além de vincular (`+`) ou desvincular (`−`) equipamentos em cada grupo.

> 🖼️ *[Inserir imagem da tela Grupo de Rastreadores]*

##### 5.7.4.2 Permissões de Rastreador
Permite conceder ou negar permissões operacionais aos rastreadores ou grupos de rastreadores (como *Conexão*, *Enviar Localização*, *Registrável*, *Rastreável R.T*, *Rastreável* e *Ouvintes*), organizando a visualização por **Permissão**, **Entidade** ou **Rastreador**.

> 🖼️ *[Inserir imagem da tela Permissões de Rastreador]*

#### 5.7.5 Auditoria
Permite consultar o histórico completo de operações realizadas no sistema. Ao clicar em **"Filtrar auditorias"**, o administrador pode refinar a busca por **Tabela**, **Texto**, **Período (De / Até)** e **Resultado** (*Sucesso*, *Falha* ou *Todos*), visualizando na tabela a ação executada, os valores antes e depois da alteração, data, usuário responsável e resultado.

> 🖼️ *[Inserir imagem da tela Auditoria]*

#### 5.7.6 Logs
Exibe os registros técnicos e ocorrências de erro do sistema. Por meio do botão **"Filtrar logs"**, é possível filtrar os registros por **Título**, **Mensagem**, **Endereço IP** e **Intervalo de datas (De / Até)**, apresentando em tabela o título, mensagem detalhada, IP, ID e usuário associado, data e URL da requisição.

> 🖼️ *[Inserir imagem da tela Logs]*

---

## 6. Logout do Sistema

Para encerrar a sessão com segurança no Vehoute, o usuário deve retornar à tela inicial de autenticação por meio de uma das seguintes opções:

1. Clicar no botão **"Sair"** presente no menu lateral, junto aos acessos rápidos das páginas;
2. Acessar o endereço de login do sistema (`/login`) diretamente na barra de endereços do navegador;
3. Utilizar o botão de voltar do navegador até retornar à tela de Login.

Ao carregar a página de login, o sistema executa automaticamente a rotina de *logoff*, invalidando o token de acesso, removendo o *cookie* de autenticação e limpando os dados da sessão no navegador.

> 🔒 **Expiração Automática:** Por medida de segurança, a sessão possui tempo de expiração automática de **1 hora**. Caso esse limite seja atingido sem renovação, o sistema encerra o acesso e solicita que o usuário faça login novamente para continuar utilizando a plataforma.

> 🖼️ *[Inserir imagem da tela de Login após o encerramento da sessão]*