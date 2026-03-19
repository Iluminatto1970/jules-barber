<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate();
    $action = admin_request_action();

    if ($action === 'create_admin') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');
        $senhaConfirma = (string) ($_POST['senha_confirma'] ?? '');
        $ativo = isset($_POST['ativo']) && $_POST['ativo'] === 'Sim' ? 'Sim' : 'Nao';
        $superAdmin = isset($_POST['super_admin']) && $_POST['super_admin'] === '1' ? 1 : 0;

        if ($nome === '' || $email === '' || $senha === '') {
            $erro = 'Preencha todos os campos obrigatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Email invalido.';
        } elseif (strlen($senha) < 6) {
            $erro = 'A senha deve ter no minimo 6 caracteres.';
        } elseif ($senha !== $senhaConfirma) {
            $erro = 'As senhas nao conferem.';
        } else {
            $check = $pdo_saas->prepare("SELECT id FROM saas_admins WHERE email = :email LIMIT 1");
            $check->bindValue(':email', $email);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $erro = 'Ja existe um administrador com este email.';
            } else {
                $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
                $insert = $pdo_saas->prepare("INSERT INTO saas_admins (nome, email, senha, ativo, super_admin, criado_em) VALUES (:nome, :email, :senha, :ativo, :super_admin, NOW())");
                $insert->bindValue(':nome', $nome);
                $insert->bindValue(':email', $email);
                $insert->bindValue(':senha', $hash);
                $insert->bindValue(':ativo', $ativo);
                $insert->bindValue(':super_admin', $superAdmin, PDO::PARAM_INT);
                $insert->execute();
                admin_set_flash('success', 'Administrador criado com sucesso.');
                admin_redirect('admins');
            }
        }
    }

    if ($action === 'update_admin') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');
        $senhaConfirma = (string) ($_POST['senha_confirma'] ?? '');
        $ativo = isset($_POST['ativo']) && $_POST['ativo'] === 'Sim' ? 'Sim' : 'Nao';
        $superAdmin = isset($_POST['super_admin']) && $_POST['super_admin'] === '1' ? 1 : 0;

        if ($adminId <= 0) {
            $erro = 'Administrador invalido.';
        } elseif ($nome === '' || $email === '') {
            $erro = 'Preencha todos os campos obrigatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Email invalido.';
        } elseif ($senha !== '' && strlen($senha) < 6) {
            $erro = 'A senha deve ter no minimo 6 caracteres.';
        } elseif ($senha !== '' && $senha !== $senhaConfirma) {
            $erro = 'As senhas nao conferem.';
        } else {
            $check = $pdo_saas->prepare("SELECT id FROM saas_admins WHERE email = :email AND id <> :id LIMIT 1");
            $check->bindValue(':email', $email);
            $check->bindValue(':id', $adminId, PDO::PARAM_INT);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $erro = 'Ja existe outro administrador com este email.';
            } else {
                if ($senha !== '') {
                    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
                    $update = $pdo_saas->prepare("UPDATE saas_admins SET nome = :nome, email = :email, senha = :senha, ativo = :ativo, super_admin = :super_admin WHERE id = :id");
                    $update->bindValue(':senha', $hash);
                } else {
                    $update = $pdo_saas->prepare("UPDATE saas_admins SET nome = :nome, email = :email, ativo = :ativo, super_admin = :super_admin WHERE id = :id");
                }
                $update->bindValue(':nome', $nome);
                $update->bindValue(':email', $email);
                $update->bindValue(':ativo', $ativo);
                $update->bindValue(':super_admin', $superAdmin, PDO::PARAM_INT);
                $update->bindValue(':id', $adminId, PDO::PARAM_INT);
                $update->execute();
                admin_set_flash('success', 'Administrador atualizado com sucesso.');
                admin_redirect('admins');
            }
        }
    }

    if ($action === 'toggle_admin') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        if ($adminId <= 0) {
            $erro = 'Administrador invalido.';
        } else {
            $query = $pdo_saas->prepare("SELECT id, ativo FROM saas_admins WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $adminId, PDO::PARAM_INT);
            $query->execute();
            $admin = $query->fetch(PDO::FETCH_ASSOC);
            if (!$admin) {
                $erro = 'Administrador nao encontrado.';
            } else {
                $novoStatus = $admin['ativo'] === 'Sim' ? 'Nao' : 'Sim';
                $update = $pdo_saas->prepare("UPDATE saas_admins SET ativo = :status WHERE id = :id");
                $update->bindValue(':status', $novoStatus);
                $update->bindValue(':id', $adminId, PDO::PARAM_INT);
                $update->execute();
                admin_set_flash('success', 'Status do administrador alterado para ' . $novoStatus . '.');
                admin_redirect('admins');
            }
        }
    }

    if ($action === 'delete_admin') {
        $adminId = (int) ($_POST['admin_id'] ?? 0);
        if ($adminId <= 0) {
            $erro = 'Administrador invalido.';
        } else {
            $query = $pdo_saas->prepare("SELECT id, super_admin FROM saas_admins WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $adminId, PDO::PARAM_INT);
            $query->execute();
            $admin = $query->fetch(PDO::FETCH_ASSOC);
            if (!$admin) {
                $erro = 'Administrador nao encontrado.';
            } elseif ((int) $admin['super_admin'] === 1) {
                $erro = 'Nao e possivel excluir um super administrador.';
            } else {
                $delete = $pdo_saas->prepare("DELETE FROM saas_admins WHERE id = :id");
                $delete->bindValue(':id', $adminId, PDO::PARAM_INT);
                $delete->execute();
                admin_set_flash('success', 'Administrador excluido com sucesso.');
                admin_redirect('admins');
            }
        }
    }

    if ($erro !== '') {
        admin_set_flash('danger', $erro);
    }
}

