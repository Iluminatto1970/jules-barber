<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexao.php';

function saas_checkout_json($ok, $dados = [])
{
    $base = ['ok' => (bool) $ok];
    if (function_exists('saas_pagbank_csrf_token') && session_status() === PHP_SESSION_ACTIVE) {
        $base['csrf_token'] = saas_pagbank_csrf_token('saas_checkout_csrf');
    }

    echo json_encode(array_merge($base, $dados), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function saas_checkout_fail($mensagem, $http = 400, $extras = [])
{
    http_response_code((int) $http);
    saas_checkout_json(false, array_merge([
        'mensagem' => (string) $mensagem,
    ], $extras));
}

function saas_checkout_sanitizar_idempotency($valor)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }

    $valor = preg_replace('/[^a-zA-Z0-9\-_]/', '', $valor);
    if (strlen((string) $valor) < 10) {
        return '';
    }

    if (strlen((string) $valor) > 120) {
        $valor = substr((string) $valor, 0, 120);
    }

    return (string) $valor;
}

function saas_checkout_buscar_idempotencia($pdoSaas, $empresaId, $idempotencyKey)
{
    if (!$pdoSaas instanceof PDO || (int) $empresaId <= 0 || trim((string) $idempotencyKey) === '') {
        return null;
    }

    try {
        $query = $pdoSaas->prepare("SELECT * FROM empresas_pagamentos WHERE empresa_id = :empresa_id AND idempotency_key = :idempotency_key LIMIT 1");
        $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
        $query->bindValue(':idempotency_key', (string) $idempotencyKey);
        $query->execute();
        $pagamento = $query->fetch(PDO::FETCH_ASSOC);
        return $pagamento ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function saas_checkout_obter_assinatura($pdoSaas, $empresaId)
{
    $query = $pdoSaas->prepare("SELECT
            a.id AS assinatura_id,
            a.plano_id,
            a.status,
            a.trial_ate,
            a.ciclo_ate,
            p.nome AS plano_nome,
            p.slug AS plano_slug,
            p.valor_mensal
        FROM empresas_assinaturas a
        LEFT JOIN planos p ON p.id = a.plano_id
        WHERE a.empresa_id = :empresa_id
        LIMIT 1");
    $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
    $query->execute();
    $assinatura = $query->fetch(PDO::FETCH_ASSOC);

    if (!$assinatura) {
        saas_garantir_assinatura_empresa($pdoSaas, $empresaId, 'starter', 'Trial', 14);
        $query->execute();
        $assinatura = $query->fetch(PDO::FETCH_ASSOC);
    }

    if (!$assinatura) {
        return null;
    }

    $valorPadrao = (float) saas_pagbank_env('SAAS_PLAN_DEFAULT_VALUE', '79.90');
    $valor = isset($assinatura['valor_mensal']) ? (float) $assinatura['valor_mensal'] : 0;
    if ($valor <= 0) {
        $valor = $valorPadrao > 0 ? $valorPadrao : 79.90;
    }

    $assinatura['valor_mensal'] = $valor;

    return $assinatura;
}

function saas_checkout_obter_responsavel($pdoTenant, $pdoSaas, $usuarioId, $empresaId)
{
    $dados = [
        'nome' => trim((string) ($_SESSION['nome'] ?? 'Responsavel SaaS')),
        'email' => '',
        'cpf' => '',
    ];

    if ($pdoTenant instanceof PDO && (int) $usuarioId > 0) {
        try {
            $queryUsuario = $pdoTenant->prepare("SELECT nome, email, cpf FROM usuarios WHERE id = :id LIMIT 1");
            $queryUsuario->bindValue(':id', (int) $usuarioId, PDO::PARAM_INT);
            $queryUsuario->execute();
            $usuario = $queryUsuario->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                if (!empty($usuario['nome'])) {
                    $dados['nome'] = trim((string) $usuario['nome']);
                }
                if (!empty($usuario['email'])) {
                    $dados['email'] = trim((string) $usuario['email']);
                }
                if (!empty($usuario['cpf'])) {
                    $dados['cpf'] = trim((string) $usuario['cpf']);
                }
            }
        } catch (Exception $e) {
            // fallback para cadastro SaaS
        }
    }

    if ($dados['email'] === '' && $pdoSaas instanceof PDO) {
        try {
            $queryContato = $pdoSaas->prepare("SELECT nome, email FROM empresas_usuarios WHERE empresa_id = :empresa_id ORDER BY id ASC LIMIT 1");
            $queryContato->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
            $queryContato->execute();
            $contato = $queryContato->fetch(PDO::FETCH_ASSOC);
            if ($contato) {
                if (!empty($contato['nome']) && $dados['nome'] === '') {
                    $dados['nome'] = trim((string) $contato['nome']);
                }
                if (!empty($contato['email'])) {
                    $dados['email'] = trim((string) $contato['email']);
                }
            }
        } catch (Exception $e) {
            // segue com fallback
        }
    }

    if ($dados['nome'] === '') {
        $dados['nome'] = 'Responsavel SaaS';
    }

    if ($dados['email'] === '') {
        $dados['email'] = 'financeiro@barbearia.local';
    }

    return $dados;
}

function saas_checkout_resumo_assinatura($pdoSaas, $empresaId)
{
    $query = $pdoSaas->prepare("SELECT status, trial_ate, ciclo_ate FROM empresas_assinaturas WHERE empresa_id = :empresa_id LIMIT 1");
    $query->bindValue(':empresa_id', (int) $empresaId, PDO::PARAM_INT);
    $query->execute();
    $assinatura = $query->fetch(PDO::FETCH_ASSOC);

    if (!$assinatura) {
        return [
            'status' => 'Trial',
            'trial_ate' => null,
            'ciclo_ate' => null,
        ];
    }

    return [
        'status' => (string) ($assinatura['status'] ?? 'Trial'),
        'trial_ate' => !empty($assinatura['trial_ate']) ? (string) $assinatura['trial_ate'] : null,
        'ciclo_ate' => !empty($assinatura['ciclo_ate']) ? (string) $assinatura['ciclo_ate'] : null,
    ];
}

function saas_checkout_status_local($statusApi)
{
    return saas_pagbank_status_local($statusApi);
}

function saas_checkout_sanitizar_payload_cartao($payload)
{
    if (!is_array($payload)) {
        return [];
    }

    $safe = $payload;
    if (!empty($safe['charges']) && is_array($safe['charges'])) {
        foreach ($safe['charges'] as $idx => $charge) {
            if (!is_array($charge)) {
                continue;
            }

            if (!empty($charge['payment_method']) && is_array($charge['payment_method'])) {
                if (!empty($charge['payment_method']['card'])) {
                    unset($safe['charges'][$idx]['payment_method']['card']);
                    $safe['charges'][$idx]['payment_method']['card_sent'] = true;
                }
            }
        }
    }

    return $safe;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    saas_checkout_fail('Metodo invalido.', 405);
}

if (!isset($_SESSION['id']) || (int) $_SESSION['id'] <= 0) {
    saas_checkout_fail('Sessao expirada. Faça login novamente.', 401);
}

if (!$pdo_saas instanceof PDO) {
    saas_checkout_fail('Servico SaaS indisponivel no momento.', 503);
}

saas_pagbank_garantir_estrutura($pdo_saas);

$empresaId = isset($_SESSION['empresa_id']) ? (int) $_SESSION['empresa_id'] : 0;
if ($empresaId <= 0) {
    saas_checkout_fail('Empresa nao identificada para checkout.', 422);
}

$usuarioId = (int) $_SESSION['id'];
$acao = trim((string) ($_POST['acao'] ?? 'gerar_pix'));

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!saas_pagbank_csrf_validar($csrfToken, 'saas_checkout_csrf')) {
    saas_checkout_fail('Sessao de checkout expirada. Atualize a pagina e tente novamente.', 419);
}

if ($acao === 'gerar_pix') {
    $assinatura = saas_checkout_obter_assinatura($pdo_saas, $empresaId);
    if (!$assinatura) {
        saas_checkout_fail('Assinatura da empresa nao encontrada.', 404);
    }

    $idempotencyKey = saas_checkout_sanitizar_idempotency($_POST['idempotency_key'] ?? '');
    if ($idempotencyKey === '') {
        $idempotencyKey = 'pix_' . $empresaId . '_' . bin2hex(random_bytes(10));
    }

    $pagamentoIdempotente = saas_checkout_buscar_idempotencia($pdo_saas, $empresaId, $idempotencyKey);
    if ($pagamentoIdempotente) {
        saas_checkout_json(true, [
            'mensagem' => 'Pagamento recuperado por idempotencia.',
            'pagamento_id' => (int) $pagamentoIdempotente['id'],
            'referencia' => (string) $pagamentoIdempotente['pedido_referencia'],
            'valor' => (float) $pagamentoIdempotente['valor'],
            'pix_copia_cola' => (string) ($pagamentoIdempotente['qr_code_text'] ?? ''),
            'qr_code_link' => (string) ($pagamentoIdempotente['qr_code_link'] ?? ''),
            'expiracao' => !empty($pagamentoIdempotente['expiracao_em']) ? (string) $pagamentoIdempotente['expiracao_em'] : null,
            'status_pagamento' => (string) $pagamentoIdempotente['status'],
            'metodo_pagamento' => (string) ($pagamentoIdempotente['metodo_pagamento'] ?? 'PIX'),
            'simulacao' => !empty(saas_pagbank_config()['simulation']),
            'idempotente' => true,
        ]);
    }

    $queryPendente = $pdo_saas->prepare("SELECT id, pedido_referencia, status, valor, qr_code_text, qr_code_link, expiracao_em, metodo_pagamento
        FROM empresas_pagamentos
        WHERE empresa_id = :empresa_id
          AND gateway = 'pagbank'
          AND metodo_pagamento = 'PIX'
          AND status = 'Pendente'
          AND (expiracao_em IS NULL OR expiracao_em >= NOW())
        ORDER BY id DESC
        LIMIT 1");
    $queryPendente->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $queryPendente->execute();
    $pagamentoPendente = $queryPendente->fetch(PDO::FETCH_ASSOC);

    if ($pagamentoPendente) {
        saas_checkout_json(true, [
            'mensagem' => 'Ja existe um PIX pendente para esta assinatura.',
            'pagamento_id' => (int) $pagamentoPendente['id'],
            'referencia' => (string) $pagamentoPendente['pedido_referencia'],
            'valor' => (float) $pagamentoPendente['valor'],
            'pix_copia_cola' => (string) ($pagamentoPendente['qr_code_text'] ?? ''),
            'qr_code_link' => (string) ($pagamentoPendente['qr_code_link'] ?? ''),
            'expiracao' => !empty($pagamentoPendente['expiracao_em']) ? (string) $pagamentoPendente['expiracao_em'] : null,
            'status_pagamento' => (string) $pagamentoPendente['status'],
            'metodo_pagamento' => (string) ($pagamentoPendente['metodo_pagamento'] ?? 'PIX'),
            'simulacao' => saas_pagbank_config()['simulation'],
            'existente' => true,
        ]);
    }

    $valor = (float) $assinatura['valor_mensal'];
    if ($valor <= 0) {
        saas_checkout_fail('Valor do plano invalido para cobranca PIX.', 422);
    }

    $valorCentavos = (int) round($valor * 100);
    $referencia = 'BARB_' . $empresaId . '_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
    $config = saas_pagbank_config();
    $expiracaoMinutos = max(30, (int) ($config['expire_pending_minutes'] ?? 1440));
    $expiracaoTimestamp = time() + ($expiracaoMinutos * 60);
    $expiracaoSql = date('Y-m-d H:i:s', $expiracaoTimestamp);

    if (!empty($config['simulation'])) {
        $pixSimulado = '00020101021226790014BR.GOV.BCB.PIX0136' . strtoupper(substr(sha1($referencia), 0, 36)) . '5204000053039865802BR5925BARBEARIA SAAS SIMULACAO6009SAO PAULO62070503***6304ABCD';

        $insert = $pdo_saas->prepare("INSERT INTO empresas_pagamentos
            (empresa_id, assinatura_id, plano_id, gateway, metodo_pagamento, pedido_referencia, idempotency_key, status, status_detalhe, valor, moeda, qr_code_text, expiracao_em, payload_criacao)
            VALUES (:empresa_id, :assinatura_id, :plano_id, 'pagbank', 'PIX', :pedido_referencia, :idempotency_key, 'Pendente', 'simulated_pending', :valor, 'BRL', :qr_code_text, :expiracao_em, :payload)");
        $insert->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
        $insert->bindValue(':assinatura_id', (int) $assinatura['assinatura_id'], PDO::PARAM_INT);
        $insert->bindValue(':plano_id', (int) $assinatura['plano_id'], PDO::PARAM_INT);
        $insert->bindValue(':pedido_referencia', $referencia);
        $insert->bindValue(':idempotency_key', $idempotencyKey);
        $insert->bindValue(':valor', $valor);
        $insert->bindValue(':qr_code_text', $pixSimulado);
        $insert->bindValue(':expiracao_em', $expiracaoSql);
        $insert->bindValue(':payload', json_encode(['simulation' => true], JSON_UNESCAPED_UNICODE));
        $insert->execute();

        saas_checkout_json(true, [
            'mensagem' => 'PIX simulado gerado com sucesso.',
            'pagamento_id' => (int) $pdo_saas->lastInsertId(),
            'referencia' => $referencia,
            'valor' => $valor,
            'pix_copia_cola' => $pixSimulado,
            'qr_code_link' => '',
            'expiracao' => $expiracaoSql,
            'status_pagamento' => 'Pendente',
            'metodo_pagamento' => 'PIX',
            'simulacao' => true,
            'idempotente' => false,
        ]);
    }

    $responsavel = saas_checkout_obter_responsavel($pdo, $pdo_saas, $usuarioId, $empresaId);
    $cpf = saas_pagbank_normalizar_cpf($responsavel['cpf'] ?? '');
    if ($cpf === '') {
        saas_checkout_fail('CPF do responsavel nao encontrado. Atualize o cadastro antes de gerar o PIX.', 422);
    }

    if (!filter_var((string) ($responsavel['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        saas_checkout_fail('Email do responsavel invalido para cobranca. Atualize o cadastro e tente novamente.', 422);
    }

    $auth = saas_pagbank_obter_auth_header($config);
    if (empty($auth['ok'])) {
        saas_checkout_fail('PagBank nao configurado corretamente. Verifique as credenciais no ambiente.', 500);
    }

    $api = saas_pagbank_api_base((string) $config['modo']);
    $payloadPix = [
        'reference_id' => $referencia,
        'customer' => [
            'name' => (string) $responsavel['nome'],
            'email' => (string) $responsavel['email'],
            'tax_id' => $cpf,
        ],
        'qr_codes' => [
            [
                'amount' => [
                    'value' => $valorCentavos,
                    'currency' => 'BRL',
                ],
                'expiration_date' => gmdate('Y-m-d\TH:i:s\Z', $expiracaoTimestamp),
                'payment_methods' => ['PIX'],
            ],
        ],
    ];

    $resposta = saas_pagbank_request(
        'POST',
        $api . '/orders',
        [
            'Authorization: ' . $auth['header'],
            'Content-Type: application/json',
            'Accept: application/json',
            'x-idempotency-key: ' . $idempotencyKey,
        ],
        json_encode($payloadPix, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        35
    );

    if (!$resposta['ok'] || !is_array($resposta['body'])) {
        saas_checkout_fail('Falha ao gerar PIX no PagBank.', 502, [
            'detalhes' => is_array($resposta['body']) ? $resposta['body'] : $resposta['raw'],
        ]);
    }

    $corpo = $resposta['body'];
    $pagbankOrderId = trim((string) ($corpo['id'] ?? ''));
    $qrCode = [];
    if (!empty($corpo['qr_codes']) && is_array($corpo['qr_codes']) && isset($corpo['qr_codes'][0]) && is_array($corpo['qr_codes'][0])) {
        $qrCode = $corpo['qr_codes'][0];
    }

    $pixCopiaCola = trim((string) ($qrCode['text'] ?? ''));
    if ($pixCopiaCola === '') {
        saas_checkout_fail('PagBank respondeu sem codigo PIX para pagamento.', 502, ['detalhes' => $corpo]);
    }

    $qrCodeLink = '';
    if (!empty($qrCode['links']) && is_array($qrCode['links'])) {
        foreach ($qrCode['links'] as $link) {
            if (!is_array($link)) {
                continue;
            }

            $href = trim((string) ($link['href'] ?? ''));
            $rel = strtolower(trim((string) ($link['rel'] ?? '')));
            if ($href === '') {
                continue;
            }

            if ($qrCodeLink === '' || strpos($rel, 'qrcode') !== false) {
                $qrCodeLink = $href;
            }
        }
    }

    $expiracaoApi = trim((string) ($qrCode['expiration_date'] ?? ''));
    if ($expiracaoApi !== '' && strtotime($expiracaoApi)) {
        $expiracaoSql = date('Y-m-d H:i:s', strtotime($expiracaoApi));
    }

    $insert = $pdo_saas->prepare("INSERT INTO empresas_pagamentos
        (empresa_id, assinatura_id, plano_id, gateway, metodo_pagamento, pedido_referencia, idempotency_key, pagbank_order_id, status, status_detalhe, valor, moeda, qr_code_text, qr_code_link, expiracao_em, payload_criacao)
        VALUES (:empresa_id, :assinatura_id, :plano_id, 'pagbank', 'PIX', :pedido_referencia, :idempotency_key, :pagbank_order_id, 'Pendente', :status_detalhe, :valor, 'BRL', :qr_code_text, :qr_code_link, :expiracao_em, :payload)");
    $insert->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $insert->bindValue(':assinatura_id', (int) $assinatura['assinatura_id'], PDO::PARAM_INT);
    $insert->bindValue(':plano_id', (int) $assinatura['plano_id'], PDO::PARAM_INT);
    $insert->bindValue(':pedido_referencia', $referencia);
    $insert->bindValue(':idempotency_key', $idempotencyKey);
    $insert->bindValue(':pagbank_order_id', $pagbankOrderId !== '' ? $pagbankOrderId : null, $pagbankOrderId !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $insert->bindValue(':status_detalhe', saas_pagbank_extrair_status_ordem($corpo) ?: 'pending');
    $insert->bindValue(':valor', $valor);
    $insert->bindValue(':qr_code_text', $pixCopiaCola);
    $insert->bindValue(':qr_code_link', $qrCodeLink !== '' ? $qrCodeLink : null, $qrCodeLink !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $insert->bindValue(':expiracao_em', $expiracaoSql);
    $insert->bindValue(':payload', json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $insert->execute();

    saas_checkout_json(true, [
        'mensagem' => 'PIX gerado com sucesso.',
        'pagamento_id' => (int) $pdo_saas->lastInsertId(),
        'referencia' => $referencia,
        'valor' => $valor,
        'pix_copia_cola' => $pixCopiaCola,
        'qr_code_link' => $qrCodeLink,
        'expiracao' => $expiracaoSql,
        'status_pagamento' => 'Pendente',
        'metodo_pagamento' => 'PIX',
        'simulacao' => false,
        'idempotente' => false,
    ]);
}

if ($acao === 'pagar_cartao') {
    $assinatura = saas_checkout_obter_assinatura($pdo_saas, $empresaId);
    if (!$assinatura) {
        saas_checkout_fail('Assinatura da empresa nao encontrada.', 404);
    }

    $valor = (float) $assinatura['valor_mensal'];
    if ($valor <= 0) {
        saas_checkout_fail('Valor do plano invalido para cobranca no cartao.', 422);
    }

    $valorCentavos = (int) round($valor * 100);
    $config = saas_pagbank_config();
    $idempotencyKey = saas_checkout_sanitizar_idempotency($_POST['idempotency_key'] ?? '');
    if ($idempotencyKey === '') {
        $idempotencyKey = 'card_' . $empresaId . '_' . bin2hex(random_bytes(10));
    }

    $pagamentoIdempotente = saas_checkout_buscar_idempotencia($pdo_saas, $empresaId, $idempotencyKey);
    if ($pagamentoIdempotente) {
        $resumoAssinatura = saas_checkout_resumo_assinatura($pdo_saas, $empresaId);
        saas_checkout_json(true, [
            'mensagem' => 'Pagamento recuperado por idempotencia.',
            'pagamento_id' => (int) $pagamentoIdempotente['id'],
            'referencia' => (string) $pagamentoIdempotente['pedido_referencia'],
            'valor' => (float) $pagamentoIdempotente['valor'],
            'status_pagamento' => (string) $pagamentoIdempotente['status'],
            'metodo_pagamento' => (string) ($pagamentoIdempotente['metodo_pagamento'] ?? 'Cartao'),
            'simulacao' => !empty($config['simulation']),
            'assinatura_status' => $resumoAssinatura['status'],
            'assinatura_trial_ate' => $resumoAssinatura['trial_ate'],
            'assinatura_ciclo_ate' => $resumoAssinatura['ciclo_ate'],
            'idempotente' => true,
        ]);
    }

    $referencia = 'BARB_CARD_' . $empresaId . '_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);

    if (!empty($config['simulation'])) {
        $insertSim = $pdo_saas->prepare("INSERT INTO empresas_pagamentos
            (empresa_id, assinatura_id, plano_id, gateway, metodo_pagamento, pedido_referencia, idempotency_key, status, status_detalhe, valor, moeda, payload_criacao)
            VALUES (:empresa_id, :assinatura_id, :plano_id, 'pagbank', 'Cartao', :pedido_referencia, :idempotency_key, 'Pendente', 'simulated_pending', :valor, 'BRL', :payload)");
        $insertSim->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
        $insertSim->bindValue(':assinatura_id', (int) $assinatura['assinatura_id'], PDO::PARAM_INT);
        $insertSim->bindValue(':plano_id', (int) $assinatura['plano_id'], PDO::PARAM_INT);
        $insertSim->bindValue(':pedido_referencia', $referencia);
        $insertSim->bindValue(':idempotency_key', $idempotencyKey);
        $insertSim->bindValue(':valor', $valor);
        $insertSim->bindValue(':payload', json_encode(['simulation' => true, 'method' => 'card'], JSON_UNESCAPED_UNICODE));
        $insertSim->execute();

        $pagamentoIdSim = (int) $pdo_saas->lastInsertId();
        $confirmacao = saas_pagbank_marcar_pagamento_pago($pdo_saas, $pagamentoIdSim, ['simulation' => true, 'method' => 'card'], 'simulacao_cartao');
        if (empty($confirmacao['ok'])) {
            saas_checkout_fail($confirmacao['message'] ?? 'Falha ao confirmar pagamento simulado no cartao.', 500);
        }

        $resumoAssinatura = saas_checkout_resumo_assinatura($pdo_saas, $empresaId);
        saas_checkout_json(true, [
            'mensagem' => 'Pagamento com cartao simulado e aprovado.',
            'pagamento_id' => $pagamentoIdSim,
            'referencia' => $referencia,
            'valor' => $valor,
            'status_pagamento' => 'Pago',
            'metodo_pagamento' => 'Cartao',
            'simulacao' => true,
            'assinatura_status' => $resumoAssinatura['status'],
            'assinatura_trial_ate' => $resumoAssinatura['trial_ate'],
            'assinatura_ciclo_ate' => $resumoAssinatura['ciclo_ate'],
            'idempotente' => false,
        ]);
    }

    $responsavel = saas_checkout_obter_responsavel($pdo, $pdo_saas, $usuarioId, $empresaId);

    $holderName = trim((string) ($_POST['holder_name'] ?? $responsavel['nome']));
    $holderTaxId = saas_pagbank_normalizar_cpf((string) ($_POST['holder_tax_id'] ?? $responsavel['cpf'] ?? ''));
    if ($holderName === '') {
        $holderName = (string) $responsavel['nome'];
    }
    if ($holderTaxId === '') {
        $holderTaxId = saas_pagbank_normalizar_cpf($responsavel['cpf'] ?? '');
    }
    if ($holderTaxId === '') {
        saas_checkout_fail('CPF do titular do cartao invalido. Atualize o cadastro e tente novamente.', 422);
    }

    if (!filter_var((string) ($responsavel['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        saas_checkout_fail('Email do responsavel invalido para cobranca no cartao.', 422);
    }

    $installments = isset($_POST['installments']) ? (int) $_POST['installments'] : 1;
    if ($installments < 1) {
        $installments = 1;
    }
    if ($installments > 12) {
        $installments = 12;
    }

    $cardEncrypted = trim((string) ($_POST['card_encrypted'] ?? ''));
    $cardNumber = preg_replace('/[^0-9]/', '', (string) ($_POST['card_number'] ?? ''));
    $cardExpMonth = preg_replace('/[^0-9]/', '', (string) ($_POST['card_exp_month'] ?? ''));
    $cardExpYear = preg_replace('/[^0-9]/', '', (string) ($_POST['card_exp_year'] ?? ''));
    $cardCvv = preg_replace('/[^0-9]/', '', (string) ($_POST['card_cvv'] ?? ''));

    $allowPlainCard = !empty($config['allow_plain_card']);
    $requireCardToken = !empty($config['require_card_token']) && !$allowPlainCard;
    if ($requireCardToken && $cardEncrypted === '') {
        saas_checkout_fail('Token de cartao obrigatorio. Ative a tokenizacao no frontend para pagar com seguranca.', 422);
    }

    if (!$allowPlainCard && $cardEncrypted === '') {
        saas_checkout_fail('Pagamento sem tokenizacao esta bloqueado por seguranca.', 422);
    }

    if (strlen($cardExpYear) === 2) {
        $cardExpYear = '20' . $cardExpYear;
    }

    if ($cardEncrypted === '') {
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            saas_checkout_fail('Numero do cartao invalido.', 422);
        }
        if ((int) $cardExpMonth < 1 || (int) $cardExpMonth > 12) {
            saas_checkout_fail('Mes de validade do cartao invalido.', 422);
        }
        if (strlen($cardExpYear) !== 4) {
            saas_checkout_fail('Ano de validade do cartao invalido.', 422);
        }
        if (strlen($cardCvv) < 3 || strlen($cardCvv) > 4) {
            saas_checkout_fail('Codigo de seguranca do cartao invalido.', 422);
        }
    }

    $auth = saas_pagbank_obter_auth_header($config);
    if (empty($auth['ok'])) {
        saas_checkout_fail('PagBank nao configurado corretamente. Verifique as credenciais no ambiente.', 500);
    }

    $api = saas_pagbank_api_base((string) $config['modo']);
    $payloadCard = [
        'reference_id' => $referencia,
        'customer' => [
            'name' => (string) $responsavel['nome'],
            'email' => (string) $responsavel['email'],
            'tax_id' => $holderTaxId,
        ],
        'charges' => [
            [
                'reference_id' => $referencia,
                'description' => 'Renovacao da assinatura SaaS',
                'amount' => [
                    'value' => $valorCentavos,
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'installments' => $installments,
                    'capture' => true,
                    'holder' => [
                        'name' => $holderName,
                        'tax_id' => $holderTaxId,
                    ],
                ],
            ],
        ],
    ];

    if ($cardEncrypted !== '') {
        $payloadCard['charges'][0]['payment_method']['card'] = [
            'encrypted' => $cardEncrypted,
        ];
    } else {
        $payloadCard['charges'][0]['payment_method']['card'] = [
            'number' => $cardNumber,
            'exp_month' => str_pad($cardExpMonth, 2, '0', STR_PAD_LEFT),
            'exp_year' => $cardExpYear,
            'security_code' => $cardCvv,
        ];
    }

    $resposta = saas_pagbank_request(
        'POST',
        $api . '/orders',
        [
            'Authorization: ' . $auth['header'],
            'Content-Type: application/json',
            'Accept: application/json',
            'x-idempotency-key: ' . $idempotencyKey,
        ],
        json_encode($payloadCard, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        35
    );

    if (!$resposta['ok'] || !is_array($resposta['body'])) {
        saas_checkout_fail('Falha ao processar pagamento no cartao.', 502, [
            'detalhes' => is_array($resposta['body']) ? $resposta['body'] : $resposta['raw'],
        ]);
    }

    $corpo = $resposta['body'];
    $pagbankOrderId = trim((string) ($corpo['id'] ?? ''));
    $statusApi = saas_pagbank_extrair_status_ordem($corpo);
    $statusLocal = saas_checkout_status_local($statusApi);

    $payloadSanitizado = saas_checkout_sanitizar_payload_cartao($payloadCard);
    $insert = $pdo_saas->prepare("INSERT INTO empresas_pagamentos
        (empresa_id, assinatura_id, plano_id, gateway, metodo_pagamento, pedido_referencia, idempotency_key, pagbank_order_id, status, status_detalhe, valor, moeda, payload_criacao, payload_status)
        VALUES (:empresa_id, :assinatura_id, :plano_id, 'pagbank', 'Cartao', :pedido_referencia, :idempotency_key, :pagbank_order_id, :status, :status_detalhe, :valor, 'BRL', :payload_criacao, :payload_status)");
    $insert->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $insert->bindValue(':assinatura_id', (int) $assinatura['assinatura_id'], PDO::PARAM_INT);
    $insert->bindValue(':plano_id', (int) $assinatura['plano_id'], PDO::PARAM_INT);
    $insert->bindValue(':pedido_referencia', $referencia);
    $insert->bindValue(':idempotency_key', $idempotencyKey);
    $insert->bindValue(':pagbank_order_id', $pagbankOrderId !== '' ? $pagbankOrderId : null, $pagbankOrderId !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $insert->bindValue(':status', $statusLocal);
    $insert->bindValue(':status_detalhe', $statusApi !== '' ? $statusApi : null, $statusApi !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $insert->bindValue(':valor', $valor);
    $insert->bindValue(':payload_criacao', json_encode($payloadSanitizado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $insert->bindValue(':payload_status', json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $insert->execute();

    $pagamentoId = (int) $pdo_saas->lastInsertId();
    if ($statusLocal === 'Pago') {
        $confirmacao = saas_pagbank_marcar_pagamento_pago($pdo_saas, $pagamentoId, $corpo, 'pagamento_cartao');
        if (empty($confirmacao['ok'])) {
            saas_checkout_fail($confirmacao['message'] ?? 'Falha ao renovar assinatura apos pagamento.', 500);
        }
    } elseif (in_array($statusLocal, ['Cancelado', 'Expirado', 'Falha'], true)) {
        saas_pagbank_atualizar_status_pagamento(
            $pdo_saas,
            $pagamentoId,
            $statusLocal,
            $statusApi,
            $corpo,
            'consulta_status',
            'Status retornado pelo gateway no checkout de cartao'
        );
    }

    $resumoAssinatura = saas_checkout_resumo_assinatura($pdo_saas, $empresaId);
    saas_checkout_json(true, [
        'mensagem' => $statusLocal === 'Pago'
            ? 'Pagamento no cartao aprovado e assinatura renovada.'
            : 'Pagamento com cartao enviado para processamento.',
        'pagamento_id' => $pagamentoId,
        'referencia' => $referencia,
        'valor' => $valor,
        'status_pagamento' => $statusLocal,
        'metodo_pagamento' => 'Cartao',
        'simulacao' => false,
        'assinatura_status' => $resumoAssinatura['status'],
        'assinatura_trial_ate' => $resumoAssinatura['trial_ate'],
        'assinatura_ciclo_ate' => $resumoAssinatura['ciclo_ate'],
        'idempotente' => false,
    ]);
}

if (in_array($acao, ['status', 'simular_pagamento', 'cancelar_pagamento', 'solicitar_estorno'], true)) {
    $pagamentoId = isset($_POST['pagamento_id']) ? (int) $_POST['pagamento_id'] : 0;
    if ($pagamentoId <= 0) {
        saas_checkout_fail('Pagamento invalido para consulta.', 422);
    }

    $query = $pdo_saas->prepare("SELECT * FROM empresas_pagamentos WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
    $query->bindValue(':id', $pagamentoId, PDO::PARAM_INT);
    $query->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $query->execute();
    $pagamento = $query->fetch(PDO::FETCH_ASSOC);

    if (!$pagamento) {
        saas_checkout_fail('Pagamento nao encontrado para esta empresa.', 404);
    }

    $config = saas_pagbank_config();

    if ($acao === 'cancelar_pagamento') {
        if ((string) $pagamento['status'] === 'Pago') {
            saas_checkout_fail('Pagamento ja confirmado. Use a opcao de estorno.', 422);
        }

        if (in_array((string) $pagamento['status'], ['Cancelado', 'Expirado'], true)) {
            saas_checkout_json(true, [
                'mensagem' => 'Pagamento ja estava cancelado/expirado.',
                'pagamento_id' => (int) $pagamento['id'],
                'status_pagamento' => (string) $pagamento['status'],
                'metodo_pagamento' => (string) ($pagamento['metodo_pagamento'] ?? (!empty($pagamento['qr_code_text']) ? 'PIX' : 'Cartao')),
                'simulacao' => !empty($config['simulation']),
            ]);
        }

        if (empty($config['simulation']) && !empty($pagamento['pagbank_order_id'])) {
            $auth = saas_pagbank_obter_auth_header($config);
            if (empty($auth['ok'])) {
                saas_checkout_fail('Nao foi possivel autenticar no PagBank para cancelamento.', 502);
            }

            $cancelamento = saas_pagbank_cancelar_order($config, (string) $auth['header'], (string) $pagamento['pagbank_order_id']);
            if (empty($cancelamento['ok'])) {
                saas_checkout_fail($cancelamento['message'] ?? 'PagBank nao confirmou cancelamento automatico.', 502);
            }

            saas_pagbank_atualizar_status_pagamento(
                $pdo_saas,
                $pagamentoId,
                'Cancelado',
                'canceled',
                is_array($cancelamento['body'] ?? null) ? (array) $cancelamento['body'] : [],
                'consulta_status',
                'Pagamento cancelado no painel da assinatura'
            );
        } else {
            saas_pagbank_atualizar_status_pagamento(
                $pdo_saas,
                $pagamentoId,
                'Cancelado',
                'manual_cancel',
                ['manual' => true],
                'consulta_status',
                'Pagamento cancelado manualmente'
            );
        }

        $query->execute();
        $pagamentoAtualizado = $query->fetch(PDO::FETCH_ASSOC);
        $resumoAssinatura = saas_checkout_resumo_assinatura($pdo_saas, $empresaId);

        saas_checkout_json(true, [
            'mensagem' => 'Pagamento cancelado com sucesso.',
            'pagamento_id' => (int) $pagamentoAtualizado['id'],
            'status_pagamento' => (string) $pagamentoAtualizado['status'],
            'valor' => (float) $pagamentoAtualizado['valor'],
            'referencia' => (string) $pagamentoAtualizado['pedido_referencia'],
            'metodo_pagamento' => (string) ($pagamentoAtualizado['metodo_pagamento'] ?? (!empty($pagamentoAtualizado['qr_code_text']) ? 'PIX' : 'Cartao')),
            'pago_em' => !empty($pagamentoAtualizado['pago_em']) ? (string) $pagamentoAtualizado['pago_em'] : null,
            'assinatura_status' => $resumoAssinatura['status'],
            'assinatura_trial_ate' => $resumoAssinatura['trial_ate'],
            'assinatura_ciclo_ate' => $resumoAssinatura['ciclo_ate'],
            'simulacao' => !empty($config['simulation']),
        ]);
    }

    if ($acao === 'solicitar_estorno') {
        if ((string) $pagamento['status'] !== 'Pago') {
            saas_checkout_fail('Somente pagamentos confirmados podem ser estornados.', 422);
        }

        $detalheEstorno = 'Solicitacao de estorno para pagamento #' . (int) $pagamento['id'];

        if (empty($config['auto_refund'])) {
            saas_pagbank_registrar_evento_billing($pdo_saas, $empresaId, 'estorno_solicitado', 'pagamento', $detalheEstorno . ' (processamento manual)');
            saas_checkout_json(true, [
                'mensagem' => 'Solicitacao de estorno registrada para processamento manual.',
                'pagamento_id' => (int) $pagamento['id'],
                'status_pagamento' => (string) $pagamento['status'],
                'metodo_pagamento' => (string) ($pagamento['metodo_pagamento'] ?? (!empty($pagamento['qr_code_text']) ? 'PIX' : 'Cartao')),
                'simulacao' => !empty($config['simulation']),
            ]);
        }

        if (empty($pagamento['pagbank_order_id'])) {
            saas_checkout_fail('Pagamento sem order_id no PagBank. Estorno automatico indisponivel.', 422);
        }

        $auth = saas_pagbank_obter_auth_header($config);
        if (empty($auth['ok'])) {
            saas_checkout_fail('Nao foi possivel autenticar no PagBank para estorno.', 502);
        }

        $estorno = saas_pagbank_solicitar_estorno_order(
            $config,
            (string) $auth['header'],
            (string) $pagamento['pagbank_order_id'],
            (int) round(((float) $pagamento['valor']) * 100)
        );

        if (empty($estorno['ok'])) {
            saas_checkout_fail($estorno['message'] ?? 'PagBank nao confirmou estorno automatico.', 502);
        }

        saas_pagbank_atualizar_status_pagamento(
            $pdo_saas,
            $pagamentoId,
            'Cancelado',
            'refunded',
            is_array($estorno['body'] ?? null) ? (array) $estorno['body'] : [],
            'consulta_status',
            'Pagamento estornado via painel da assinatura'
        );
        saas_pagbank_registrar_evento_billing($pdo_saas, $empresaId, 'estorno_confirmado', 'pagamento', $detalheEstorno);

        $query->execute();
        $pagamentoAtualizado = $query->fetch(PDO::FETCH_ASSOC);
        $resumoAssinatura = saas_checkout_resumo_assinatura($pdo_saas, $empresaId);

        saas_checkout_json(true, [
            'mensagem' => 'Estorno solicitado e confirmado pelo gateway.',
            'pagamento_id' => (int) $pagamentoAtualizado['id'],
            'status_pagamento' => (string) $pagamentoAtualizado['status'],
            'valor' => (float) $pagamentoAtualizado['valor'],
            'referencia' => (string) $pagamentoAtualizado['pedido_referencia'],
            'metodo_pagamento' => (string) ($pagamentoAtualizado['metodo_pagamento'] ?? (!empty($pagamentoAtualizado['qr_code_text']) ? 'PIX' : 'Cartao')),
            'pago_em' => !empty($pagamentoAtualizado['pago_em']) ? (string) $pagamentoAtualizado['pago_em'] : null,
            'assinatura_status' => $resumoAssinatura['status'],
            'assinatura_trial_ate' => $resumoAssinatura['trial_ate'],
            'assinatura_ciclo_ate' => $resumoAssinatura['ciclo_ate'],
            'simulacao' => !empty($config['simulation']),
        ]);
    }

    if ($acao === 'simular_pagamento') {
        if (empty($config['simulation'])) {
            saas_checkout_fail('Simulacao desativada no ambiente.', 403);
        }

        $confirmacao = saas_pagbank_marcar_pagamento_pago($pdo_saas, $pagamentoId, ['simulacao' => true], 'simulacao');
        if (empty($confirmacao['ok'])) {
            saas_checkout_fail($confirmacao['message'] ?? 'Falha ao simular pagamento.', 500);
        }
    }

    saas_pagbank_incrementar_tentativa_consulta($pdo_saas, $pagamentoId);

    if ((string) $pagamento['status'] === 'Pendente' && !empty($pagamento['expiracao_em'])) {
        $expiraEm = strtotime((string) $pagamento['expiracao_em']);
        if ($expiraEm && $expiraEm < time()) {
            saas_pagbank_atualizar_status_pagamento(
                $pdo_saas,
                $pagamentoId,
                'Expirado',
                'expired',
                ['local_expired' => true],
                'consulta_status',
                'Pagamento expirado por tempo limite'
            );
        }
    }

    if ($pagamento['status'] === 'Pendente' && empty($config['simulation']) && !empty($pagamento['pagbank_order_id'])) {
        $auth = saas_pagbank_obter_auth_header($config);
        if (!empty($auth['ok'])) {
            $resposta = saas_pagbank_consultar_order($config, (string) $auth['header'], (string) $pagamento['pagbank_order_id']);

            if ($resposta['ok'] && is_array($resposta['body'])) {
                $statusApi = saas_pagbank_extrair_status_ordem($resposta['body']);
                $statusLocal = saas_pagbank_status_local($statusApi);
                saas_pagbank_atualizar_status_pagamento(
                    $pdo_saas,
                    $pagamentoId,
                    $statusLocal,
                    $statusApi,
                    $resposta['body'],
                    'consulta_status',
                    'Atualizacao automatica por consulta de status'
                );
            }
        }
    }

    $query->execute();
    $pagamentoAtualizado = $query->fetch(PDO::FETCH_ASSOC);
    $resumoAssinatura = saas_checkout_resumo_assinatura($pdo_saas, $empresaId);

    saas_checkout_json(true, [
        'pagamento_id' => (int) $pagamentoAtualizado['id'],
        'status_pagamento' => (string) $pagamentoAtualizado['status'],
        'valor' => (float) $pagamentoAtualizado['valor'],
        'referencia' => (string) $pagamentoAtualizado['pedido_referencia'],
        'metodo_pagamento' => (string) ($pagamentoAtualizado['metodo_pagamento'] ?? (!empty($pagamentoAtualizado['qr_code_text']) ? 'PIX' : 'Cartao')),
        'pago_em' => !empty($pagamentoAtualizado['pago_em']) ? (string) $pagamentoAtualizado['pago_em'] : null,
        'assinatura_status' => $resumoAssinatura['status'],
        'assinatura_trial_ate' => $resumoAssinatura['trial_ate'],
        'assinatura_ciclo_ate' => $resumoAssinatura['ciclo_ate'],
        'simulacao' => !empty($config['simulation']),
    ]);
}

saas_checkout_fail('Acao de checkout invalida.', 422);
