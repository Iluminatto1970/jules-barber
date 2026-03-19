#!/usr/bin/env bash
set -euo pipefail

if ! command -v systemctl >/dev/null 2>&1; then
    echo "[ERRO] systemctl nao encontrado neste ambiente." >&2
    exit 1
fi

if ! systemctl --user --version >/dev/null 2>&1; then
    echo "[ERRO] systemd de usuario nao disponivel." >&2
    exit 1
fi

INTERVAL_MINUTES="${1:-5}"
if ! [[ "$INTERVAL_MINUTES" =~ ^[0-9]+$ ]]; then
    echo "[ERRO] Intervalo invalido. Use numero inteiro em minutos (ex: 5)." >&2
    exit 1
fi

if (( INTERVAL_MINUTES < 1 || INTERVAL_MINUTES > 60 )); then
    echo "[ERRO] Intervalo deve ficar entre 1 e 60 minutos." >&2
    exit 1
fi

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP_SCRIPT="${BASE_DIR}/sistema/saas/reconciliar_pagamentos.php"

if [[ ! -f "$PHP_SCRIPT" ]]; then
    echo "[ERRO] Script de reconciliacao nao encontrado em: $PHP_SCRIPT" >&2
    exit 1
fi

RUNNER_DIR="${HOME}/.local/bin"
RUNNER_PATH="${RUNNER_DIR}/barbearia-saas-reconciliar"

UNIT_DIR="${HOME}/.config/systemd/user"
SERVICE_NAME="barbearia-saas-reconciliacao.service"
TIMER_NAME="barbearia-saas-reconciliacao.timer"
STATE_DIR="${HOME}/.local/state/barbearia-saas"
LOG_PATH="${STATE_DIR}/reconciliacao.log"
LOCK_PATH="/tmp/barbearia-saas-reconcile.lock"

mkdir -p "$RUNNER_DIR" "$UNIT_DIR" "$STATE_DIR"

cat > "$RUNNER_PATH" <<EOF
#!/usr/bin/env bash
set -euo pipefail
exec /usr/bin/flock -n "$LOCK_PATH" /usr/bin/php "$PHP_SCRIPT" "\$@"
EOF

chmod +x "$RUNNER_PATH"

cat > "${UNIT_DIR}/${SERVICE_NAME}" <<EOF
[Unit]
Description=Barbearia SaaS payment reconciliation

[Service]
Type=oneshot
WorkingDirectory=$BASE_DIR
ExecStart=$RUNNER_PATH
StandardOutput=append:$LOG_PATH
StandardError=append:$LOG_PATH
Nice=10
IOSchedulingClass=best-effort
IOSchedulingPriority=7
EOF

cat > "${UNIT_DIR}/${TIMER_NAME}" <<EOF
[Unit]
Description=Barbearia SaaS payment reconciliation timer

[Timer]
OnCalendar=*:0/$INTERVAL_MINUTES
RandomizedDelaySec=30
AccuracySec=1min
Persistent=true
Unit=$SERVICE_NAME

[Install]
WantedBy=timers.target
EOF

systemctl --user daemon-reload
systemctl --user enable --now "$TIMER_NAME"

echo "[OK] Timer instalado: $TIMER_NAME"
echo "[OK] Intervalo: a cada $INTERVAL_MINUTES minuto(s)"
echo "[OK] Log: $LOG_PATH"
echo "[INFO] Ver status: systemctl --user status $TIMER_NAME"
echo "[INFO] Executar agora: systemctl --user start $SERVICE_NAME"