$admins = [];
try {
    $query = $pdo_saas->query("SELECT id, nome, email, ativo, super_admin, criado_em, atualizado_em FROM saas_admins ORDER BY id ASC");
    $admins = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pdo_saas->query("CREATE TABLE IF NOT EXISTS saas_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        ativo ENUM('Sim', 'Nao') DEFAULT 'Sim',
        super_admin TINYINT(1) DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $count = $pdo_saas->query("SELECT COUNT(*) FROM saas_admins")->fetchColumn();
    if ((int) $count === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo_saas->prepare("INSERT INTO saas_admins (nome, email, senha, ativo, super_admin) VALUES ('Administrador', 'admin@superzap.fun', ?, 'Sim', 1)")->execute([$hash]);
    }
    
    $query = $pdo_saas->query("SELECT id, nome, email, ativo, super_admin, criado_em, atualizado_em FROM saas_admins ORDER BY id ASC");
    $admins = $query->fetchAll(PDO::FETCH_ASSOC);
}
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

    .admin-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 14px;
    }

    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .panel-head h3 {
        margin: 0;
        font-size: 1.02rem;
    }

    .card-body {
        padding: 15px;
    }

    .admin-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        background: #fff;
    }

    .admin-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .admin-item-nome {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .admin-item-email {
        font-size: 0.85rem;
        color: #64748b;
    }

    .admin-badges {
        display: flex;
        gap: 6px;
        margin-top: 6px;
    }

    @media (max-width: 1200px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="toolbar">
    <div>
        <strong>Administradores SaaS</strong>
        <span class="text-muted ml-2">(<?= count($admins) ?> cadastrados)</span>
    </div>
    <button type="button" class="btn btn-brand" data-toggle="modal" data-target="#modalNovoAdmin">
        <i class="fa fa-plus mr-1"></i> Novo administrador
    </button>
</div>

<div class="admin-grid">
    <section class="panel-card">
        <header class="panel-head">
            <h3><i class="fa fa-user-shield mr-2"></i>Lista de administradores</h3>
        </header>
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nenhum administrador cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>#<?= (int) $admin['id'] ?></td>
                            <td><strong><?= admin_h($admin['nome']) ?></strong></td>
                            <td><small><?= admin_h($admin['email']) ?></small></td>
                            <td>
                                <?php if ((int) $admin['super_admin'] === 1): ?>
                                    <span class="status-pill status-warning">Super Admin</span>
                                <?php else: ?>
                                    <span class="status-pill status-info">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill <?= $admin['ativo'] === 'Sim' ? 'status-success' : 'status-danger' ?>">
                                    <?= $admin['ativo'] === 'Sim' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td><small><?= admin_h(admin_format_date($admin['criado_em'])) ?></small></td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-soft" onclick="editarAdmin(<?= htmlspecialchars(json_encode($admin), ENT_QUOTES, 'UTF-8') ?>)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="admin_id" value="<?= (int) $admin['id'] ?>">
                                    <button class="btn btn-sm <?= $admin['ativo'] === 'Sim' ? 'btn-danger' : 'btn-success' ?>" type="submit" title="<?= $admin['ativo'] === 'Sim' ? 'Desativar' : 'Ativar' ?>">
                                        <i class="fa <?= $admin['ativo'] === 'Sim' ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </button>
                                </form>
                                <?php if ((int) $admin['super_admin'] !== 1): ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir este administrador?');">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="delete_admin">
                                    <input type="hidden" name="admin_id" value="<?= (int) $admin['id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" title="Excluir">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card">
        <header class="panel-head">
            <h3><i class="fa fa-info-circle mr-2"></i>Informacoes</h3>
        </header>
        <div class="card-body">
            <p class="text-muted mb-3">Os administradores SaaS gerenciam todas as empresas, planos, assinaturas e tuneis da plataforma.</p>
            
            <div class="alert alert-info mb-3">
                <strong>Super Admin:</strong> Acesso completo, nao pode ser excluido.
            </div>
            
            <div class="alert alert-warning mb-0">
                <strong>Admin:</strong> Pode gerenciar empresas e assinaturas, mas nao pode excluir outros admins.
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalNovoAdmin" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="create_admin">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-user-plus mr-2"></i>Novo administrador</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Senha *</label>
                            <input type="password" name="senha" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Confirmar senha *</label>
                            <input type="password" name="senha_confirma" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="ativo" class="form-control">
                                <option value="Sim">Ativo</option>
                                <option value="Nao">Inativo</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tipo</label>
                            <select name="super_admin" class="form-control">
                                <option value="0">Admin</option>
                                <option value="1">Super Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="fa fa-save mr-1"></i>Criar administrador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarAdmin" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" id="formEditarAdmin">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="update_admin">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit mr-2"></i>Editar administrador</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nova senha (deixe em branco para manter)</label>
                            <input type="password" name="senha" class="form-control" minlength="6">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Confirmar nova senha</label>
                            <input type="password" name="senha_confirma" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="ativo" id="edit_ativo" class="form-control">
                                <option value="Sim">Ativo</option>
                                <option value="Nao">Inativo</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tipo</label>
                            <select name="super_admin" id="edit_super_admin" class="form-control">
                                <option value="0">Admin</option>
                                <option value="1">Super Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="fa fa-save mr-1"></i>Salvar alteracoes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarAdmin(admin) {
    document.getElementById('edit_admin_id').value = admin.id;
    document.getElementById('edit_nome').value = admin.nome;
    document.getElementById('edit_email').value = admin.email;
    document.getElementById('edit_ativo').value = admin.ativo;
    document.getElementById('edit_super_admin').value = admin.super_admin;
    document.getElementById('formEditarAdmin').querySelector('input[name="senha"]').value = '';
    document.getElementById('formEditarAdmin').querySelector('input[name="senha_confirma"]').value = '';
    $('#modalEditarAdmin').modal('show');
}
</script>
