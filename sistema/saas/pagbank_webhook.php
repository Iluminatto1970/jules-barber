<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexao.php';

function saas_webhook_json($ok, $dados = [])
{
    echo json_encode(array_merge(['ok' => (bool) $ok], $dados), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function saas_webhook_fail($mensagem, $http = 400)
{
    http_response_code((int) $http);
    saas_webhook_json(false, ['mensagem' => (string) $mensagem]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    saas_webhook_fail('Metodo invalido.', 405);
}

if (!$pdo_saas instanceof PDO) {
    saas_webhook_fail('Servico SaaS indisponivel.', 503);
}

saas_pagbank_garantir_estrutura($pdo_saas);

$config = saas_pagbank_config();
$tokenWebhook = trim((string) ($config['webhook_token'] ?? ''));
$requireToken = !empty($config['webhook_require_token']);
if ($requireToken && $tokenWebhook === '') {
    saas_webhook_fail('Webhook PagBank nao configurado com token de seguranca.', 503);
}

if ($tokenWebhook !== '') {
    $tokenRecebido = '';
    $candidatosToken = ['X-Webhook-Token', 'X-PagBank-Token', 'X-PagSeguro-Token'];
    foreach ($candidatosToken as $headerNome) {
        $headerValor = saas_pagbank_header($headerNome);
        if ($headerValor !== '') {
            $tokenRecebido = $headerValor;
            break;
        }
    }

    if ($tokenRecebido === '' || !hash_equals($tokenWebhook, $tokenRecebido)) {
        saas_webhook_fail('Token de webhook invalido.', 401);
    }
}

$raw = file_get_contents('php://input');
$hmacSecret = trim((string) ($config['webhook_hmac_secret'] ?? ''));
if ($hmacSecret !== '') {
    $assinaturaRecebida = '';
    $candidatosAssinatura = ['X-Signature', 'X-Webhook-Signature', 'X-PagBank-Signature', 'X-PagSeguro-Signature'];
    foreach ($candidatosAssinatura as $headerNome) {
        $headerValor = saas_pagbank_header($headerNome);
        if ($headerValor !== '') {
            $assinaturaRecebida = $headerValor;
            break;
        }
    }

    if (!saas_pagbank_validar_assinatura_webhook((string) $raw, $assinaturaRecebida, $hmacSecret)) {
        saas_webhook_fail('Assinatura HMAC do webhook invalida.', 401);
    }
}

$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}
if (!is_array($payload)) {
    $payload = [];
}

$status = saas_pagbank_extrair_status_ordem($payload);
$statusLocal = saas_pagbank_status_local($status);
$eventType = strtolower(trim((string) ($payload['event'] ?? $payload['type'] ?? $payload['notification_type'] ?? '')));
$eventId = saas_pagbank_extrair_evento_id($payload);

$reference = trim((string) (
    $payload['reference_id']
    ?? $payload['order_id']
    ?? ($payload['data']['reference_id'] ?? '')
    ?? ($payload['order']['reference_id'] ?? '')
));

$orderId = trim((string) (
    $payload['id']
    ?? ($payload['order']['id'] ?? '')
    ?? ($payload['data']['id'] ?? '')
    ?? ''
));

if ($reference === '' && !empty($payload['charges']) && is_array($payload['charges'])) {
    foreach ($payload['charges'] as $charge) {
        if (!is_array($charge)) {
            continue;
        }

        if ($reference === '' && !empty($charge['reference_id'])) {
            $reference = trim((string) $charge['reference_id']);
        }

        if ($orderId === '' && !empty($charge['id'])) {
            $orderId = trim((string) $charge['id']);
        }
    }
}

if ($reference === '' && $orderId === '') {
    saas_webhook_json(true, [
        'ignored' => true,
        'reason' => 'Webhook sem referencia de pagamento',
    ]);
}

$pagamento = null;

if ($reference !== '') {
    $query = $pdo_saas->prepare("SELECT id, status, pagbank_order_id, webhook_evento_id FROM empresas_pagamentos WHERE pedido_referencia = :ref LIMIT 1");
    $query->bindValue(':ref', $reference);
    $query->execute();
    $pagamento = $query->fetch(PDO::FETCH_ASSOC);
}

if (!$pagamento && $orderId !== '') {
    $query = $pdo_saas->prepare("SELECT id, status, pagbank_order_id, webhook_evento_id FROM empresas_pagamentos WHERE pagbank_order_id = :order_id LIMIT 1");
    $query->bindValue(':order_id', $orderId);
    $query->execute();
    $pagamento = $query->fetch(PDO::FETCH_ASSOC);
}

if (!$pagamento) {
    saas_webhook_json(true, [
        'ignored' => true,
        'reason' => 'Pagamento nao encontrado',
        'reference' => $reference,
        'order_id' => $orderId,
    ]);
}

$pagamentoId = (int) $pagamento['id'];
$eventoDuplicado = $eventId !== '' && !empty($pagamento['webhook_evento_id']) && hash_equals((string) $pagamento['webhook_evento_id'], $eventId);
if ($eventoDuplicado) {
    saas_webhook_json(true, [
        'processed' => true,
        'duplicated' => true,
        'pagamento_id' => $pagamentoId,
        'status' => (string) $pagamento['status'],
    ]);
}

$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$updatePayload = $pdo_saas->prepare("UPDATE empresas_pagamentos
    SET payload_webhook = :payload,
        webhook_evento_id = CASE WHEN :event_id <> '' THEN :event_id ELSE webhook_evento_id END,
        status_detalhe = CASE WHEN :status_detalhe <> '' THEN :status_detalhe ELSE status_detalhe END,
        pagbank_order_id = CASE
            WHEN (:order_id_check <> '' AND (pagbank_order_id IS NULL OR pagbank_order_id = '')) THEN :order_id_value
            ELSE pagbank_order_id
        END
    WHERE id = :id");
$updatePayload->bindValue(':payload', $payloadJson);
$updatePayload->bindValue(':event_id', $eventId);
$updatePayload->bindValue(':status_detalhe', $status !== '' ? $status : '');
$updatePayload->bindValue(':order_id_check', $orderId);
$updatePayload->bindValue(':order_id_value', $orderId);
$updatePayload->bindValue(':id', $pagamentoId, PDO::PARAM_INT);
$updatePayload->execute();

$eventoPago = $statusLocal === 'Pago'
    || strpos($eventType, 'paid') !== false
    || strpos($eventType, 'approved') !== false;

if ($eventoPago) {
    $confirmacao = saas_pagbank_marcar_pagamento_pago($pdo_saas, $pagamentoId, $payload, 'webhook_pagbank');
    if (empty($confirmacao['ok'])) {
        saas_webhook_fail($confirmacao['message'] ?? 'Falha ao processar pagamento confirmado.', 500);
    }

    saas_webhook_json(true, [
        'processed' => true,
        'status' => 'Pago',
        'pagamento_id' => $pagamentoId,
        'ciclo_ate' => $confirmacao['ciclo_ate'] ?? null,
    ]);
}

if (in_array($statusLocal, ['Cancelado', 'Expirado', 'Falha'], true)) {
    saas_pagbank_atualizar_status_pagamento(
        $pdo_saas,
        $pagamentoId,
        $statusLocal,
        $status,
        $payload,
        'webhook_pagbank',
        'Atualizado por webhook PagBank'
    );

    saas_webhook_json(true, [
        'processed' => true,
        'status' => $statusLocal,
        'pagamento_id' => $pagamentoId,
    ]);
}

saas_webhook_json(true, [
    'processed' => true,
    'status' => $status !== '' ? $status : 'pendente',
    'status_local' => $statusLocal,
    'pagamento_id' => $pagamentoId,
]);
