<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

include_once 'crud_template.php';
$crud = new CRUD_Template();

table = 'empresas_tunnels';
title = 'Tuneis SaaS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate();
    $action = admin_request_action();
    
    if ($action === 'create') {
        $data = [
            'empresa_id' => (int) ($_POST['empresa_id'] ?? 0),
            'tunnel_nome' => trim((string) ($_POST['tunnel_nome'] ?? '')),
            'tunnel_id' => null,
            'dominio' => trim((string) ($_POST['dominio'] ?? '')),
            'service_url' => trim((string) ($_POST['service_url'] ?? 'http://127.0.0.1:8000')),
            'tipo' => 'cloudflared',
            'status' => 'Inativo',
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        // Validação personalizada
        if ($data['tunnel_nome'] === '' || $data['dominio'] === '') {
            admin_set_flash('danger', 'Nome do tunnel e dominio sao obrigatorios.');
        } else {
            // Verificar se dominio já existe
            $check = $pdo_saas->prepare("SELECT id FROM empresas_tunnels WHERE dominio = :dominio LIMIT 1");
            $check->bindValue(':dominio', $data['dominio']);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                admin_set_flash('danger', 'Dominio já cadastrado em outro tunnel.');
            } else {
                // Criar tunnel cloudflared
                if (!saas_cloudflared_disponivel()) {
                    admin_set_flash('danger', 'cloudflared nao encontrado no servidor.');
                } else {
                    try {
                        $tunnel = saas_criar_tunnel($data['tunnel_nome']);
                        $data['tunnel_id'] = $tunnel['id'];
                        
                        if (saas_dominio_publico($data['dominio'])) {
                            saas_criar_dns_tunnel($data['tunnel_id'], $data['dominio']);
                        }
                        
                        $slug = $data['empresa_id'] > 0 ? 'empresa-' . $data['empresa_id'] : $data['tunnel_nome'];
                        $configPath = saas_salvar_config_tunnel($slug, $data['tunnel_id'], [$data['dominio']], $data['service_url']);
                        saas_iniciar_tunnel_background($slug, $data['tunnel_id'], $configPath);
                        $data['status'] = 'Ativo';
                        
                        // Usar CRUD Template
                        $result = $crud->handleCRUD($table, 'create', $data);
                        if ($result) {
                            admin_set_flash('success', 'Tunnel criado com sucesso.');
                            admin_redirect('tuneis');
                        } else {
                            admin_set_flash('danger', 'Erro ao criar tunnel.');
                        }
                    } catch (Exception $e) {
                        admin_set_flash('danger', 'Erro ao criar tunnel: ' . $e->getMessage());
                    }
                }
            }
        }
    }
    
    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'empresa_id' => (int) ($_POST['empresa_id'] ?? 0),
            'tunnel_nome' => trim((string) ($_POST['tunnel_nome'] ?? '')),
            'dominio' => trim((string) ($_POST['dominio'] ?? '')),
            'service_url' => trim((string) ($_POST['service_url'] ?? 'http://127.0.0.1:8000'))
        ];
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Tunnel inválido para edição.');
        } elseif ($data['tunnel_nome'] === '' || $data['dominio'] === '') {
            admin_set_flash('danger', 'Nome do tunnel e dominio são obrigatórios.');
        } else {
            // Verificar se dominio já existe para outro tunnel
            $check = $pdo_saas->prepare("SELECT id FROM empresas_tunnels WHERE dominio = :dominio AND id <> :id LIMIT 1");
            $check->bindValue(':dominio', $data['dominio']);
            $check->bindValue(':id', $id, PDO::PARAM_INT);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                admin_set_flash('danger', 'Dominio já utilizado por outro tunnel.');
            } else {
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'update', $data, $id);
                if ($result) {
                    admin_set_flash('success', 'Tunnel atualizado com sucesso.');
                    admin_redirect('tuneis');
                } else {
                    admin_set_flash('danger', 'Erro ao atualizar tunnel.');
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Tunnel inválido.');
        } else {
            // Verificar se tunnel está em execução
            $query = $pdo_saas->prepare("SELECT tunnel_id, status FROM empresas_tunnels WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $tunnel = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$tunnel) {
                admin_set_flash('danger', 'Tunnel não encontrado.');
            } else {
                // Parar processo se estiver rodando
                if ($tunnel['status'] === 'Ativo') {
                    $pattern = 'cloudflared.*' . preg_quote($tunnel['tunnel_id'], '/');
                    exec('pkill -f ' . escapeshellarg($pattern));
                }
                
                // Usar CRUD Template
                $result = $crud->handleCRUD($table, 'delete', [], $id);
                if ($result) {
                    admin_set_flash('success', 'Tunnel excluído com sucesso.');
                    admin_redirect('tuneis');
                } else {
                    admin_set_flash('danger', 'Erro ao excluir tunnel.');
                }
            }
        }
    }
    
    if ($action === 'start') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Tunnel inválido.');
        } else {
            // Verificar se tunnel existe
            $query = $pdo_saas->prepare("SELECT t.*, e.slug AS empresa_slug FROM empresas_tunnels t LEFT JOIN empresas e ON e.id = t.empresa_id WHERE t.id = :id LIMIT 1");
            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $tunnel = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$tunnel) {
                admin_set_flash('danger', 'Tunnel não encontrado.');
            } else {
                // Iniciar tunnel
                if (!saas_cloudflared_disponivel()) {
                    admin_set_flash('danger', 'cloudflared nao encontrado no servidor.');
                } else {
                    try {
                        $slugEmpresa = !empty($tunnel['empresa_slug']) ? saas_normalizar_slug($tunnel['empresa_slug']) : '';
                        $slugTunnel = saas_normalizar_slug($tunnel['tunnel_nome']);
                        $configDir = dirname(__DIR__) . '/tunnels';
                        
                        $candidatos = [];
                        if ($slugEmpresa !== '') {
                            $candidatos[] = $configDir . '/' . $slugEmpresa . '.yml';
                        }
                        $candidatos[] = $configDir . '/' . $slugTunnel . '.yml';
                        
                        $configPath = null;
                        foreach ($candidatos as $caminho) {
                            if (file_exists($caminho)) {
                                $configPath = $caminho;
                                break;
                            }
                        }
                        
                        if (!$configPath) {
                            admin_set_flash('danger', 'Arquivo de configuracao nao encontrado.');
                        } else {
                            $slugLog = $slugEmpresa !== '' ? $slugEmpresa : $slugTunnel;
                            saas_iniciar_tunnel_background($slugLog, $tunnel['tunnel_id'], $configPath);
                            
                            $update = $pdo_saas->prepare("UPDATE empresas_tunnels SET status = 'Ativo' WHERE id = :id");
                            $update->bindValue(':id', $id, PDO::PARAM_INT);
                            $update->execute();
                            
                            admin_set_flash('success', 'Tunnel iniciado.');
                            admin_redirect('tuneis');
                        }
                    } catch (Exception $e) {
                        admin_set_flash('danger', 'Erro ao iniciar tunnel: ' . $e->getMessage());
                    }
                }
            }
        }
    }
    
    if ($action === 'stop') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            admin_set_flash('danger', 'Tunnel inválido.');
        } else {
            // Verificar se tunnel existe
            $query = $pdo_saas->prepare("SELECT tunnel_id FROM empresas_tunnels WHERE id = :id LIMIT 1");
            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $tunnel = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$tunnel) {
                admin_set_flash('danger', 'Tunnel não encontrado.');
            } else {
                // Parar tunnel
                $pattern = 'cloudflared.*' . preg_quote($tunnel['tunnel_id'], '/');
                exec('pkill -f ' . escapeshellarg($pattern));
                
                $update = $pdo_saas->prepare("UPDATE empresas_tunnels SET status = 'Inativo' WHERE id = :id");
                $update->bindValue(':id', $id, PDO::PARAM_INT);
                $update->execute();
                
                admin_set_flash('success', 'Tunnel parado.');
                admin_redirect('tuneis');
            }
        }
    }
    
    if ($erro !== '') {
        admin_set_flash('danger', $erro);
    }
}

