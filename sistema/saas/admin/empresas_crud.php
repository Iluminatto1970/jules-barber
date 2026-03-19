<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

include_once 'crud_template.php';
$crud = new CRUD_Template();

table = 'empresas';
title = 'Empresas SaaS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate();
    $action = admin_request_action();
    
    if ($action === 'create') {
        $data = [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'banco' => trim((string) ($_POST['banco'] ?? '')),
            'db_host' => trim((string) ($_POST['db_host'] ?? '')),
            'db_usuario' => trim((string) ($_POST['db_usuario'] ?? '')),
            'db_senha' => (string) ($_POST['db_senha'] ?? ''),
            'ativo' => (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim'
        ];
        
        // Validação personalizada
        if ($data['nome'] === '' || $data['slug'] === '' || $data['banco'] === '' || $data['db_host'] === '' || $data['db_usuario'] === '') {
            admin_set_flash('danger', 'Preencha todos os campos obrigatórios da empresa.');
        } else {
            // Verificar se slug já existe
            $check = $pdo_saas->prepare("SELECT id FROM empresas WHERE slug = :slug LIMIT 1");
            $check->bindValue(':slug', $data['slug']);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                admin_set_flash('danger', 'Slug já utilizado por outra empresa.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'create', $data);
                if ($result) {
                    admin_set_flash('success', 'Empresa criada com sucesso.');
                    admin_redirect('empresas');
                } else {
                    admin_set_flash('danger', 'Erro ao criar empresa.');
                }
            }
        }
    }
    
    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'banco' => trim((string) ($_POST['banco'] ?? '')),
            'db_host' => trim((string) ($_POST['db_host'] ?? '')),
            'db_usuario' => trim((string) ($_POST['db_usuario'] ?? '')),
            'db_senha' => (string) ($_POST['db_senha'] ?? ''),
            'ativo' => (isset($_POST['ativo']) && $_POST['ativo'] === 'Nao') ? 'Nao' : 'Sim'
        ];
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Empresa inválida para edição.');
        } elseif ($data['nome'] === '' || $data['slug'] === '' || $data['banco'] === '' || $data['db_host'] === '' || $data['db_usuario'] === '') {
            admin_set_flash('danger', 'Preencha os campos obrigatórios da empresa.');
        } else {
            // Verificar se slug já existe para outra empresa
            $check = $pdo_saas->prepare("SELECT id FROM empresas WHERE slug = :slug AND id <> :id LIMIT 1");
            $check->bindValue(':slug', $data['slug']);
            $check->bindValue(':id', $id, PDO::PARAM_INT);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                admin_set_flash('danger', 'Slug já utilizado por outra empresa.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'update', $data, $id);
                if ($result) {
                    admin_set_flash('success', 'Empresa atualizada com sucesso.');
                    admin_redirect('empresas');
                } else {
                    admin_set_flash('danger', 'Erro ao atualizar empresa.');
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Empresa inválida.');
        } else {
            // Verificar se empresa tem assinaturas
            $check = $pdo_saas->prepare("SELECT COUNT(*) FROM empresas_assinaturas WHERE empresa_id = :id");
            $check->bindValue(':id', $id, PDO::PARAM_INT);
            $check->execute();
            $count = $check->fetchColumn();
            
            if ($count > 0) {
                admin_set_flash('danger', 'Não é possível excluir empresa com assinaturas ativas.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'delete', [], $id);
                if ($result) {
                    admin_set_flash('success', 'Empresa excluída com sucesso.');
                    admin_redirect('empresas');
                } else {
                    admin_set_flash('danger', 'Erro ao excluir empresa.');
                }
            }
        }
    }
    
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Empresa inv́lida.');
        } else {
            // Verificar status atual
            $query = $pdo_saas->prepare("SELECT ativo FROM empresas WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $empresa = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$empresa) {
                admin_set_flash('danger', 'Empresa não encontrada.');
            } else {
                $novoStatus = $empresa['ativo'] === 'Sim' ? 'Nao' : 'Sim';
                $update = $pdo_saas->prepare("UPDATE empresas SET ativo = :status WHERE id = :id");
                $update->bindValue(':status', $novoStatus);
                $update->bindValue(':id', $id, PDO::PARAM_INT);
                $update->execute();
                admin_set_flash('success', 'Status da empresa alterado para ' . $novoStatus . '.');
                admin_redirect('empresas');
            }
        }
    }
    
    if ($erro !== '') {
        admin_set_flash('danger', $erro);
    }
}

