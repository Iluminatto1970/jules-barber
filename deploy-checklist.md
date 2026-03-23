# Deploy Checklist (Render)

## 1) Preparar repositório

- Confirmar que os arquivos de deploy estão versionados:
  - `Dockerfile`
  - `render.yaml`
  - `sistema/conexao.php`
  - `README.md`

## 2) Banco de dados externo

- Criar um MySQL/MariaDB externo (Render não fornece MySQL nativo).
- Criar os bancos:
  - `barbearia`
  - `barbearia_saas`
- Criar usuário de aplicação com permissão nesses dois bancos.
- Importar os dumps:
  - `sistema/backups/barbearia-20260225-080849.sql`
  - `sistema/backups/barbearia_saas-20260225-080856.sql`

## 3) Render Web Service

- Conectar o repositório no Render.
- Criar Web Service usando `render.yaml`.
- Definir variáveis de ambiente:

```env
DB_HOST=<host_mysql>
DB_NAME=barbearia
DB_USER=<usuario_app>
DB_PASS=<senha_app>

SAAS_DB_HOST=<host_mysql>
SAAS_DB_NAME=barbearia_saas
SAAS_DB_USER=<usuario_app>
SAAS_DB_PASS=<senha_app>
```

## 4) Validação pós-deploy

- Abrir homepage e validar carregamento.
- Validar login em `/sistema/`.
- Validar painel administrativo.
- Validar rota SaaS admin (`/sistema/saas/admin/`).
- Checar logs do serviço no Render para erros de conexão com banco.

## 5) Operação local (apoio)

- Subir stack local: `make up`
- Validar fluxos básicos: `make smoke`
- Resetar banco local: `make reset`
