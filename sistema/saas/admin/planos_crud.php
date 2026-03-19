<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

include_once 'crud_template.php';
$crud = new CRUD_Template();

table = 'planos';
title = 'Planos SaaS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate();
    $action = admin_request_action();
    
    if ($action === 'create') {
        $data = [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'slug' => saas_normalizar_slug((string) ($_POST['slug'] ?? '')),
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'trial_dias' => max(0, (int) ($_POST['trial_dias'] ?? 14)),
            'valor_mensal' => 0,
            'ativo' => (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim'
        ];
        
        // Tratar valor mensal
        $valorMensalInput = trim((string) ($_POST['valor_mensal'] ?? ''));
        if (strpos($valorMensalInput, ',') !== false) {
            $valorMensalInput = str_replace('.', '', $valorMensalInput);
            $valorMensalInput = str_replace(',', '.', $valorMensalInput);
        }
        $valorMensalInput = preg_replace('/[^0-9.]+/', '', $valorMensalInput);
        $data['valor_mensal'] = (float) $valorMensalInput;
        
        // Validação personalizada
        if ($data['nome'] === '') {
            admin_set_flash('danger', 'Informe o nome do plano.');
        } elseif ($data['valor_mensal'] <= 0) {
            admin_set_flash('danger', 'Informe um valor mensal maior que zero.');
        } else {
            // Verificar se slug já existe
            $check = $pdo_saas->prepare("SELECT id FROM planos WHERE slug = :slug LIMIT 1");
            $check->bindValue(':slug', $data['slug']);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                admin_set_flash('danger', 'Slug de plano já utilizado.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'create', $data);
                if ($result) {
                    admin_set_flash('success', 'Plano criado com sucesso.');
                    admin_redirect('planos');
                } else {
                    admin_set_flash('danger', 'Erro ao criar plano.');
                }
            }
        }
    }
    
    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'slug' => saas_normalizar_slug((string) ($_POST['slug'] ?? '')),
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'trial_dias' => max(0, (int) ($_POST['trial_dias'] ?? 14)),
            'valor_mensal' => 0,
            'ativo' => (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim'
        ];
        
        // Tratar valor mensal
        $valorMensalInput = trim((string) ($_POST['valor_mensal'] ?? ''));
        if (strpos($valorMensalInput, ',') !== false) {
            $valorMensalInput = str_replace('.', '', $valorMensalInput);
            $valorMensalInput = str_replace(',', '.', $valorMensalInput);
        }
        $valorMensalInput = preg_replace('/[^0-9.]+/', '', $valorMensalInput);
        $data['valor_mensal'] = (float) $valorMensalInput;
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Plano inválido para edição.');
        } elseif ($data['nome'] === '') {
            admin_set_flash('danger', 'Informe o nome do plano.');
        } elseif ($data['valor_mensal'] <= 0) {
            admin_set_flash('danger', 'Informe um valor mensal maior que zero.');
        } else {
            // Verificar se slug já existe para outro plano
            $check = $pdo_saas->prepare("SELECT id FROM planos WHERE slug = :slug AND id <> :id LIMIT 1");
            $check->bindValue(':slug', $data['slug']);
            $check->bindValue(':id', $id, PDO::PARAM_INT);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                admin_set_flash('danger', 'Slug já utilizado por outro plano.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'update', $data, $id);
                if ($result) {
                    admin_set_flash('success', 'Plano atualizado com sucesso.');
                    admin_redirect('planos');
                } else {
                    admin_set_flash('danger', 'Erro ao atualizar plano.');
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Plano inválido.');
        } else {
            // Verificar se plano tem assinaturas
            $check = $pdo_saas->prepare("SELECT COUNT(*) FROM empresas_assinaturas WHERE plano_id = :id");
            $check->bindValue(':id', $id, PDO::PARAM_INT);
            $check->execute();
            $count = $check->fetchColumn();
            
            if ($count > 0) {
                admin_set_flash('danger', 'Não é possível excluir plano com assinaturas vinculadas.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'delete', [], $id);
                if ($result) {
                    admin_set_flash('success', 'Plano excluído com sucesso.');
                    admin_redirect('planos');
                } else {
                    admin_set_flash('danger', 'Erro ao excluir plano.');
                }
            }
        }
    }
    
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Plano inválido.');
        } else {
            // Verificar status atual
            $query = $pdo_saas->prepare("SELECT ativo FROM planos WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $plano = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$plano) {
                admin_set_flash('danger', 'Plano não encontrado.');
            } else {
                $novoStatus = $plano['ativo'] === 'Sim' ? 'Nao' : 'Sim';
                $update = $pdo_saas->prepare("UPDATE planos SET ativo = :status WHERE id = :id");
                $update->bindValue(':status', $novoStatus);
                $update->bindValue(':id', $id, PDO::PARAM_INT);
                $update->execute();
                admin_set_flash('success', 'Status do plano alterado para ' . $novoStatus . '.');
                admin_redirect('planos');
            }
        }
    }
    
    if ($erro !== '') {
        admin_set_flash('danger', $erro);
    }
}