$tuneis = $crud->getAll($table);

// Calcular métricas
$metricas = [
    'total' => 0,
    'rodando' => 0,
    'parados' => 0
];

if (!empty($tuneis)) {
    $metricas['total'] = count($tuneis);
    foreach ($tuneis as $t) {
        $rodando = saas_tunnel_em_execucao($t['tunnel_id']);
        if ($rodando) {
            $metricas['rodando']++;
        } else {
            $metricas['parados']++;
        }
    }
}

?>

<style>
    .toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .tunel-metrics { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .tunel-metric { border: 1px solid #dbe5f1; background: #fff; border-radius: 12px; padding: 10px 12px; }
    .tunel-metric span { display: block; color: #64748b; font-size: 0.8rem; }
    .tunel-metric strong { font-size: 1.4rem; }
    
    .tunel-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 14px;
    }

    .tunel-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .tunel-panel-head h3 {
        margin: 0;
        font-size: 1rem;
    }

    .tunel-box-body {
        padding: 14px 16px;
    }

    .tunel-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        background: #fff;
    }

    .tunel-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .tunel-item-nome {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .tunel-item-dominio {
        font-size: 0.85rem;
        color: #64748b;
    }

    .tunel-badges {
        display: flex;
        gap: 6px;
        margin-top: 6px;
    }

    @media (max-width: 1200px) {
        .tunel-grid {
            grid-template-columns: 1fr;
        }
        .tunel-metrics { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="toolbar">
    <div>
        <strong><?= admin_h($title) ?></strong>
        <span class="text-muted ml-2">(<?= $metricas['total'] ?> cadastrados)</span>
    </div>
    <button type="button" class="btn btn-brand" data-toggle="modal" data-target="#modalNovoTunnel">
        <i class="fa fa-plus mr-1"></i> Novo Tunnel
    </button>
</div>

<div class="tunel-metrics">
    <article class="tunel-metric"><span>Total Cloudflared</span><strong><?= $metricas['total'] ?></strong></article>
    <article class="tunel-metric"><span>Rodando</span><strong><?= $metricas['rodando'] ?></strong></article>
    <article class="tunel-metric"><span>Parados</span><strong><?= $metricas['parados'] ?></strong></article>
    <article class="tunel-metric"><span>SSH Ngrok</span><strong><?= count($ngrokTunnels) > 0 ? 'ON' : 'OFF' ?></strong></article>
</div>

<div class="tunel-grid">
    <section class="panel-card">
        <header class="tunel-panel-head">
            <h3><i class="fa fa-network-wired mr-2"></i>Lista de tuneis</h3>
        </header>
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Tunnel</th>
                        <th>Dominio</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tuneis)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Nenhum tunnel encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tuneis as $t): ?>
                        <tr>
                            <td>#<?= (int) $t['id'] ?></td>
                            <td><?= admin_h($t['empresa_id'] ? 'Empresa #' . $t['empresa_id'] : 'Sem empresa') ?></td>
                            <td><strong><?= admin_h($t['tunnel_nome']) ?></strong></td>
                            <td><?= admin_h($t['dominio']) ?></td>
                            <td>
                                <span class="status-pill <?= $t['status'] === 'Ativo' ? 'status-success' : 'status-danger' ?>">
                                    <?= $t['status'] === 'Ativo' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-soft" onclick="editarTunel(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8') ?>)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="start">
                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                    <button class="btn btn-sm btn-success" type="submit" title="Iniciar">
                                        <i class="fa fa-play"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="stop">
                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" title="Parar">
                                        <i class="fa fa-stop"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir tunnel?');">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
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
        <header class="tunel-panel-head">
            <h3><i class="fa fa-info-circle mr-2"></i>Informações</h3>
        </header>
        <div class="tunel-box-body">
            <p class="text-muted mb-3">Os tuneis SaaS permitem acesso externo aos painéis das empresas de forma segura e controlada.</p>
            
            <div class="alert alert-info mb-3">
                <strong>Cloudflared:</strong> Ferramenta para criação de túneis seguros.
            </div>
            
            <div class="alert alert-warning mb-0">
                <strong>DNS:</strong> Configuração automática de DNS para domínios públicos.
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalNovoTunnel" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-plus mr-2"></i>Novo Tunnel</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Empresa (opcional)</label>
                        <select name="empresa_id" class="form-control">
                            <option value="">Sem empresa vinculada</option>
                            <?php 
                            $empresas = $pdo_saas->query("SELECT id, nome FROM empresas WHERE ativo = 'Sim' ORDER BY nome ASC");
                            $empresas = $empresas->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($empresas as $e): ?>
                                <option value="<?= (int) $e['id'] ?>"><?= admin_h($e['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nome do Tunnel *</label>
                        <input type="text" name="tunnel_nome" class="form-control" required placeholder="meu-tunnel">
                    </div>
                    <div class="form-group">
                        <label>Dominio *</label>
                        <input type="text" name="dominio" class="form-control" required placeholder="exemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Service URL</label>
                        <input type="text" name="service_url" class="form-control" value="http://127.0.0.1:8000" placeholder="http://localhost:8000">
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

<div class="modal fade" id="modalEditarTunel" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" id="formEditarTunel">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><?php if ((int) $t['id'] > 0): ?><i class="fa fa-edit mr-2"></i>Editar tunnel<?php else: ?><?php endif; ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Empresa</label>
                        <select name="empresa_id" id="edit_empresa_id" class="form-control">
                            <option value="">Sem empresa vinculada</option>
                            <?php 
                            $empresas = $pdo_saas->query("SELECT id, nome FROM empresas WHERE ativo = 'Sim' ORDER BY nome ASC");
                            $empresas = $empresas->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($empresas as $e): ?>
                                <option value="<?= (int) $e['id'] ?>"><?= admin_h($e['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nome do Tunnel *</label>
                        <input type="text" name="tunnel_nome" id="edit_tunnel_nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Dominio *</label>
                        <input type="text" name="dominio" id="edit_dominio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Service URL</label>
                        <input type="text" name="service_url" id="edit_service_url" class="form-control" placeholder="http://localhost:8000">
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
function editarTunel(tunel) {
    document.getElementById('edit_id').value = tunel.id;
    document.getElementById('edit_empresa_id').value = tunel.empresa_id;
    document.getElementById('edit_tunnel_nome').value = tunel.tunnel_nome;
    document.getElementById('edit_dominio').value = tunel.dominio;
    document.getElementById('edit_service_url').value = tunel.service_url;
    $('#modalEditarTunel').modal('show');
}
</script>