# Modo SaaS (Multiempresa)

Este sistema foi preparado para operar em modo multiempresa usando estrategia **database-per-tenant**.

Tambem foi implementado o modulo de planos SaaS (sem precificacao), com assinatura por empresa e limites por recurso.

## Como funciona

- Cada empresa usa um banco proprio (exemplo: `barbearia_empresa_a`)
- Um banco de controle (`barbearia_saas`) faz o mapeamento:
  - empresa -> banco
  - dominio -> empresa
- O arquivo `sistema/conexao.php` identifica o dominio da requisicao e conecta no banco correto

## 1) Bootstrap inicial

No diretorio `app/app`, execute:

```bash
php sistema/saas/bootstrap.php
```

Isso cria o banco de controle e registra a empresa principal.

## 2) Criar banco de uma nova empresa

Clone o banco modelo (exemplo usando o banco atual `barbearia`):

```bash
mysqldump -u root -p barbearia > /tmp/base_tenant.sql
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS barbearia_empresa_x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p barbearia_empresa_x < /tmp/base_tenant.sql
```

## 3) Registrar a nova empresa no SaaS

```bash
php sistema/saas/registrar_empresa.php \
  --nome="Barbearia Empresa X" \
  --slug="empresa-x" \
  --dominio="empresa-x.seudominio.com" \
  --banco="barbearia_empresa_x"
```

Ao registrar empresa, o script agora faz automaticamente:

- cria/usa um tunnel no Cloudflared (`tenant-<slug>`)
- cria o DNS do dominio principal para esse tunnel
- salva o arquivo de config em `sistema/saas/tunnels/<slug>.yml`
- inicia o tunnel em background e grava log em `sistema/saas/logs/<slug>.log`
- registra vinculo na tabela `empresas_tunnels`
- cria/atualiza a assinatura inicial da empresa no plano `starter`

Para executar sem rede (somente cadastro SaaS), use:

```bash
php sistema/saas/registrar_empresa.php \
  --nome="Barbearia Empresa X" \
  --slug="empresa-x" \
  --dominio="empresa-x.seudominio.com" \
  --banco="barbearia_empresa_x" \
  --skip-network
```

Se precisar adicionar mais dominios:

```bash
php sistema/saas/registrar_empresa.php \
  --nome="Barbearia Empresa X" \
  --slug="empresa-x" \
  --dominio="empresa-x.seudominio.com" \
  --extra-domains="www.empresa-x.seudominio.com,empresa-x.local" \
  --banco="barbearia_empresa_x"
```

## 4) Requisitos de tunnel

Antes de registrar empresas com rede automatica, garanta:

```bash
cloudflared login
cloudflared tunnel list
```

Observacao: dominios locais (`localhost`, IPs, `.local`) nao recebem DNS no Cloudflare automaticamente.

Opcionalmente, você pode sobrescrever o nome do tunnel e o service URL:

```bash
php sistema/saas/registrar_empresa.php \
  --nome="Barbearia Empresa X" \
  --slug="empresa-x" \
  --dominio="empresa-x.seudominio.com" \
  --banco="barbearia_empresa_x" \
  --tunnel-name="empresa-x-tunnel" \
  --service-url="http://127.0.0.1:8000"
```

## 5) Planos SaaS

Tabelas criadas no `barbearia_saas`:

- `planos`
- `planos_recursos`
- `empresas_assinaturas`
- `empresas_uso_mensal`
- `empresas_eventos_billing`

Plano inicial criado automaticamente:

- `starter` (trial por padrao)
- recursos de menu habilitados
- limites base:
  - usuarios: 20
  - produtos: 500
  - servicos: 120
  - agendamentos/mes: 2000

Ao criar empresa, voce pode sobrescrever assinatura:

```bash
php sistema/saas/registrar_empresa.php \
  --nome="Barbearia Empresa X" \
  --slug="empresa-x" \
  --dominio="empresa-x.seudominio.com" \
  --banco="barbearia_empresa_x" \
  --plano="starter" \
  --status-assinatura="Trial" \
  --trial-dias=14
```

Para atualizar assinatura de uma empresa existente:

```bash
php sistema/saas/atualizar_assinatura.php --slug="empresa-x" --plano="starter" --status="Ativa"
```

## 6) Checkout PIX e Cartao com PagBank

Foi adicionado checkout de renovacao da assinatura dentro do painel do tenant em:

- `sistema/painel/index.php?pag=assinatura_saas`

Endpoints da integracao:

