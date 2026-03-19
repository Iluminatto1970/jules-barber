<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

function assinatura_status_badge($status)
{
    if ($status === 'Ativa') {
        return ['status-success', 'Ativa'];
    }

    if ($status === 'Trial') {
        return ['status-warning', 'Trial'];
    }

    if ($status === 'Suspensa') {
        return ['status-danger', 'Suspensa'];
    }

    if ($status === 'Cancelada') {
        return ['status-muted', 'Cancelada'];
    }

    return ['status-muted', $status ?: 'Indefinida'];
}

function assinatura_normalizar_data($valor, $campo)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    $ts = strtotime($valor);
    if ($ts === false) {
        throw new Exception('Data invalida no campo: ' . $campo);
    }

    return date('Y-m-d', $ts);
}

function assinatura_normalizar_datetime($valor, $campo)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $valor)) {
        $valor = str_replace('T', ' ', $valor) . ':00';
    }

    $ts = strtotime($valor);
    if ($ts === false) {
        throw new Exception('Data/hora invalida no campo: ' . $campo);
    }

    return date('Y-m-d H:i:s', $ts);
}

function assinatura_para_input_data($valor)
{
    if (!$valor) {
        return '';
    }

    $ts = strtotime((string) $valor);
    if ($ts === false) {
        return '';
    }

    return date('Y-m-d', $ts);
}