$planos = $crud->getAll($table);

?>

<style>
    .toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .planos-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 14px;
    }

    .plan-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .plan-panel-head h3 {
        margin: 0;
        font-size: 1rem;
    }

    .plan-box-body {
        padding: 14px 16px;
    }

    .plan-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        background: #fff;
    }

    .plan-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .plan-item-nome {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .plan-item-slug {
        font-size: 0.85rem;
        color: #64748b;
    }

    .plan-badges {
        display: flex;
        gap: 6px;
        margin-top: 6px;
    }

    @media (max-width: 1200px) {
        .planos-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="toolbar">
    <div>
        <strong><?= admin_h($title) ?></strong>
        <span class="text-muted ml-2">(<?= count($planos) ?> cadastrados)</span>
    </div>
    <button type="button" class="btn btn-brand" data-toggle="modal" data-target="#modalNovoPlano">
        <i class="fa fa-plus mr-1"></i> Novo plano
    </button>
</div>

<div class="planos-grid">
    <section class="panel-card">
        <header class="plan-panel-head">
            <h3><i class="fa fa-layer-group mr-2"></i>Lista de planos</h3>
        </header>
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plano</th>
                        <th>Slug</th>
                        <th>Trial</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($planos)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nenhum plano encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($planos as $plano): ?>
                        <tr>
                            <td>#<?= (int) $plano['id'] ?></td>
                            <td><strong><?= admin_h($plano['nome']) ?></strong></td>
                            <td><small><?= admin_h($plano['slug']) ?></small></td>
                            <td><?= (int) $plano['trial_dias'] ?> dias</td>
                            <td>R$ <?= number_format((float) $plano['valor_mensal'], 2, ',', '.') ?></td>
                            <td>
                                <span class="status-pill <?= $plano['ativo'] === 'Sim' ? 'status-success' : 'status-muted' ?>">
                                    <?= $plano['ativo'] === 'Sim' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-soft" onclick="editarPlano(<?= htmlspecialchars(json_encode($plano), ENT_QUOTES, 'UTF-8') ?>)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= (int) $plano['id'] ?>">
                                    <button class="btn btn-sm <?= $plano['ativo'] === 'Sim' ? 'btn-danger' : 'btn-success' ?>" type="submit" title="<?= $plano['ativo'] === 'Sim' ? 'Desativar' : 'Ativar' ?>">
                                        <i class="fa <?= $plano['ativo'] === 'Sim' ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir este plano?');">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $plano['id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" title="Excluir">
                                        <i class="fa fa-trash"></i>
                                    </button>
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
        <header class="plan-panel-head">
            <h3><i class="fa fa-info-circle mr-2"></i>Informações</h3>
        </header>
        <div class="plan-box-body">
            <p class="text-muted mb-3">Os planos SaaS definem as funcionalidades e limites disponíveis para cada empresa.</p>
            
            <div class="alert alert-info mb-3">
                <strong>Trial:</strong> Período de teste gratuito para novas empresas.
            </div>
            
            <div class="alert alert-warning mb-0">
                <strong>Valor:</strong> Valor mensal do plano em Reais (R$).
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalNovoPlano" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-layer-group mr-2"></i>Novo plano</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" class="form-control" required placeholder="starter">
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" class="form-control" rows="3" placeholder="Descrição do plano (opcional)"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Dias de trial</label>
                            <input type="number" min="0" name="trial_dias" class="form-control" value="14">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Valor mensal (R$)</label>
                            <input type="text" name="valor_mensal" class="form-control" value="79,90" placeholder="79,90">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="ativo" class="form-control">
                            <option value="Sim">Ativo</option>
                            <option value="Nao">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><?= admin_h($title) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarPlano" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" id="formEditarPlano">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit mr-2"></i>Editar plano</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" id="edit_slug" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" id="edit_descricao" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Dias de trial</label>
                            <input type="number" min="0" name="trial_dias" id="edit_trial_dias" class="form-control" value="14">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Valor mensal (R$)</label>
                            <input type="text" name="valor_mensal" id="edit_valor_mensal" class="form-control" placeholder="79,90">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="ativo" id="edit_ativo" class="form-control">
                            <option value="Sim">Ativo</option>
                            <option value="Nao">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="fa fa-save mr-1"></i>Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarPlano(plano) {
    document.getElementById('edit_id').value = plano.id;
    document.getElementById('edit_nome').value = plano.nome;
    document.getElementById('edit_slug').value = plano.slug;
    document.getElementById('edit_descricao').value = plano.descricao;
    document.getElementById('edit_trial_dias').value = plano.trial_dias;
    document.getElementById('edit_valor_mensal').value = plano.valor_mensal.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('edit_ativo').value = plano.ativo;
    $('#modalEditarPlano').modal('show');
}
</script>