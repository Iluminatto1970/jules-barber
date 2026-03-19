<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Uso permitido apenas via CLI.";
    exit;
}

require_once __DIR__ . '/../conexao.php';

if (!$pdo_saas instanceof PDO) {
    fwrite(STDERR, "[ERRO] Conexao com banco SaaS indisponivel." . PHP_EOL);
    exit(1);
}

saas_pagbank_garantir_estrutura($pdo_saas);

$limit = 0;
$empresaFiltro = 0;
$dryRun = false;

foreach ($argv as $arg) {
    if (strpos((string) $arg, '--limit=') === 0) {
        $limit = (int) substr((string) $arg, 8);
        continue;
    }

    if (strpos((string) $arg, '--empresa=') === 0) {
        $empresaFiltro = (int) substr((string) $arg, 9);
        continue;
    }

    if ($arg === '--dry-run') {
        $dryRun = true;
    }
}

$config = saas_pagbank_config();
if ($limit <= 0) {
    $limit = (int) ($config['reconciliacao_limit'] ?? 200);
}
$limit = max(10, min(1000, $limit));

$sql = "SELECT id, empresa_id, pedido_referencia, pagbank_order_id, status, metodo_pagamento, expiracao_em, valor
    FROM empresas_pagamentos
    WHERE status = 'Pendente'";

if ($empresaFiltro > 0) {
    $sql .= " AND empresa_id = :empresa_id";
}

$sql .= " ORDER BY id ASC LIMIT {$limit}";

$query = $pdo_saas->prepare($sql);
if ($empresaFiltro > 0) {
    $query->bindValue(':empresa_id', $empresaFiltro, PDO::PARAM_INT);
}
$query->execute();
$pagamentos = $query->fetchAll(PDO::FETCH_ASSOC);

$total = count($pagamentos);
if ($total === 0) {
    echo "[INFO] Nenhum pagamento pendente para reconciliar." . PHP_EOL;
    exit(0);
}

$auth = null;
if (empty($config['simulation'])) {
    $auth = saas_pagbank_obter_auth_header($config);
    if (empty($auth['ok'])) {
        fwrite(STDERR, "[ERRO] Falha ao autenticar no PagBank para reconciliacao." . PHP_EOL);
        exit(1);
    }
}

$contadores = [
    'processados' => 0,
    'pagos' => 0,
    'cancelados' => 0,
    'expirados' => 0,
    'falhas' => 0,
    'pendentes' => 0,
    'erros' => 0,
];

echo "[INFO] Reconciliando {$total} pagamento(s)..." . PHP_EOL;

foreach ($pagamentos as $pagamento) {
    $contadores['processados']++;
    $pagamentoId = (int) $pagamento['id'];
    $orderId = trim((string) ($pagamento['pagbank_order_id'] ?? ''));
    $statusLocal = 'Pendente';
    $statusApi = '';
    $payload = [];

    if (!empty($pagamento['expiracao_em'])) {
        $expiraTimestamp = strtotime((string) $pagamento['expiracao_em']);
        if ($expiraTimestamp && $expiraTimestamp < time()) {
            $statusLocal = 'Expirado';
            $statusApi = 'expired';
        }
    }

    if ($statusLocal === 'Pendente' && !empty($config['simulation'])) {
        $contadores['pendentes']++;
        echo "[SKIP] #{$pagamentoId} em simulacao." . PHP_EOL;
        continue;
    }

    if ($statusLocal === 'Pendente' && $orderId === '') {
        $contadores['pendentes']++;
        echo "[SKIP] #{$pagamentoId} sem order_id no gateway." . PHP_EOL;
        continue;
    }

    if ($statusLocal === 'Pendente') {
        $consulta = saas_pagbank_consultar_order($config, (string) ($auth['header'] ?? ''), $orderId);
        if (empty($consulta['ok']) || !is_array($consulta['body'])) {
            $contadores['erros']++;
            echo "[ERRO] #{$pagamentoId} falha ao consultar order {$orderId}." . PHP_EOL;
            continue;
        }

        $payload = $consulta['body'];
        $statusApi = saas_pagbank_extrair_status_ordem($payload);
        $statusLocal = saas_pagbank_status_local($statusApi);
    }

    if ($dryRun) {
        echo "[DRY] #{$pagamentoId} -> {$statusLocal} ({$statusApi})" . PHP_EOL;
    } else {
        saas_pagbank_incrementar_tentativa_consulta($pdo_saas, $pagamentoId);
        $update = saas_pagbank_atualizar_status_pagamento(
            $pdo_saas,
            $pagamentoId,
            $statusLocal,
            $statusApi,
            $payload,
            'consulta_status',
            'Reconciliacao automatica'
        );

        if (empty($update['ok'])) {
            $contadores['erros']++;
            echo "[ERRO] #{$pagamentoId} nao atualizado: " . ($update['message'] ?? 'erro desconhecido') . PHP_EOL;
            continue;
        }

        echo "[OK] #{$pagamentoId} -> {$statusLocal}" . PHP_EOL;
    }

    if ($statusLocal === 'Pago') {
        $contadores['pagos']++;
    } elseif ($statusLocal === 'Cancelado') {
        $contadores['cancelados']++;
    } elseif ($statusLocal === 'Expirado') {
        $contadores['expirados']++;
    } elseif ($statusLocal === 'Falha') {
        $contadores['falhas']++;
    } else {
        $contadores['pendentes']++;
    }
}

echo PHP_EOL;
echo "[RESUMO] processados={$contadores['processados']} pagos={$contadores['pagos']} cancelados={$contadores['cancelados']} expirados={$contadores['expirados']} falhas={$contadores['falhas']} pendentes={$contadores['pendentes']} erros={$contadores['erros']}" . PHP_EOL;

exit(0);