function assinatura_para_input_datetime($valor)
{
    if (!$valor) {
        return '';
    }

    $ts = strtotime((string) $valor);
    if ($ts === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $ts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectParams = [];
    $returnNome = trim((string) ($_POST['return_nome'] ?? ''));
    $returnDns = trim((string) ($_POST['return_dns'] ?? ''));
    $returnStatus = trim((string) ($_POST['return_status'] ?? ''));

    if ($returnNome !== '') {
        $redirectParams['nome'] = $returnNome;
    }

    if ($returnDns !== '') {
        $redirectParams['dns'] = $returnDns;
    }

    if ($returnStatus !== '') {
        $redirectParams['status'] = $returnStatus;
    }

    try {
        admin_csrf_validate();
        $action = admin_request_action();

        if ($action === 'update_assinatura') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            $planoId = (int) ($_POST['plano_id'] ?? 0);
            $status = trim((string) ($_POST['status_assinatura'] ?? 'Trial'));
            $trialDias = isset($_POST['trial_dias']) && $_POST['trial_dias'] !== ''
                ? max(0, (int) $_POST['trial_dias'])
                : null;

            $inicioEmInput = trim((string) ($_POST['inicio_em'] ?? ''));
            $trialAteInput = trim((string) ($_POST['trial_ate'] ?? ''));
            $cicloAteInput = trim((string) ($_POST['ciclo_ate'] ?? ''));
            $observacoes = trim((string) ($_POST['observacoes'] ?? ''));

            if ($empresaId <= 0 || $planoId <= 0) {
                throw new Exception('Empresa/plano invalidos para atualizar a assinatura.');
            }

            $statusValidos = ['Trial', 'Ativa', 'Suspensa', 'Cancelada'];
            if (!in_array($status, $statusValidos, true)) {
                throw new Exception('Status da assinatura invalido.');
            }

            $queryPlano = $pdo_saas->prepare("SELECT slug, nome, trial_dias FROM planos WHERE id = :id LIMIT 1");
            $queryPlano->bindValue(':id', $planoId, PDO::PARAM_INT);
            $queryPlano->execute();
            $plano = $queryPlano->fetch(PDO::FETCH_ASSOC);
            if (!$plano) {
                throw new Exception('Plano selecionado nao encontrado.');
            }

            $queryAtual = $pdo_saas->prepare("SELECT inicio_em, trial_ate, ciclo_ate, observacoes FROM empresas_assinaturas WHERE empresa_id = :empresa_id LIMIT 1");
            $queryAtual->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $queryAtual->execute();
            $assinaturaAtual = $queryAtual->fetch(PDO::FETCH_ASSOC);

            $inicioEm = assinatura_normalizar_datetime($inicioEmInput, 'Inicio');
            if ($inicioEm === null) {
                if (!empty($assinaturaAtual['inicio_em'])) {
                    $inicioEm = $assinaturaAtual['inicio_em'];
                } else {
                    $inicioEm = date('Y-m-d H:i:s');
                }
            }

            $trialAte = assinatura_normalizar_data($trialAteInput, 'Trial ate');
            if ($trialAte === null && $status === 'Trial') {
                if ($trialDias === null) {
                    $trialDias = max(0, (int) $plano['trial_dias']);
                }

                $trialAte = date('Y-m-d', strtotime('+' . $trialDias . ' days'));
            }

            if ($status !== 'Trial' && $trialAteInput === '') {
                $trialAte = null;
            }

            $cicloAte = assinatura_normalizar_data($cicloAteInput, 'Ciclo ate');

            $suspensaEm = null;
            if ($status === 'Suspensa') {
                $suspensaEm = date('Y-m-d H:i:s');
            }

            $salvar = $pdo_saas->prepare("INSERT INTO empresas_assinaturas
                (empresa_id, plano_id, status, inicio_em, trial_ate, ciclo_ate, suspensa_em, observacoes)
                VALUES
                (:empresa_id, :plano_id, :status, :inicio_em, :trial_ate, :ciclo_ate, :suspensa_em, :observacoes)
                ON DUPLICATE KEY UPDATE
                    plano_id = VALUES(plano_id),
                    status = VALUES(status),
                    inicio_em = VALUES(inicio_em),
                    trial_ate = VALUES(trial_ate),
                    ciclo_ate = VALUES(ciclo_ate),
                    suspensa_em = VALUES(suspensa_em),
                    observacoes = VALUES(observacoes)");

            $salvar->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $salvar->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
            $salvar->bindValue(':status', $status);
            $salvar->bindValue(':inicio_em', $inicioEm);

            if ($trialAte === null) {
                $salvar->bindValue(':trial_ate', null, PDO::PARAM_NULL);
            } else {
                $salvar->bindValue(':trial_ate', $trialAte);
            }

            if ($cicloAte === null) {
                $salvar->bindValue(':ciclo_ate', null, PDO::PARAM_NULL);
            } else {
                $salvar->bindValue(':ciclo_ate', $cicloAte);
            }

            if ($suspensaEm === null) {
                $salvar->bindValue(':suspensa_em', null, PDO::PARAM_NULL);
            } else {
                $salvar->bindValue(':suspensa_em', $suspensaEm);
            }

            if ($observacoes === '') {
                $salvar->bindValue(':observacoes', null, PDO::PARAM_NULL);
            } else {
                $salvar->bindValue(':observacoes', $observacoes);
            }

            $salvar->execute();

            $detalhe = 'Plano atualizado para ' . $plano['nome']
                . ' | status: ' . $status
                . ' | trial ate: ' . ($trialAte ?: '-')
                . ' | ciclo ate: ' . ($cicloAte ?: '-');

            $evento = $pdo_saas->prepare("INSERT INTO empresas_eventos_billing (empresa_id, tipo, recurso, detalhe)
                VALUES (:empresa_id, 'assinatura_atualizada', 'assinatura', :detalhe)");
            $evento->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
            $evento->bindValue(':detalhe', $detalhe);
            $evento->execute();

            admin_set_flash('success', 'Assinatura atualizada com sucesso.');
        }
    } catch (Exception $e) {
        admin_set_flash('danger', $e->getMessage());
    }

    admin_redirect('assinaturas', $redirectParams);
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$nomeFilter = trim((string) ($_GET['nome'] ?? ''));
$dnsFilter = trim((string) ($_GET['dns'] ?? ''));
$legacyQuery = trim((string) ($_GET['q'] ?? ''));

if ($nomeFilter === '' && $legacyQuery !== '') {
    $nomeFilter = $legacyQuery;
}

$planos = [];
$assinaturas = [];
$eventos = [];

try {
    $planos = $pdo_saas->query("SELECT id, nome, slug FROM planos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT
            a.id,
            a.empresa_id,
            a.plano_id,
            a.status,
            a.inicio_em,
            a.trial_ate,
            a.ciclo_ate,
            a.suspensa_em,
            a.observacoes,
            a.atualizado_em,
            e.nome AS empresa_nome,
            e.slug AS empresa_slug,
            e.ativo AS empresa_ativa,
            d.dominio AS empresa_dns,
            p.nome AS plano_nome
        FROM empresas_assinaturas a
        LEFT JOIN empresas e ON e.id = a.empresa_id
        LEFT JOIN empresas_dominios d ON d.empresa_id = e.id AND d.principal = 1
        LEFT JOIN planos p ON p.id = a.plano_id
        WHERE 1 = 1";

    $params = [];
    if ($statusFilter !== '') {
        $sql .= " AND a.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($nomeFilter !== '') {
        $sql .= " AND (
            e.nome LIKE :nome
            OR e.slug LIKE :nome
            OR p.nome LIKE :nome
        )";
        $params[':nome'] = '%' . $nomeFilter . '%';
    }

    if ($dnsFilter !== '') {
        $sql .= " AND d.dominio LIKE :dns";
        $params[':dns'] = '%' . $dnsFilter . '%';
    }

    $sql .= " ORDER BY a.atualizado_em DESC, a.id DESC LIMIT 250";

    $query = $pdo_saas->prepare($sql);
    foreach ($params as $key => $value) {
        $query->bindValue($key, $value);
    }
    $query->execute();
    $assinaturas = $query->fetchAll(PDO::FETCH_ASSOC);

    $queryEventos = $pdo_saas->query("SELECT
            b.id,
            b.empresa_id,
            b.tipo,
            b.recurso,
            b.detalhe,
            b.criado_em,
            e.nome AS empresa_nome
        FROM empresas_eventos_billing b
        LEFT JOIN empresas e ON e.id = b.empresa_id
        ORDER BY b.id DESC
        LIMIT 20");
    $eventos = $queryEventos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    admin_set_flash('danger', 'Falha ao carregar assinaturas: ' . $e->getMessage());
}
?>

<style>
    .assinaturas-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .plan-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .plan-header h3 {
        margin: 0;
        font-size: 1rem;
    }

    .assinaturas-toolbar form {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        width: 100%;
    }

    .events-box {
        margin-top: 14px;
    }

    @media (max-width: 767px) {
        .assinaturas-toolbar form {
            flex-direction: column;
            align-items: stretch;
        }

        .assinaturas-toolbar .form-control,
        .assinaturas-toolbar .btn {
            width: 100%;
        }
    }
</style>

<div class="assinaturas-toolbar">
    <form method="get" class="align-items-center">
        <input type="hidden" name="page" value="assinaturas">
        <input type="text" class="form-control" name="nome" placeholder="Nome da empresa ou plano" value="<?= admin_h($nomeFilter) ?>">
        <input type="text" class="form-control" name="dns" placeholder="DNS principal" value="<?= admin_h($dnsFilter) ?>">
        <select class="form-control" name="status">
            <option value="">Todos status</option>
            <?php foreach (['Trial', 'Ativa', 'Suspensa', 'Cancelada'] as $status): ?>
                <option value="<?= admin_h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= admin_h($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-soft" type="submit"><i class="fa fa-search mr-1"></i>Filtrar</button>
        <?php if ($nomeFilter !== '' || $dnsFilter !== '' || $statusFilter !== ''): ?>
            <a href="?page=assinaturas" class="btn btn-soft">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<section class="panel-card">
    <header class="plan-header">
        <h3><i class="fa fa-credit-card mr-2"></i>Assinaturas (<?= count($assinaturas) ?>)</h3>
        <small class="text-muted">Gerencie plano, status e datas da assinatura</small>
    </header>

    <div class="table-responsive">
        <table class="table table-modern table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>DNS</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Inicio</th>
                    <th>Trial ate</th>
                    <th>Expiracao</th>
                    <th>Atualizado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assinaturas)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Nenhuma assinatura encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assinaturas as $assinatura):
                        $badge = assinatura_status_badge($assinatura['status']);
                    ?>
                        <tr>
                            <td>#<?= (int) $assinatura['id'] ?></td>
                            <td>
                                <strong><?= admin_h($assinatura['empresa_nome'] ?: 'Empresa removida') ?></strong><br>
                                <small class="text-muted"><?= admin_h($assinatura['empresa_slug']) ?></small>
                            </td>
                            <td><small><?= admin_h($assinatura['empresa_dns'] ?: '-') ?></small></td>
                            <td><?= admin_h($assinatura['plano_nome'] ?: 'Sem plano') ?></td>
                            <td><span class="status-pill <?= admin_h($badge[0]) ?>"><?= admin_h($badge[1]) ?></span></td>
                            <td><small><?= admin_h(admin_format_date($assinatura['inicio_em'], true)) ?></small></td>
                            <td><small><?= admin_h(admin_format_date($assinatura['trial_ate'])) ?></small></td>
                            <td>
                                <small><?= admin_h(admin_format_date($assinatura['ciclo_ate'])) ?></small>
                                <?php
                                $assinaturaVencida = ($assinatura['status'] === 'Ativa' && !empty($assinatura['ciclo_ate']) && date('Y-m-d') > $assinatura['ciclo_ate']);
                                ?>
                                <?php if ($assinaturaVencida): ?>
                                    <span class="status-pill status-danger mt-1">Vencida</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= admin_h(admin_format_date($assinatura['atualizado_em'], true)) ?></small></td>
                            <td class="text-right">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-soft js-editar-assinatura"
                                    data-toggle="modal"
                                    data-target="#modalEditarAssinatura"
                                    data-empresa-id="<?= (int) $assinatura['empresa_id'] ?>"
                                    data-empresa-nome="<?= admin_h($assinatura['empresa_nome']) ?>"
                                    data-plano-id="<?= (int) $assinatura['plano_id'] ?>"
                                    data-status="<?= admin_h($assinatura['status']) ?>"
                                    data-inicio-em="<?= admin_h(assinatura_para_input_datetime($assinatura['inicio_em'])) ?>"
                                    data-trial-ate="<?= admin_h(assinatura_para_input_data($assinatura['trial_ate'])) ?>"
                                    data-ciclo-ate="<?= admin_h(assinatura_para_input_data($assinatura['ciclo_ate'])) ?>"
                                    data-observacoes="<?= admin_h($assinatura['observacoes'] ?: '') ?>"
                                >
                                    <i class="fa fa-pen"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel-card events-box">
    <header class="plan-header">
        <h3><i class="fa fa-history mr-2"></i>Eventos de billing</h3>
    </header>
    <div class="table-responsive">
        <table class="table table-modern table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>Tipo</th>
                    <th>Recurso</th>
                    <th>Detalhe</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventos)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Sem eventos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td>#<?= (int) $evento['id'] ?></td>
                            <td><?= admin_h($evento['empresa_nome'] ?: 'Empresa removida') ?></td>
                            <td><small><?= admin_h($evento['tipo']) ?></small></td>
                            <td><small><?= admin_h($evento['recurso'] ?: '-') ?></small></td>
                            <td><small><?= admin_h($evento['detalhe'] ?: '-') ?></small></td>
                            <td><small><?= admin_h(admin_format_date($evento['criado_em'], true)) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="modalEditarAssinatura" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="update_assinatura">
                <input type="hidden" name="empresa_id" id="assinaturaEmpresaId" value="">
                <input type="hidden" name="return_nome" value="<?= admin_h($nomeFilter) ?>">
                <input type="hidden" name="return_dns" value="<?= admin_h($dnsFilter) ?>">
                <input type="hidden" name="return_status" value="<?= admin_h($statusFilter) ?>">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-pen mr-1"></i>Atualizar assinatura</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body">
                    <p class="mb-3 text-muted">Empresa: <strong id="assinaturaEmpresaNome">-</strong></p>

                    <div class="form-group">
                        <label>Plano</label>
                        <select class="form-control" name="plano_id" id="assinaturaPlanoId" required>
                            <option value="">Selecione</option>
                            <?php foreach ($planos as $plano): ?>
                                <option value="<?= (int) $plano['id'] ?>"><?= admin_h($plano['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status_assinatura" id="assinaturaStatus">
                            <?php foreach (['Trial', 'Ativa', 'Suspensa', 'Cancelada'] as $status): ?>
                                <option value="<?= admin_h($status) ?>"><?= admin_h($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Inicio da assinatura</label>
                        <input type="datetime-local" class="form-control" name="inicio_em" id="assinaturaInicioEm">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-sm-6">
                            <label>Trial ate</label>
                            <input type="date" class="form-control" name="trial_ate" id="assinaturaTrialAte">
                        </div>
                        <div class="form-group col-sm-6">
                            <label>Expiracao do plano</label>
                            <input type="date" class="form-control" name="ciclo_ate" id="assinaturaCicloAte">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Trial (dias, opcional)</label>
                        <input type="number" min="0" class="form-control" name="trial_dias" id="assinaturaTrialDias" value="14">
                        <small class="form-text text-muted">
                            Se status for Trial e o campo "Trial ate" estiver vazio, esta quantidade sera usada para calcular automaticamente.
                        </small>
                    </div>

                    <div class="form-group mb-0">
                        <label>Observacoes</label>
                        <textarea class="form-control" name="observacoes" id="assinaturaObservacoes" rows="3" placeholder="Opcional"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Salvar alteracoes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var botoes = document.querySelectorAll('.js-editar-assinatura');
        var empresaIdInput = document.getElementById('assinaturaEmpresaId');
        var empresaNomeNode = document.getElementById('assinaturaEmpresaNome');
        var planoInput = document.getElementById('assinaturaPlanoId');
        var statusInput = document.getElementById('assinaturaStatus');
        var inicioInput = document.getElementById('assinaturaInicioEm');
        var trialAteInput = document.getElementById('assinaturaTrialAte');
        var cicloAteInput = document.getElementById('assinaturaCicloAte');
        var observacoesInput = document.getElementById('assinaturaObservacoes');
        var trialDiasInput = document.getElementById('assinaturaTrialDias');

        if (!botoes.length || !empresaIdInput || !empresaNomeNode || !planoInput || !statusInput) {
            return;
        }

        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                empresaIdInput.value = botao.getAttribute('data-empresa-id') || '';
                empresaNomeNode.textContent = botao.getAttribute('data-empresa-nome') || '-';
                planoInput.value = botao.getAttribute('data-plano-id') || '';
                statusInput.value = botao.getAttribute('data-status') || 'Trial';

                if (inicioInput) {
                    inicioInput.value = botao.getAttribute('data-inicio-em') || '';
                }

                if (trialAteInput) {
                    trialAteInput.value = botao.getAttribute('data-trial-ate') || '';
                }

                if (cicloAteInput) {
                    cicloAteInput.value = botao.getAttribute('data-ciclo-ate') || '';
                }

                if (observacoesInput) {
                    observacoesInput.value = botao.getAttribute('data-observacoes') || '';
                }

                if (trialDiasInput) {
                    trialDiasInput.value = '14';
                }
            });
        });
    })();
</script>