- `sistema/saas/pagamento_checkout.php` (PIX, cartao, consulta, cancelamento e estorno)
- `sistema/saas/pagbank_webhook.php` (confirmacao assincrona e atualizacao de status)
- `sistema/saas/reconciliar_pagamentos.php` (reconciliacao via CLI)

Variaveis de ambiente suportadas:

```env
PAGBANK_MODE=sandbox
PAGBANK_CLIENT_ID=
PAGBANK_CLIENT_SECRET=
PAGBANK_TOKEN=
PAGBANK_SIMULATION=false

PAGBANK_WEBHOOK_TOKEN=
PAGBANK_WEBHOOK_REQUIRE_TOKEN=true
PAGBANK_WEBHOOK_HMAC_SECRET=

PAGBANK_REQUIRE_CARD_TOKEN=true
PAGBANK_ALLOW_PLAIN_CARD=false
PAGBANK_ENABLE_AUTO_REFUND=false

PAGBANK_NOTIFY_EMAIL_FROM=
PAGBANK_NOTIFY_EMAIL_REPLY_TO=

PAGBANK_EXPIRE_PENDING_MINUTES=1440
PAGBANK_RECONCILE_LIMIT=200
PAGBANK_RENEW_DAYS=30
SAAS_PLAN_DEFAULT_VALUE=79.90
```

Observacoes importantes:

- `PAGBANK_SIMULATION=true` permite testar o fluxo sem cobranca real (PIX e cartao).
- Em producao, mantenha `PAGBANK_REQUIRE_CARD_TOKEN=true` e `PAGBANK_ALLOW_PLAIN_CARD=false`.
- O checkout exige CSRF e idempotencia em todas as acoes de escrita.
- O CPF do titular/responsavel precisa existir e ser valido para gerar cobranca.
- Com webhook em producao, configure a URL publica apontando para `sistema/saas/pagbank_webhook.php`.
- Cada pagamento confirmado renova o ciclo da assinatura por `PAGBANK_RENEW_DAYS` (padrao: 30 dias).

## 7) Webhook seguro

Recomendacao em producao:

- `PAGBANK_WEBHOOK_REQUIRE_TOKEN=true`
- `PAGBANK_WEBHOOK_TOKEN` preenchido
- `PAGBANK_WEBHOOK_HMAC_SECRET` preenchido

Com HMAC ativo, o endpoint valida assinatura do payload recebido.

## 8) Reconciliacao de pagamentos pendentes

O script CLI consulta pedidos pendentes no gateway e atualiza status local:

```bash
php sistema/saas/reconciliar_pagamentos.php
```

Opcoes disponiveis:

```bash
php sistema/saas/reconciliar_pagamentos.php --limit=100
php sistema/saas/reconciliar_pagamentos.php --empresa=12
php sistema/saas/reconciliar_pagamentos.php --dry-run
```

## 9) Cancelamento e estorno no painel

- Cancelamento: permitido para pagamentos ainda nao confirmados.
- Estorno: permitido para pagamentos `Pago`.
- Com `PAGBANK_ENABLE_AUTO_REFUND=true`, o sistema tenta estorno direto no gateway.
- Com `PAGBANK_ENABLE_AUTO_REFUND=false`, registra solicitacao para processamento manual.

## 10) Agendamento em producao (systemd/cron)

Opcao recomendada (systemd user timer):

```bash
chmod +x sistema/saas/instalar_reconciliacao_timer.sh
./sistema/saas/instalar_reconciliacao_timer.sh 5
```

Isso cria e habilita:

- `~/.config/systemd/user/barbearia-saas-reconciliacao.service`
- `~/.config/systemd/user/barbearia-saas-reconciliacao.timer`

Comandos uteis:

```bash
systemctl --user status barbearia-saas-reconciliacao.timer
systemctl --user start barbearia-saas-reconciliacao.service
journalctl --user -u barbearia-saas-reconciliacao.service -n 100 --no-pager
```

Opcao alternativa (cron): use o exemplo em `sistema/saas/reconciliacao.cron.example`.

## 11) Checklist de validacao rapida

- Gerar PIX em simulacao (`gerar_pix`) e confirmar idempotencia com a mesma `idempotency_key`.
- Consultar status (`status`) e cancelar pendente (`cancelar_pagamento`).
- Pagar cartao em simulacao (`pagar_cartao`) e validar renovacao.
- Solicitar estorno (`solicitar_estorno`) em pagamento `Pago`.
- Processar webhook com token/HMAC e validar deduplicacao por `event_id`.
- Rodar reconciliacao (`--dry-run`) e conferir resumo final.
