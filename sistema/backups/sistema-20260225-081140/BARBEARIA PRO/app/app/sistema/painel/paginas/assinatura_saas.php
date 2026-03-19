<?php
@session_start();
require_once('verificar.php');
require_once('../conexao.php');

$pag = 'assinatura_saas';

function assinatura_saas_badge_local($status)
{
    if ($status === 'Ativa') {
        return ['label-success', 'Ativa'];
    }

    if ($status === 'Trial') {
        return ['label-warning', 'Trial'];
    }

    if ($status === 'Suspensa') {
        return ['label-danger', 'Suspensa'];
    }

    if ($status === 'Cancelada') {
        return ['label-default', 'Cancelada'];
    }

    return ['label-default', $status !== '' ? $status : 'Indefinida'];
}

if (!$pdo_saas instanceof PDO) {
    ?>
    <div class="alert alert-danger">Servico de assinatura SaaS indisponivel no momento.</div>
    <?php
    return;
}

saas_pagbank_garantir_estrutura($pdo_saas);

$empresaId = isset($_SESSION['empresa_id']) ? (int) $_SESSION['empresa_id'] : 0;
$assinatura = [
    'status' => 'Trial',
    'trial_ate' => null,
    'ciclo_ate' => null,
    'plano_nome' => 'Starter',
    'valor_mensal' => (float) saas_pagbank_env('SAAS_PLAN_DEFAULT_VALUE', '79.90'),
];
$pagamentos = [];