$empresas = $crud->getAll($table);

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

    .empresa-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
        gap: 14px;
    }

    .empresa-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .empresa-panel-head h3 {
        margin: 0;
        font-size: 1.02rem;
    }

    .empresa-card-body {
        padding: 15px;
    }

    .empresa-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        background: #fff;
    }

    .empresa-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .empresa-item-nome {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .empresa-item-slug {
        font-size: 0.85rem;
        color: #64748b;
    }

    .empresa-badges {
        display: flex;
        gap: 6px;
        margin-top: 6px;
    }

    @media (max-width: 1200px) {
        .empresa-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="toolbar">
    <div>
        <strong><?= admin_h($title) ?></strong>
        <span class="text-muted ml-2">(<?= count($empresas) ?> cadastradas)</span>
    </div>
    <button type="button" class="btn btn-brand" data-toggle="modal" data-target="#modalNovaEmpresa">
        <i class="fa fa-plus mr-1"></i> Nova empresa
    </button>
</div>

<div class="empresa-grid">
    <section class="panel-card">
        <header class="empresa-panel-head">
            <h3><i class="fa fa-building mr-2"></i>Lista de empresas</h3>
        </header>
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Slug</th>
                        <th>Banco</th>
                        <th>Status</th>
                        <th>DB Host</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($empresas)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td>#<?= (int) $empresa['id'] ?></td>
                            <td><strong><?= admin_h($empresa['nome']) ?></strong></td>
                            <td><small><?= admin_h($empresa['slug']) ?></small></td>
                            <td><small><?= admin_h($empresa['banco']) ?></small></td>
                            <td>
                                <span class="status-pill <?= $empresa['ativo'] === 'Sim' ? 'status-success' : 'status-danger' ?>">
                                    <?= $empresa['ativo'] === 'Sim' ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td><small><?= admin_h($empresa['db_host']) ?></small></td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-soft" onclick="editarEmpresa(<?= htmlspecialchars(json_encode($empresa), ENT_QUOTES, 'UTF-8') ?>)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= (int) $empresa['id'] ?>">
                                    <button class="btn btn-sm <?= $empresa['ativo'] === 'Sim' ? 'btn-danger' : 'btn-success' ?>" type="submit" title="<?= $empresa['ativo'] === 'Sim' ? 'Desativar' : 'Ativar' ?>">
                                        <i class="fa <?= $empresa['ativo'] === 'Sim' ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir esta empresa?');">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $empresa['id'] ?>">
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
        <header class="empresa-panel-head">
            <h3><i class="fa fa-info-circle mr-2"></i>Informações</h3>
        </header>
        <div class="empresa-card-body">
            <p class="text-muted mb-3">As empresas são os tenants do sistema SaaS. Cada empresa tem seu próprio banco de dados e painel de controle.</p>
            
            <div class="alert alert-info mb-3">
                <strong>Banco tenant:</strong> Cada empresa possui um banco de dados separado para isolamento de dados.
            </div>
            
            <div class="alert alert-warning mb-0">
                <strong>Dominios:</strong> Cada empresa pode ter múltiplos domínios associados ao seu painel.
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalNovaEmpresa" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-building mr-2"></i>Nova empresa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" class="form-control" required placeholder="nome-da-empresa">
                    </div>
                    <div class="form-group">
                        <label>Banco tenant *</label>
                        <input type="text" name="banco" class="form-control" required placeholder="nome_banco_empresa">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>DB Host *</label>
                            <input type="text" name="db_host" class="form-control" required value="127.0.0.1">
                        </div>
                        <div class="form-group col-md-6">
                            <label>DB Usuario *</label>
                            <input type="text" name="db_usuario" class="form-control" required value="root">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>DB Senha</label>
                            <input type="password" name="db_senha" class="form-control" placeholder="Deixe em branco para manter atual">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="ativo" class="form-control">
                                <option value="Sim">Ativo</option>
                                <option value="Nao">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="fa fa-save mr-1"></i>Criar empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarEmpresa" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" id="formEditarEmpresa">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit mr-2"></i>Editar empresa</h5>
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
                        <label>Banco tenant *</label>
                        <input type="text" name="banco" id="edit_banco" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>DB Host *</label>
                            <input type="text" name="db_host" id="edit_db_host" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>DB Usuario *</label>
                            <input type="text" name="db_usuario" id="edit_db_usuario" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>DB Senha (opcional)</label>
                            <input type="password" name="db_senha" class="form-control" placeholder="Deixe em branco para manter atual">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="ativo" id="edit_ativo" class="form-control">
                                <option value="Sim">Ativo</option>
                                <option value="Nao">Inativo</option>
                            </select>
                        </div>
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
function editarEmpresa(empresa) {
    document.getElementById('edit_id').value = empresa.id;
    document.getElementById('edit_nome').value = empresa.nome;
    document.getElementById('edit_slug').value = empresa.slug;
    document.getElementById('edit_banco').value = empresa.banco;
    document.getElementById('edit_db_host').value = empresa.db_host;
    document.getElementById('edit_db_usuario').value = empresa.db_usuario;
    document.getElementById('edit_ativo').value = empresa.ativo;
    $('#modalEditarEmpresa').modal('show');
}
</script>