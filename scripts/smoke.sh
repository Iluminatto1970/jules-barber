#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"

ADMIN_EMAIL="${SMOKE_ADMIN_EMAIL:-admin@admin}"
ADMIN_PASSWORD="${SMOKE_ADMIN_PASSWORD:-123}"

COOKIE_JAR="$(mktemp)"
trap 'rm -f "$COOKIE_JAR"' EXIT

assert_status() {
  local path="$1"
  local expected="$2"
  local code
  code="$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${path}")"
  if [[ "$code" != "$expected" ]]; then
    echo "FAIL: ${path} retornou HTTP ${code} (esperado ${expected})"
    exit 1
  fi
  echo "OK: ${path} -> HTTP ${code}"
}

assert_status_in() {
  local path="$1"
  shift
  local allowed=("$@")
  local code
  code="$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${path}")"
  local ok="false"
  for expected in "${allowed[@]}"; do
    if [[ "$code" == "$expected" ]]; then
      ok="true"
      break
    fi
  done
  if [[ "$ok" != "true" ]]; then
    echo "FAIL: ${path} retornou HTTP ${code} (esperado: ${allowed[*]})"
    exit 1
  fi
  echo "OK: ${path} -> HTTP ${code}"
}

assert_contains() {
  local path="$1"
  local pattern="$2"
  local body
  body="$(curl -s "${BASE_URL}${path}")"
  if ! grep -Fq "$pattern" <<<"$body"; then
    echo "FAIL: ${path} nao contem '${pattern}'"
    exit 1
  fi
  echo "OK: ${path} contem '${pattern}'"
}

echo "==> Validando endpoints publicos"
assert_status "/" "200"
assert_status "/agendamentos" "200"
assert_status "/produtos" "200"
assert_status "/servicos" "200"

echo "==> Validando login administrativo"
assert_status "/sistema/" "200"

login_ok="false"
login_response=""
for _ in $(seq 1 20); do
  login_response="$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -d "email=${ADMIN_EMAIL}&senha=${ADMIN_PASSWORD}" -X POST "${BASE_URL}/sistema/autenticar.php")"
  if grep -Fq "painel/" <<<"$login_response"; then
    login_ok="true"
    break
  fi
  sleep 2
done

if [[ "$login_ok" != "true" ]]; then
  echo "FAIL: login nao redirecionou para painel (credenciais: ${ADMIN_EMAIL})"
  echo "Resposta recebida:"
  echo "$login_response" | sed -n '1,5p'
  exit 1
fi
echo "OK: login respondeu com redirect para painel"

panel_body="$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "${BASE_URL}/sistema/painel/")"
if ! grep -Eq "MENU DE NAVEGACAO|MENU DE NAVEGAÇÃO|Painel" <<<"$panel_body"; then
  echo "FAIL: painel nao carregou apos login"
  exit 1
fi
echo "OK: painel carregou com sessao autenticada"

echo "==> Validando rota SaaS admin"
assert_status_in "/sistema/saas/admin/" "200" "302"

echo "Smoke test finalizado com sucesso"