if ($empresaId > 0) {
    $query = $pdo_saas->prepare("SELECT
            a.status,
            a.trial_ate,
            a.ciclo_ate,
            p.nome AS plano_nome,
            p.valor_mensal
        FROM empresas_assinaturas a
        LEFT JOIN planos p ON p.id = a.plano_id
        WHERE a.empresa_id = :empresa_id
        LIMIT 1");
    $query->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $query->execute();
    $resumo = $query->fetch(PDO::FETCH_ASSOC);

    if ($resumo) {
        $assinatura = array_merge($assinatura, $resumo);
    }

    $valorMensal = isset($assinatura['valor_mensal']) ? (float) $assinatura['valor_mensal'] : 0;
    if ($valorMensal <= 0) {
        $valorMensal = (float) saas_pagbank_env('SAAS_PLAN_DEFAULT_VALUE', '79.90');
    }
    $assinatura['valor_mensal'] = $valorMensal;

    $queryPagamentos = $pdo_saas->prepare("SELECT id, pedido_referencia, status, status_detalhe, valor, criado_em, pago_em, expiracao_em, qr_code_text, metodo_pagamento, cancel_reason
        FROM empresas_pagamentos
        WHERE empresa_id = :empresa_id
        ORDER BY id DESC
        LIMIT 12");
    $queryPagamentos->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $queryPagamentos->execute();
    $pagamentos = $queryPagamentos->fetchAll(PDO::FETCH_ASSOC);
}

$statusAssinatura = (string) ($assinatura['status'] ?? 'Trial');
$badge = assinatura_saas_badge_local($statusAssinatura);
$valorMensalFormatado = number_format((float) $assinatura['valor_mensal'], 2, ',', '.');
$checkoutConfig = saas_pagbank_config();
$simulacaoAtiva = !empty($checkoutConfig['simulation']);
$allowPlainCardCheckout = !empty($checkoutConfig['allow_plain_card']) || $simulacaoAtiva;
$requireTokenCardCheckout = !empty($checkoutConfig['require_card_token']) && !$allowPlainCardCheckout;
$autoRefundEnabled = !empty($checkoutConfig['auto_refund']);
$checkoutCsrfToken = saas_pagbank_csrf_token('saas_checkout_csrf');

$dataReferencia = null;
if (!empty($assinatura['ciclo_ate'])) {
    $dataReferencia = $assinatura['ciclo_ate'];
} elseif (!empty($assinatura['trial_ate'])) {
    $dataReferencia = $assinatura['trial_ate'];
}

$dataReferenciaFmt = '-';
if (!empty($dataReferencia) && strtotime((string) $dataReferencia)) {
    $dataReferenciaFmt = date('d/m/Y', strtotime((string) $dataReferencia));
}
?>

<style>
    .assinatura-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 16px;
    }

    .assinatura-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        padding: 18px;
    }

    .assinatura-card h4 {
        margin-top: 0;
        margin-bottom: 14px;
        font-weight: 600;
    }

    .assinatura-item {
        margin-bottom: 10px;
    }

    .checkout-msg {
        margin-bottom: 12px;
        display: none;
    }

    .pix-box {
        border: 1px dashed #d7d7d7;
        border-radius: 8px;
        padding: 14px;
        background: #fafafa;
        display: none;
    }

    .pix-box textarea {
        min-height: 86px;
        resize: vertical;
    }

    .pix-meta {
        font-size: 13px;
        color: #555;
    }

    .pix-meta strong {
        color: #222;
    }

    .pix-qrcode {
        max-width: 220px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        padding: 6px;
        background: #fff;
    }

    .card-box {
        border: 1px solid #e6e6e6;
        border-radius: 8px;
        padding: 14px;
        background: #fff;
        margin-top: 12px;
    }

    .checkout-divider {
        margin: 14px 0;
        border-top: 1px dashed #d7d7d7;
    }

    @media (max-width: 991px) {
        .assinatura-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="assinatura-grid">
    <div class="assinatura-card">
        <h4><i class="fa fa-id-card"></i> Resumo da assinatura</h4>

        <div class="assinatura-item">Plano atual: <strong><?= htmlspecialchars((string) ($assinatura['plano_nome'] ?? 'Starter'), ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="assinatura-item">Status: <span class="label <?= htmlspecialchars($badge[0], ENT_QUOTES, 'UTF-8') ?>" id="assinatura-status"><?= htmlspecialchars($badge[1], ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="assinatura-item">Valor mensal: <strong id="assinatura-valor">R$ <?= $valorMensalFormatado ?></strong></div>
        <div class="assinatura-item">Validade atual: <strong id="assinatura-validade"><?= htmlspecialchars($dataReferenciaFmt, ENT_QUOTES, 'UTF-8') ?></strong></div>

        <hr>

        <p style="margin-bottom: 10px; color: #666;">Clique no botão abaixo para gerar o PIX de pagamento via PixGo.</p>
        <a href="https://pixgo.org/pay/b47f728ccdb0652839e509445ff42427" target="_blank" class="btn btn-success">
            <i class="fa fa-qrcode"></i> Gerar PIX de pagamento
        </a>
    </div>

    <div class="assinatura-card" style="display:none;">
        <h4><i class="fa fa-credit-card"></i> Checkout PagBank (PIX e Cartao)</h4>

        <div class="alert checkout-msg" id="checkout-msg"></div>

        <div style="font-weight: 600; margin-bottom: 8px;"><i class="fa fa-qrcode"></i> Pagar com PIX</div>
        <div class="pix-box" id="pix-box">
            <div class="row">
                <div class="col-md-6">
                    <img src="" alt="QR Code PIX" class="img-responsive pix-qrcode" id="pix-qrcode-img" style="display:none;">
                </div>
                <div class="col-md-6 pix-meta">
                    <div>Referencia: <strong id="pix-referencia">-</strong></div>
                    <div>Valor: <strong id="pix-valor">-</strong></div>
                    <div>Expira em: <strong id="pix-expiracao">-</strong></div>
                    <div>Status: <strong id="pix-status">-</strong></div>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <label for="pix-copia-cola">PIX copia e cola</label>
                <textarea class="form-control" id="pix-copia-cola" readonly></textarea>
                <button class="btn btn-success" type="button" id="btn-copiar-pix" style="margin-top: 8px;">
                    <i class="fa fa-copy"></i> Copiar codigo PIX
                </button>
            </div>
        </div>

        <div class="checkout-divider"></div>

        <div style="font-weight: 600; margin-bottom: 8px;"><i class="fa fa-credit-card"></i> Pagar com Cartao de Credito</div>
        <div class="card-box" id="card-box">
            <div class="row">
                <div class="col-md-6">
                    <label for="card-holder-name">Nome do titular</label>
                    <input type="text" class="form-control" id="card-holder-name" placeholder="Nome impresso no cartao">
                </div>
                <div class="col-md-6">
                    <label for="card-holder-tax">CPF do titular</label>
                    <input type="text" class="form-control" id="card-holder-tax" placeholder="Somente numeros">
                </div>
            </div>

            <div class="row" style="margin-top: 10px;">
                <div class="col-md-7">
                    <label for="card-number">Numero do cartao</label>
                    <input type="text" class="form-control" id="card-number" placeholder="0000 0000 0000 0000">
                </div>
                <div class="col-md-2">
                    <label for="card-exp-month">Mes</label>
                    <input type="text" class="form-control" id="card-exp-month" placeholder="MM">
                </div>
                <div class="col-md-2">
                    <label for="card-exp-year">Ano</label>
                    <input type="text" class="form-control" id="card-exp-year" placeholder="AAAA">
                </div>
                <div class="col-md-1">
                    <label for="card-cvv">CVV</label>
                    <input type="password" class="form-control" id="card-cvv" placeholder="***">
                </div>
            </div>

            <div class="row" style="margin-top: 10px;">
                <div class="col-md-4">
                    <label for="card-installments">Parcelas</label>
                    <select class="form-control" id="card-installments">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>x</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="card-encrypted">Token de cartao (opcional)</label>
                    <input type="text" class="form-control" id="card-encrypted" placeholder="Se usar tokenizacao no frontend, informe aqui">
                </div>
            </div>

            <div style="margin-top: 12px;">
                <button class="btn btn-primary" type="button" id="btn-pagar-cartao">
                    <i class="fa fa-lock"></i> Pagar com cartao
                </button>
            </div>
            <?php if ($requireTokenCardCheckout): ?>
                <small class="text-danger" style="display:block; margin-top: 8px;">Tokenizacao de cartao obrigatoria neste ambiente. Informe o token em "Token de cartao" para concluir.</small>
            <?php else: ?>
                <small class="text-muted" style="display:block; margin-top: 8px;">Mesmas credenciais `PAGBANK_*` do Bio Link: sem novas variaveis obrigatorias.</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="assinatura-card" style="margin-top: 16px;">
    <h4><i class="fa fa-history"></i> Ultimos pagamentos</h4>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Referencia</th>
                    <th>Metodo</th>
                    <th>Status</th>
                    <th>Detalhe</th>
                    <th>Valor</th>
                    <th>Criado em</th>
                    <th>Pago em</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pagamentos)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">Nenhum pagamento registrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pagamentos as $pagamento): ?>
                    <?php
                    $labelPagamento = 'label-default';
                    $metodoPagamento = !empty($pagamento['metodo_pagamento'])
                        ? (string) $pagamento['metodo_pagamento']
                        : (!empty($pagamento['qr_code_text']) ? 'PIX' : 'Cartao');
                    if ($pagamento['status'] === 'Pago') {
                        $labelPagamento = 'label-success';
                    } elseif ($pagamento['status'] === 'Pendente') {
                        $labelPagamento = 'label-warning';
                    } elseif ($pagamento['status'] === 'Cancelado' || $pagamento['status'] === 'Expirado') {
                        $labelPagamento = 'label-danger';
                    }
                    ?>
                    <tr>
                        <td>#<?= (int) $pagamento['id'] ?></td>
                        <td><small><?= htmlspecialchars((string) $pagamento['pedido_referencia'], ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><span class="label label-info"><?= htmlspecialchars($metodoPagamento, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><span class="label <?= $labelPagamento ?>"><?= htmlspecialchars((string) $pagamento['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><small><?= htmlspecialchars((string) ($pagamento['status_detalhe'] ?: $pagamento['cancel_reason'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td>R$ <?= number_format((float) $pagamento['valor'], 2, ',', '.') ?></td>
                        <td><small><?= !empty($pagamento['criado_em']) ? date('d/m/Y H:i', strtotime((string) $pagamento['criado_em'])) : '-' ?></small></td>
                        <td><small><?= !empty($pagamento['pago_em']) ? date('d/m/Y H:i', strtotime((string) $pagamento['pago_em'])) : '-' ?></small></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        var endpoint = '../saas/pagamento_checkout.php';
        var pagamentoAtualId = 0;
        var polling = null;
        var simulacaoAtiva = <?= $simulacaoAtiva ? 'true' : 'false' ?>;
        var allowPlainCard = <?= $allowPlainCardCheckout ? 'true' : 'false' ?>;
        var requireCardToken = <?= $requireTokenCardCheckout ? 'true' : 'false' ?>;
        var autoRefundEnabled = <?= $autoRefundEnabled ? 'true' : 'false' ?>;
        var csrfToken = <?= json_encode((string) $checkoutCsrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var pixIdempotencyKey = '';
        var cardIdempotencyKey = '';

        function parseResposta(resposta) {
            if (typeof resposta === 'string') {
                try {
                    resposta = JSON.parse(resposta);
                } catch (e) {
                    return {ok: false, mensagem: resposta};
                }
            }

            if (resposta && resposta.csrf_token) {
                csrfToken = resposta.csrf_token;
            }

            return resposta || {ok: false, mensagem: 'Resposta invalida.'};
        }

        function gerarIdempotency(prefixo) {
            var base = prefixo + '-' + Date.now() + '-';

            if (window.crypto && window.crypto.getRandomValues) {
                var arr = new Uint8Array(10);
                window.crypto.getRandomValues(arr);
                var hex = Array.prototype.map.call(arr, function (n) {
                    return n.toString(16).padStart(2, '0');
                }).join('');
                return base + hex;
            }

            return base + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
        }

        function somenteNumeros(valor) {
            return String(valor || '').replace(/\D+/g, '');
        }

        function formatarData(valor) {
            if (!valor) {
                return '-';
            }

            var normalizado = valor.replace(' ', 'T');
            var data = new Date(normalizado);
            if (isNaN(data.getTime())) {
                return valor;
            }

            return data.toLocaleString('pt-BR');
        }

        function mostrarMensagem(tipo, mensagem) {
            var box = $('#checkout-msg');
            box.removeClass('alert-success alert-danger alert-info alert-warning');
            box.addClass('alert-' + tipo);
            box.text(mensagem || '');
            box.show();
        }

        function atualizarStatusVisual(status) {
            var statusNode = $('#pix-status');
            statusNode.text(status || '-');

            $('#btn-verificar-pix').hide();
            $('#btn-simular-pago').hide();
            $('#btn-cancelar-pagamento').hide();
            $('#btn-estornar-pagamento').hide();

            if (status === 'Pago') {
                $('#btn-estornar-pagamento').show();
                $('#btn-estornar-pagamento').text(autoRefundEnabled ? 'Estornar agora' : 'Solicitar estorno');
                if (polling) {
                    clearInterval(polling);
                    polling = null;
                }
            } else if (status === 'Pendente') {
                $('#btn-verificar-pix').show();
                $('#btn-cancelar-pagamento').show();
                if (simulacaoAtiva) {
                    $('#btn-simular-pago').show();
                }
            }
        }

        function renderCheckout(dados) {
            pagamentoAtualId = parseInt(dados.pagamento_id || 0, 10);
            $('#pix-box').show();

            $('#pix-referencia').text(dados.referencia || '-');
            $('#pix-valor').text('R$ ' + (parseFloat(dados.valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
            $('#pix-expiracao').text(formatarData(dados.expiracao));
            $('#pix-copia-cola').val(dados.pix_copia_cola || '');

            var qrLink = (dados.qr_code_link || '').trim();
            if (qrLink !== '') {
                $('#pix-qrcode-img').attr('src', qrLink).show();
            } else {
                $('#pix-qrcode-img').hide();
            }

            atualizarStatusVisual(dados.status_pagamento || 'Pendente');
        }

        function atualizarResumoAssinatura(dados) {
            if (!dados) {
                return;
            }

            if (dados.assinatura_status) {
                var label = 'label-default';
                if (dados.assinatura_status === 'Ativa') {
                    label = 'label-success';
                } else if (dados.assinatura_status === 'Trial') {
                    label = 'label-warning';
                } else if (dados.assinatura_status === 'Suspensa') {
                    label = 'label-danger';
                }

                $('#assinatura-status').attr('class', 'label ' + label).text(dados.assinatura_status);
            }

            if (dados.assinatura_ciclo_ate) {
                $('#assinatura-validade').text(formatarData(dados.assinatura_ciclo_ate + ' 00:00:00'));
            } else if (dados.assinatura_trial_ate) {
                $('#assinatura-validade').text(formatarData(dados.assinatura_trial_ate + ' 00:00:00'));
            }
        }

        function verificarPagamento(mostrarRetorno) {
            if (!pagamentoAtualId) {
                return;
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    acao: 'status',
                    csrf_token: csrfToken,
                    pagamento_id: pagamentoAtualId
                },
                success: function (resposta) {
                    var dados = parseResposta(resposta);
                    if (!dados.ok) {
                        if (mostrarRetorno) {
                            mostrarMensagem('danger', dados.mensagem || 'Falha ao verificar pagamento.');
                        }
                        return;
                    }

                    atualizarStatusVisual(dados.status_pagamento || '-');
                    atualizarResumoAssinatura(dados);

                    if (dados.status_pagamento === 'Pago') {
                        mostrarMensagem('success', 'Pagamento confirmado! Assinatura renovada com sucesso.');
                    } else if (mostrarRetorno) {
                        mostrarMensagem('info', 'Pagamento ainda pendente. Tente novamente em alguns segundos.');
                    }
                },
                error: function () {
                    if (mostrarRetorno) {
                        mostrarMensagem('danger', 'Erro de comunicacao ao verificar pagamento.');
                    }
                }
            });
        }

        function iniciarPolling() {
            if (polling) {
                clearInterval(polling);
            }

            polling = setInterval(function () {
                verificarPagamento(false);
            }, 15000);
        }

        $('#btn-pagar-cartao').on('click', function () {
            var holderName = $.trim($('#card-holder-name').val());
            var holderTax = somenteNumeros($('#card-holder-tax').val());
            var cardNumber = somenteNumeros($('#card-number').val());
            var cardExpMonth = somenteNumeros($('#card-exp-month').val());
            var cardExpYear = somenteNumeros($('#card-exp-year').val());
            var cardCvv = somenteNumeros($('#card-cvv').val());
            var installments = parseInt($('#card-installments').val() || '1', 10);
            var cardEncrypted = $.trim($('#card-encrypted').val());

            if (holderName === '') {
                mostrarMensagem('warning', 'Informe o nome do titular do cartao.');
                return;
            }

            if (cardEncrypted === '') {
                if (requireCardToken) {
                    mostrarMensagem('warning', 'Token de cartao obrigatorio neste ambiente.');
                    return;
                }

                if (!allowPlainCard) {
                    mostrarMensagem('warning', 'Pagamento sem tokenizacao foi bloqueado por seguranca.');
                    return;
                }

                if (cardNumber.length < 13 || cardNumber.length > 19) {
                    mostrarMensagem('warning', 'Numero do cartao invalido.');
                    return;
                }

                if (cardExpMonth.length < 1 || parseInt(cardExpMonth, 10) < 1 || parseInt(cardExpMonth, 10) > 12) {
                    mostrarMensagem('warning', 'Mes de validade invalido.');
                    return;
                }

                if (cardExpYear.length < 2) {
                    mostrarMensagem('warning', 'Ano de validade invalido.');
                    return;
                }

                if (cardCvv.length < 3 || cardCvv.length > 4) {
                    mostrarMensagem('warning', 'CVV invalido.');
                    return;
                }
            }

            mostrarMensagem('info', 'Processando pagamento no cartao...');
            if (!cardIdempotencyKey) {
                cardIdempotencyKey = gerarIdempotency('card');
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    acao: 'pagar_cartao',
                    csrf_token: csrfToken,
                    holder_name: holderName,
                    holder_tax_id: holderTax,
                    card_number: cardNumber,
                    card_exp_month: cardExpMonth,
                    card_exp_year: cardExpYear,
                    card_cvv: cardCvv,
                    installments: installments,
                    card_encrypted: cardEncrypted,
                    idempotency_key: cardIdempotencyKey
                },
                success: function (resposta) {
                    var dados = parseResposta(resposta);
                    if (!dados.ok) {
                        cardIdempotencyKey = '';
                        mostrarMensagem('danger', dados.mensagem || 'Falha ao processar pagamento no cartao.');
                        return;
                    }

                    pagamentoAtualId = parseInt(dados.pagamento_id || 0, 10);
                    atualizarStatusVisual(dados.status_pagamento || 'Pendente');
                    atualizarResumoAssinatura(dados);

                    if (dados.status_pagamento === 'Pago') {
                        mostrarMensagem('success', dados.mensagem || 'Pagamento no cartao aprovado e assinatura renovada.');
                        cardIdempotencyKey = '';
                    } else {
                        mostrarMensagem('info', dados.mensagem || 'Pagamento enviado para analise. Acompanhe o status.');
                        iniciarPolling();
                    }
                },
                error: function () {
                    cardIdempotencyKey = '';
                    mostrarMensagem('danger', 'Erro de comunicacao ao processar cartao.');
                }
            });
        });

        $('#btn-gerar-pix').on('click', function () {
            mostrarMensagem('info', 'Gerando cobranca PIX no PagBank...');
            if (!pixIdempotencyKey) {
                pixIdempotencyKey = gerarIdempotency('pix');
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    acao: 'gerar_pix',
                    csrf_token: csrfToken,
                    idempotency_key: pixIdempotencyKey
                },
                success: function (resposta) {
                    var dados = parseResposta(resposta);
                    if (!dados.ok) {
                        pixIdempotencyKey = '';
                        mostrarMensagem('danger', dados.mensagem || 'Nao foi possivel gerar o PIX.');
                        return;
                    }

                    renderCheckout(dados);
                    mostrarMensagem('success', dados.mensagem || 'PIX gerado com sucesso.');
                    iniciarPolling();
                },
                error: function () {
                    pixIdempotencyKey = '';
                    mostrarMensagem('danger', 'Erro de comunicacao ao gerar o PIX.');
                }
            });
        });

        $('#btn-verificar-pix').on('click', function () {
            verificarPagamento(true);
        });

        $('#btn-cancelar-pagamento').on('click', function () {
            if (!pagamentoAtualId) {
                mostrarMensagem('warning', 'Nenhum pagamento selecionado para cancelar.');
                return;
            }

            if (!confirm('Deseja cancelar este pagamento pendente?')) {
                return;
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    acao: 'cancelar_pagamento',
                    csrf_token: csrfToken,
                    pagamento_id: pagamentoAtualId
                },
                success: function (resposta) {
                    var dados = parseResposta(resposta);
                    if (!dados.ok) {
                        mostrarMensagem('danger', dados.mensagem || 'Falha ao cancelar pagamento.');
                        return;
                    }

                    atualizarStatusVisual(dados.status_pagamento || 'Cancelado');
                    atualizarResumoAssinatura(dados);
                    mostrarMensagem('success', dados.mensagem || 'Pagamento cancelado com sucesso.');
                    pixIdempotencyKey = '';
                    cardIdempotencyKey = '';
                },
                error: function () {
                    mostrarMensagem('danger', 'Erro de comunicacao ao cancelar pagamento.');
                }
            });
        });

        $('#btn-estornar-pagamento').on('click', function () {
            if (!pagamentoAtualId) {
                mostrarMensagem('warning', 'Nenhum pagamento selecionado para estorno.');
                return;
            }

            if (!confirm('Deseja solicitar estorno deste pagamento?')) {
                return;
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    acao: 'solicitar_estorno',
                    csrf_token: csrfToken,
                    pagamento_id: pagamentoAtualId
                },
                success: function (resposta) {
                    var dados = parseResposta(resposta);
                    if (!dados.ok) {
                        mostrarMensagem('danger', dados.mensagem || 'Falha ao solicitar estorno.');
                        return;
                    }

                    atualizarStatusVisual(dados.status_pagamento || 'Cancelado');
                    atualizarResumoAssinatura(dados);
                    mostrarMensagem('success', dados.mensagem || 'Estorno solicitado.');
                },
                error: function () {
                    mostrarMensagem('danger', 'Erro de comunicacao ao solicitar estorno.');
                }
            });
        });

        $('#btn-simular-pago').on('click', function () {
            if (!pagamentoAtualId) {
                mostrarMensagem('warning', 'Gere um pagamento antes de simular a confirmacao.');
                return;
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    acao: 'simular_pagamento',
                    csrf_token: csrfToken,
                    pagamento_id: pagamentoAtualId
                },
                success: function (resposta) {
                    var dados = parseResposta(resposta);
                    if (!dados.ok) {
                        mostrarMensagem('danger', dados.mensagem || 'Falha ao simular pagamento.');
                        return;
                    }

                    atualizarStatusVisual(dados.status_pagamento || 'Pago');
                    atualizarResumoAssinatura(dados);
                    mostrarMensagem('success', 'Pagamento simulado com sucesso e assinatura renovada.');
                },
                error: function () {
                    mostrarMensagem('danger', 'Erro de comunicacao ao simular pagamento.');
                }
            });
        });

        $('#btn-copiar-pix').on('click', function () {
            var codigo = $('#pix-copia-cola').val();
            if (!codigo) {
                mostrarMensagem('warning', 'Nenhum codigo PIX disponivel para copia.');
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(codigo).then(function () {
                    mostrarMensagem('success', 'Codigo PIX copiado para a area de transferencia.');
                }).catch(function () {
                    mostrarMensagem('warning', 'Nao foi possivel copiar automaticamente. Selecione e copie manualmente.');
                });
                return;
            }

            var campo = document.getElementById('pix-copia-cola');
            campo.focus();
            campo.select();
            try {
                document.execCommand('copy');
                mostrarMensagem('success', 'Codigo PIX copiado para a area de transferencia.');
            } catch (e) {
                mostrarMensagem('warning', 'Nao foi possivel copiar automaticamente. Selecione e copie manualmente.');
            }
        });
    })();
</script>
