# 💈 Sistema de Gestão para Barbearia

Um sistema completo de gestão para barbearias desenvolvido em PHP com MySQL, oferecendo funcionalidades tanto para clientes quanto para administradores.

## 🚀 Funcionalidades

### Para Clientes (Frontend)

- **Agendamento Online**: Sistema completo de agendamento com seleção de funcionário, data, horário e serviços
- **Catálogo de Produtos**: Visualização de produtos disponíveis
- **Catálogo de Serviços**: Lista de serviços oferecidos com preços
- **Integração WhatsApp**: Contato direto via WhatsApp
- **Design Responsivo**: Interface adaptável para dispositivos móveis

### Para Administradores (Backend)

- **Dashboard Completo**: Indicadores de performance, agendamentos e financeiro
- **Gestão de Agendamentos**: Controle completo da agenda por funcionário
- **Gestão de Funcionários**: Cadastro, horários, dias de trabalho e comissões
- **Gestão Financeira**: Contas a pagar/receber, relatórios de vendas
- **Controle de Estoque**: Produtos, fornecedores e alertas de estoque baixo
- **Gestão de Clientes**: Cadastro completo com histórico e cartão fidelidade
- **Sistema de Permissões**: Controle granular de acesso por módulo
- **Relatórios**: Diversos relatórios gerenciais

## 🛠️ Tecnologias Utilizadas

### Frontend

- **HTML5** e **CSS3**
- **JavaScript** e **jQuery**
- **Bootstrap** para responsividade
- **Owl Carousel** para sliders
- **Font Awesome** para ícones
- **AJAX** para interações dinâmicas

### Backend

- **PHP** (versão 7.0+)
- **MySQL** para banco de dados
- **PDO** para conexão com banco
- **Sistema de Sessões** para autenticação
- **DOMPDF** para geração de relatórios

## ☁️ Deploy no Render

Este repositório já está preparado para deploy no Render usando Docker:

- `Dockerfile` para subir o app PHP
- `render.yaml` com serviço web e variáveis de ambiente

### Passos

1. Suba este repositório para o GitHub.
2. No Render, crie um **Web Service** conectando o repositório.
3. O Render vai detectar `render.yaml` automaticamente.
4. Use `.env.render.example` como referência e configure as variáveis no painel do Render:

```env
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=

SAAS_DB_HOST=
SAAS_DB_NAME=
SAAS_DB_USER=
SAAS_DB_PASS=
```

No Render, use banco MySQL externo e senhas fortes.

### Observação importante

O Render não oferece MySQL gerenciado nativo. Para produção, use um MySQL externo (Railway, Aiven, PlanetScale, VPS com MariaDB, etc.) e preencha as variáveis acima com esse servidor.

## 📋 Pré-requisitos

- **XAMPP** ou servidor web com PHP 7.0+
- **MySQL** 5.6+
- **Navegador web** moderno

## 🔧 Instalação

1. **Clone ou baixe o projeto** para a pasta `htdocs` do XAMPP:

   ```
   c:\xampp\htdocs\barbearia\
   ```

2. **Importe o banco de dados**:

   - Acesse o phpMyAdmin
   - Crie um banco chamado `barbearia`
   - Importe o arquivo `barbearia.sql`

3. **Configure a conexão**:

   - Edite o arquivo `sistema/conexao.php`
   - Ajuste as credenciais do banco de dados:
     ```php
     $servidor = 'localhost';
     $banco = 'barbearia';
     $usuario = 'root';
     $senha = '';
     ```

4. **Acesse o sistema**:
   - Frontend: `http://localhost/barbearia`
   - Backend: `http://localhost/barbearia/sistema`

## 🐳 Rodar com Docker Compose (mais prático)

Se quiser subir tudo local com um comando (app + MariaDB + carga dos dumps):

1. Gere o `.env` local (uma vez):

```bash
cp .env.example .env
```

2. Suba tudo com um comando:

```bash
docker compose up -d --build
```

Ou, se preferir, use os atalhos via `Makefile`:

```bash
make up
```

3. Abra no navegador:

- Frontend: `http://localhost:8000`
- Backend: `http://localhost:8000/sistema`

4. Parar ambiente:

```bash
docker compose down
```

5. Resetar banco e recriar do zero (apaga dados):

```bash
docker compose down -v
docker compose up -d --build
```

6. Rodar checklist funcional automático (smoke test):

```bash
make smoke
```

Comandos rápidos do `Makefile`:

- `make up` (sobe stack)
- `make down` (desce stack)
- `make reset` (recria banco)
- `make logs` (logs app + db)
- `make ps` (status dos serviços)
- `make smoke` (validação funcional)

Variáveis opcionais:

- `MARIADB_ROOT_PASSWORD` (padrão: `root123`)
- `APP_DB_PASS` (padrão: `app123`)
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `SAAS_DB_HOST`, `SAAS_DB_NAME`, `SAAS_DB_USER`, `SAAS_DB_PASS`

## ⚠️ Erro 502 / Bad Gateway

Se o dominio estiver no Cloudflare Tunnel e aparecer **Bad Gateway**, normalmente o servico local na porta `8000` nao esta em execucao.

Suba o servidor PHP local no diretorio `app/app`:

```bash
php -S 127.0.0.1:8000 -t . router.php
```

Depois valide localmente:

```bash
curl -I http://127.0.0.1:8000
```

Se estiver usando dominio via Cloudflare Tunnel, valide tambem se o tunnel esta ativo:

```bash
cloudflared --config sistema/saas/tunnels/barbearia-demo-saas.yml tunnel run
```

## ♻️ Persistencia dos tuneis

Foi padronizado um stack via `systemd --user` para manter os servicos e tuneis ativos apos reinicio/sessao:

```bash
systemctl --user daemon-reload
systemctl --user enable --now superzap-tunnels.target
```

Verifique rapidamente tudo com:

```bash
/home/iluminatto/scripts/status-superzap-tunnels.sh
```

Observacao: o backend do AtendeChat foi adicionado ao stack user como `atendechat-backend-8082.service`.
Com ele ativo, `atende-api.superzap.fun` e `api.atende.superzap.fun` deixam de retornar Bad Gateway (atualmente retornam `403`, esperado sem autenticacao).

## 🔐 Acesso Padrão

**Administrador:**

## dados_ficticios.sql

- **Email**: admin@barbeariaelite.com
- **Senha**: 123

## barbearia.sql

- **Email**: admin@admin
- **Senha**: 123

## 📁 Estrutura do Projeto

```
barbearia/
├── ajax/                    # Scripts AJAX
├── css/                     # Estilos CSS
├── fonts/                   # Fontes
├── images/                  # Imagens do frontend
├── js/                      # Scripts JavaScript
├── sistema/                 # Sistema administrativo
│   ├── painel/             # Painel administrativo
│   │   └── paginas/        # Módulos do sistema
│   ├── conexao.php         # Configuração do banco
│   └── autenticar.php      # Autenticação
├── agendamentos.php        # Página de agendamentos
├── produtos.php            # Página de produtos
├── servicos.php            # Página de serviços
├── index.php               # Página inicial
└── barbearia.sql           # Estrutura do banco
```

## 🗄️ Estrutura do Banco de Dados

### Tabelas Principais

- **usuarios**: Funcionários e administradores
- **agendamentos**: Controle de horários e serviços
- **clientes**: Cadastro de clientes
- **servicos**: Catálogo de serviços
- **produtos**: Controle de estoque
- **receber/pagar**: Gestão financeira
- **horarios**: Horários disponíveis
- **config**: Configurações do sistema

## 🎨 Personalização

### Configurações do Sistema

Acesse o painel administrativo para personalizar:

- Nome da barbearia
- Logo e imagens
- Informações de contato
- Textos da página inicial
- Configurações de WhatsApp e redes sociais

Para alterar o horário de funcionamento no Google Maps, siga os passos abaixo:

abra o arquivo `index.php` e encontre a linha 1290:

<p><i class="fa fa-clock-o"></i>Seg-Sex: 8h às 18h | Sáb: 8h às 16h</p>

### Estilos

- Edite `css/style.css` para personalizar o frontend
- Modifique `sistema/painel/css/` para o backend

## 📱 Recursos Especiais

- **URLs Amigáveis**: Sistema de reescrita via .htaccess
- **Validação em Tempo Real**: Verificação de horários disponíveis
- **Cadastro Automático**: Clientes são cadastrados automaticamente no agendamento
- **Sistema de Cartão Fidelidade**: Controle de pontos por cliente
- **Alertas de Estoque**: Notificações quando produtos estão em baixa
- **Comissões**: Cálculo automático de comissões por funcionário

## 🔒 Segurança

- Autenticação por sessões PHP
- Senhas criptografadas (MD5)
- Sistema de permissões granular
- Validações de entrada de dados
- Proteção contra SQL Injection (PDO)

## 🏢 Modo SaaS (Multiempresa)

O projeto agora suporta modo multiempresa por dominio, com conexao dinamica por tenant.

- Banco de controle SaaS: `barbearia_saas`
- Mapeamento por dominio: tabela `empresas_dominios`
- Cada empresa usa banco proprio

Leia o guia em `sistema/saas/README.md` para bootstrap e cadastro de novas empresas.

---

**Desenvolvido com ❤️ para facilitar a gestão de barbearias**
