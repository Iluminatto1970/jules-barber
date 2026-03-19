<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

if ($pdo_saas instanceof PDO && function_exists('saas_pagbank_garantir_estrutura')) {
    saas_pagbank_garantir_estrutura($pdo_saas);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectParams = [];

    if (!empty($_POST['return_plan_id'])) {
        $redirectParams['plan_id'] = (int) $_POST['return_plan_id'];
    }

    if (!empty($_POST['return_edit_id'])) {
        $redirectParams['edit'] = (int) $_POST['return_edit_id'];
    }

    try {
        admin_csrf_validate();
        $action = admin_request_action();

        if ($action === 'save_plano') {
            $planoId = (int) ($_POST['plano_id'] ?? 0);
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $slug = saas_normalizar_slug((string) ($_POST['slug'] ?? ''));
            $descricao = trim((string) ($_POST['descricao'] ?? ''));
            $trialDias = max(0, (int) ($_POST['trial_dias'] ?? 14));
            $valorMensalInput = trim((string) ($_POST['valor_mensal'] ?? '79,90'));
            $ativo = (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim';

            $valorMensalNormalizado = $valorMensalInput;
            if (strpos($valorMensalNormalizado, ',') !== false) {
                $valorMensalNormalizado = str_replace('.', '', $valorMensalNormalizado);
                $valorMensalNormalizado = str_replace(',', '.', $valorMensalNormalizado);
            }
            $valorMensalNormalizado = preg_replace('/[^0-9.]+/', '', $valorMensalNormalizado);
            $valorMensal = (float) $valorMensalNormalizado;

            if ($nome === '') {
                throw new Exception('Informe o nome do plano.');
            }

            if ($valorMensal <= 0) {
                throw new Exception('Informe um valor mensal maior que zero.');
            }

            $check = $pdo_saas->prepare("SELECT id FROM planos WHERE slug = :slug AND id <> :id LIMIT 1");
            $check->bindValue(':slug', $slug);
            $check->bindValue(':id', $planoId, PDO::PARAM_INT);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception('Slug de plano ja utilizado.');
            }

            if ($planoId > 0) {
                $query = $pdo_saas->prepare("UPDATE planos
                    SET nome = :nome, slug = :slug, descricao = :descricao, trial_dias = :trial_dias, valor_mensal = :valor_mensal, ativo = :ativo
                    WHERE id = :id");
                $query->bindValue(':id', $planoId, PDO::PARAM_INT);
                $query->bindValue(':nome', $nome);
                $query->bindValue(':slug', $slug);
                $query->bindValue(':descricao', $descricao !== '' ? $descricao : null, $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $query->bindValue(':trial_dias', $trialDias, PDO::PARAM_INT);
                $query->bindValue(':valor_mensal', $valorMensal);
                $query->bindValue(':ativo', $ativo);
                $query->execute();

                admin_set_flash('success', 'Plano atualizado com sucesso.');
                $redirectParams['edit'] = $planoId;
                $redirectParams['plan_id'] = $planoId;
            } else {
                $query = $pdo_saas->prepare("INSERT INTO planos (nome, slug, descricao, trial_dias, valor_mensal, ativo)
                    VALUES (:nome, :slug, :descricao, :trial_dias, :valor_mensal, :ativo)");
                $query->bindValue(':nome', $nome);
                $query->bindValue(':slug', $slug);
                $query->bindValue(':descricao', $descricao !== '' ? $descricao : null, $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $query->bindValue(':trial_dias', $trialDias, PDO::PARAM_INT);
                $query->bindValue(':valor_mensal', $valorMensal);
                $query->bindValue(':ativo', $ativo);
                $query->execute();

                $novoId = (int) $pdo_saas->lastInsertId();
                admin_set_flash('success', 'Plano criado com sucesso.');
                $redirectParams['edit'] = $novoId;
                $redirectParams['plan_id'] = $novoId;
            }
        }

        if ($action === 'delete_plano') {
            $planoId = (int) ($_POST['plano_id'] ?? 0);
            if ($planoId <= 0) {
                throw new Exception('Plano invalido para exclusao.');
            }

            $uso = $pdo_saas->prepare("SELECT COUNT(*) FROM empresas_assinaturas WHERE plano_id = :plano_id");
            $uso->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
            $uso->execute();
            if ((int) $uso->fetchColumn() > 0) {
                throw new Exception('Nao e possivel excluir plano com assinaturas vinculadas.');
            }

            $delete = $pdo_saas->prepare("DELETE FROM planos WHERE id = :id");
            $delete->bindValue(':id', $planoId, PDO::PARAM_INT);
            $delete->execute();
            admin_set_flash('success', 'Plano excluido com sucesso.');

            unset($redirectParams['edit'], $redirectParams['plan_id']);
        }

        if ($action === 'save_recurso') {
            $planoId = (int) ($_POST['plano_id'] ?? 0);
            $recurso = strtolower(trim((string) ($_POST['recurso'] ?? '')));
            $recurso = preg_replace('/[^a-z0-9_]+/', '_', $recurso);
            $permitido = (isset($_POST['permitido']) && $_POST['permitido'] === 'Nao') ? 'Nao' : 'Sim';
            $limiteRaw = trim((string) ($_POST['limite'] ?? ''));
            $limite = $limiteRaw === '' ? null : max(0, (int) $limiteRaw);
            $periodo = (isset($_POST['periodo']) && $_POST['periodo'] === 'mensal') ? 'mensal' : 'total';

            if ($planoId <= 0 || $recurso === '') {
                throw new Exception('Informe plano e nome do recurso.');
            }

            $query = $pdo_saas->prepare("INSERT INTO planos_recursos (plano_id, recurso, permitido, limite, periodo)
                VALUES (:plano_id, :recurso, :permitido, :limite, :periodo)
                ON DUPLICATE KEY UPDATE permitido = VALUES(permitido), limite = VALUES(limite), periodo = VALUES(periodo)");
            $query->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
            $query->bindValue(':recurso', $recurso);
            $query->bindValue(':permitido', $permitido);
            if ($limite === null) {
                $query->bindValue(':limite', null, PDO::PARAM_NULL);
            } else {
                $query->bindValue(':limite', $limite, PDO::PARAM_INT);
            }
            $query->bindValue(':periodo', $periodo);
            $query->execute();

            admin_set_flash('success', 'Recurso salvo com sucesso.');
            $redirectParams['plan_id'] = $planoId;
            $redirectParams['edit'] = $planoId;
        }

        if ($action === 'delete_recurso') {
            $recursoId = (int) ($_POST['recurso_id'] ?? 0);
            $planoId = (int) ($_POST['plano_id'] ?? 0);
            if ($recursoId <= 0 || $planoId <= 0) {
                throw new Exception('Recurso invalido para exclusao.');
            }

            $query = $pdo_saas->prepare("DELETE FROM planos_recursos WHERE id = :id AND plano_id = :plano_id");
            $query->bindValue(':id', $recursoId, PDO::PARAM_INT);
            $query->bindValue(':plano_id', $planoId, PDO::PARAM_INT);
            $query->execute();

            admin_set_flash('success', 'Recurso removido.');
            $redirectParams['plan_id'] = $planoId;
            $redirectParams['edit'] = $planoId;
        }
    } catch (Exception $e) {
        admin_set_flash('danger', $e->getMessage());
    }

    admin_redirect('planos', $redirectParams);
}

$selectedPlanId = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

$planos = [];
$planoEdit = [
    'id' => 0,
    'nome' => '',
    'slug' => '',
    'descricao' => '',
    'trial_dias' => 14,
    'valor_mensal' => 79.90,
    'ativo' => 'Sim',
];
$recursos = [];

try {
    $query = $pdo_saas->query("SELECT
            p.id,
            p.nome,
            p.slug,
            p.descricao,
            p.trial_dias,
            p.valor_mensal,
            p.ativo,
            p.criado_em,
            COUNT(DISTINCT a.empresa_id) AS total_empresas,
            COUNT(DISTINCT r.id) AS total_recursos
        FROM planos p
        LEFT JOIN empresas_assinaturas a ON a.plano_id = p.id
        LEFT JOIN planos_recursos r ON r.plano_id = p.id
        GROUP BY p.id, p.nome, p.slug, p.descricao, p.trial_dias, p.valor_mensal, p.ativo, p.criado_em
        ORDER BY p.ativo DESC, p.id ASC");
    $planos = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedPlanId <= 0 && !empty($planos)) {
        $selectedPlanId = (int) $planos[0]['id'];
    }

    if ($editId > 0) {
        $queryEdit = $pdo_saas->prepare("SELECT * FROM planos WHERE id = :id LIMIT 1");
        $queryEdit->bindValue(':id', $editId, PDO::PARAM_INT);
        $queryEdit->execute();
        $planoEncontrado = $queryEdit->fetch(PDO::FETCH_ASSOC);
        if ($planoEncontrado) {
            $planoEdit = $planoEncontrado;
        }
    }

    if ($selectedPlanId > 0) {
        $queryRecursos = $pdo_saas->prepare("SELECT id, recurso, permitido, limite, periodo FROM planos_recursos WHERE plano_id = :plano_id ORDER BY recurso ASC");
        $queryRecursos->bindValue(':plano_id', $selectedPlanId, PDO::PARAM_INT);
        $queryRecursos->execute();
        $recursos = $queryRecursos->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    admin_set_flash('danger', 'Nao foi possivel carregar os planos: ' . $e->getMessage());
}
?>

<style>
    .planos-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 14px;
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

    .plan-box-body {
        padding: 14px 16px;
    }

    .resource-grid {
        margin-top: 14px;
    }

    @media (max-width: 1200px) {
        .planos-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .plan-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="planos-grid mb-3">
    <section class="panel-card">
        <header class="plan-header">
            <h3><i class="fa fa-layer-group mr-2"></i>Planos cadastrados (<?= count($planos) ?>)</h3>
            <a href="?page=planos" class="btn btn-sm btn-soft">Novo</a>
        </header>

        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plano</th>
                        <th>Trial</th>
                        <th>Valor</th>
                        <th>Empresas</th>
                        <th>Recursos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($planos)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Nenhum plano cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($planos as $plano): ?>
                        <tr class="<?= (int) $plano['id'] === $selectedPlanId ? 'table-primary' : '' ?>">
                            <td>#<?= (int) $plano['id'] ?></td>
                            <td>
                                <strong><?= admin_h($plano['nome']) ?></strong><br>
                                <small class="text-muted"><?= admin_h($plano['slug']) ?></small>
                            </td>
                            <td><?= (int) $plano['trial_dias'] ?> dias</td>
                            <td>R$ <?= number_format((float) $plano['valor_mensal'], 2, ',', '.') ?></td>
                            <td><?= (int) $plano['total_empresas'] ?></td>
                            <td><?= (int) $plano['total_recursos'] ?></td>
                            <td>
                                <span class="status-pill <?= $plano['ativo'] === 'Sim' ? 'status-success' : 'status-muted' ?>">
                                    <?= $plano['ativo'] === 'Sim' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="?page=planos&plan_id=<?= (int) $plano['id'] ?>&edit=<?= (int) $plano['id'] ?>" class="btn btn-sm btn-soft" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir este plano?');">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="delete_plano">
                                    <input type="hidden" name="plano_id" value="<?= (int) $plano['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <header class="plan-header">
            <h3>
                <i class="fa fa-pen mr-2"></i>
                <?= (int) $planoEdit['id'] > 0 ? 'Editar plano' : 'Novo plano' ?>
            </h3>
        </header>

        <div class="plan-box-body">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="save_plano">
                <input type="hidden" name="plano_id" value="<?= (int) $planoEdit['id'] ?>">
                <input type="hidden" name="return_plan_id" value="<?= (int) ($selectedPlanId > 0 ? $selectedPlanId : $planoEdit['id']) ?>">

                <div class="form-group mb-2">
                    <label class="mb-1">Nome</label>
                    <input type="text" name="nome" class="form-control" required value="<?= admin_h($planoEdit['nome']) ?>">
                </div>

                <div class="form-group mb-2">
                    <label class="mb-1">Slug</label>
                    <input type="text" name="slug" class="form-control" required value="<?= admin_h($planoEdit['slug']) ?>" placeholder="starter">
                </div>

                <div class="form-group mb-2">
                    <label class="mb-1">Descricao</label>
                    <textarea name="descricao" class="form-control" rows="3"><?= admin_h($planoEdit['descricao']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-sm-6 mb-2">
                        <label class="mb-1">Dias de trial</label>
                        <input type="number" min="0" name="trial_dias" class="form-control" value="<?= (int) $planoEdit['trial_dias'] ?>">
                    </div>
                    <div class="form-group col-sm-6 mb-2">
                        <label class="mb-1">Valor mensal (R$)</label>
                        <input type="text" name="valor_mensal" class="form-control" value="<?= admin_h(number_format((float) $planoEdit['valor_mensal'], 2, ',', '.')) ?>" placeholder="79,90">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-sm-6 mb-2">
                        <label class="mb-1">Status</label>
                        <select name="ativo" class="form-control">
                            <option value="Sim" <?= $planoEdit['ativo'] === 'Sim' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Nao" <?= $planoEdit['ativo'] === 'Nao' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-brand btn-sm"><i class="fa fa-save mr-1"></i>Salvar plano</button>
            </form>
        </div>
    </section>
</div>

<section class="panel-card resource-grid">
    <header class="plan-header">
        <h3><i class="fa fa-sliders-h mr-2"></i>Recursos do plano <?= $selectedPlanId > 0 ? '#' . (int) $selectedPlanId : '' ?></h3>
    </header>
    <div class="plan-box-body">
        <?php if ($selectedPlanId <= 0): ?>
            <div class="text-muted">Selecione um plano para configurar recursos.</div>
        <?php else: ?>
            <form method="post" class="mb-3 p-3" style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="save_recurso">
                <input type="hidden" name="plano_id" value="<?= (int) $selectedPlanId ?>">
                <input type="hidden" name="return_plan_id" value="<?= (int) $selectedPlanId ?>">
                <input type="hidden" name="return_edit_id" value="<?= (int) $selectedPlanId ?>">

                <div class="form-row">
                    <div class="form-group col-md-4 mb-2">
                        <label class="mb-1">Recurso</label>
                        <input type="text" name="recurso" class="form-control" placeholder="limite_usuarios" required>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label class="mb-1">Permitido</label>
                        <select name="permitido" class="form-control">
                            <option value="Sim">Sim</option>
                            <option value="Nao">Nao</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label class="mb-1">Limite</label>
                        <input type="number" min="0" name="limite" class="form-control" placeholder="vazio = ilimitado">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label class="mb-1">Periodo</label>
                        <select name="periodo" class="form-control">
                            <option value="total">Total</option>
                            <option value="mensal">Mensal</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-soft btn-block"><i class="fa fa-plus mr-1"></i>Salvar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-modern table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Permitido</th>
                            <th>Limite</th>
                            <th>Periodo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recursos)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Nenhum recurso configurado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recursos as $recurso): ?>
                                <tr>
                                    <td><code><?= admin_h($recurso['recurso']) ?></code></td>
                                    <td>
                                        <span class="status-pill <?= $recurso['permitido'] === 'Sim' ? 'status-success' : 'status-muted' ?>">
                                            <?= admin_h($recurso['permitido']) ?>
                                        </span>
                                    </td>
                                    <td><?= $recurso['limite'] !== null ? (int) $recurso['limite'] : '-' ?></td>
                                    <td><?= admin_h($recurso['periodo']) ?></td>
                                    <td class="text-right">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Remover recurso?');">
                                            <?= admin_csrf_input() ?>
                                            <input type="hidden" name="action" value="delete_recurso">
                                            <input type="hidden" name="recurso_id" value="<?= (int) $recurso['id'] ?>">
                                            <input type="hidden" name="plano_id" value="<?= (int) $selectedPlanId ?>">
                                            <input type="hidden" name="return_plan_id" value="<?= (int) $selectedPlanId ?>">
                                            <input type="hidden" name="return_edit_id" value="<?= (int) $selectedPlanId ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
