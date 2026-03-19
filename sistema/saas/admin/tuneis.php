<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_csrf_validate();
        $action = admin_request_action();

        if ($action === 'create_tunnel') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0);
            $tunnelNome = saas_normalizar_tunnel_nome(trim((string) ($_POST['tunnel_nome'] ?? '')));
            $dominio = saas_normalizar_dominio(trim((string) ($_POST['dominio'] ?? '')));
            $serviceUrl = trim((string) ($_POST['service_url'] ?? 'http://127.0.0.1:8000'));
            $tipo = trim((string) ($_POST['tipo'] ?? 'cloudflared'));

            if ($tunnelNome === '' || $dominio === '') {
                throw new Exception('Nome do tunnel e dominio sao obrigatorios.');
            }

            $tunnelId = null;
            $status = 'Inativo';

            if ($tipo === 'cloudflared') {
                if (!saas_cloudflared_disponivel()) {
                    throw new Exception('cloudflared nao encontrado no servidor.');
                }

                $tunnel = saas_criar_tunnel($tunnelNome);
                $tunnelId = $tunnel['id'];

                if (saas_dominio_publico($dominio)) {
                    saas_criar_dns_tunnel($tunnelId, $dominio);
                }

                $slug = $empresaId > 0 ? 'empresa-' . $empresaId : $tunnelNome;
                $configPath = saas_salvar_config_tunnel($slug, $tunnelId, [$dominio], $serviceUrl);
                saas_iniciar_tunnel_background($slug, $tunnelId, $configPath);
                $status = 'Ativo';
            }

            $insert = $pdo_saas->prepare("INSERT INTO empresas_tunnels (empresa_id, tunnel_nome, tunnel_id, dominio, service_url, tipo, status, criado_em) VALUES (:empresa_id, :tunnel_nome, :tunnel_id, :dominio, :service_url, :tipo, :status, NOW())");
            $insert->bindValue(':empresa_id', $empresaId > 0 ? $empresaId : null, $empresaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->bindValue(':tunnel_nome', $tunnelNome);
            $insert->bindValue(':tunnel_id', $tunnelId ?: null, $tunnelId ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insert->bindValue(':dominio', $dominio);
            $insert->bindValue(':service_url', $serviceUrl);
            $insert->bindValue(':tipo', $tipo);
            $insert->bindValue(':status', $status);
            $insert->execute();

            admin_set_flash('success', 'Tunnel criado com sucesso.');
        }

        if ($action === 'start_tunnel') {
            $tunnelId = trim((string) ($_POST['tunnel_id'] ?? ''));

            $queryTunnel = $pdo_saas->prepare("SELECT t.*, e.slug AS empresa_slug FROM empresas_tunnels t LEFT JOIN empresas e ON e.id = t.empresa_id WHERE t.tunnel_id = :tunnel_id LIMIT 1");
            $queryTunnel->bindValue(':tunnel_id', $tunnelId);
            $queryTunnel->execute();
            $tunnel = $queryTunnel->fetch(PDO::FETCH_ASSOC);

            if (!$tunnel) {
                throw new Exception('Tunnel nao encontrado.');
            }

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
                throw new Exception('Arquivo de configuracao nao encontrado.');
            }

            $slugLog = $slugEmpresa !== '' ? $slugEmpresa : $slugTunnel;
            saas_iniciar_tunnel_background($slugLog, $tunnelId, $configPath);

            $update = $pdo_saas->prepare("UPDATE empresas_tunnels SET status = 'Ativo' WHERE tunnel_id = :tunnel_id");
            $update->bindValue(':tunnel_id', $tunnelId);
            $update->execute();

            admin_set_flash('success', 'Tunnel iniciado.');
        }

        if ($action === 'stop_tunnel') {
            $tunnelId = trim((string) ($_POST['tunnel_id'] ?? ''));
            $pattern = 'cloudflared.*' . preg_quote($tunnelId, '/');
            exec('pkill -f ' . escapeshellarg($pattern));

            $update = $pdo_saas->prepare("UPDATE empresas_tunnels SET status = 'Inativo' WHERE tunnel_id = :tunnel_id");
            $update->bindValue(':tunnel_id', $tunnelId);
            $update->execute();

            admin_set_flash('success', 'Tunnel parado.');
        }

        if ($action === 'delete_tunnel') {
            $tunnelId = trim((string) ($_POST['tunnel_id'] ?? ''));

            $pattern = 'cloudflared.*' . preg_quote($tunnelId, '/');
            exec('pkill -f ' . escapeshellarg($pattern));

            $delete = $pdo_saas->prepare("DELETE FROM empresas_tunnels WHERE tunnel_id = :tunnel_id");
            $delete->bindValue(':tunnel_id', $tunnelId);
            $delete->execute();

            admin_set_flash('success', 'Tunnel excluido.');
        }

        if ($action === 'bulk_action') {
            $bulkAction = trim((string) ($_POST['bulk_action'] ?? ''));
            $selectedIds = $_POST['selected_tunnels'] ?? [];

            if (empty($selectedIds) || !is_array($selectedIds)) {
                throw new Exception('Selecione ao menos um tunnel.');
            }

            $count = 0;
            foreach ($selectedIds as $tunnelId) {
                $tunnelId = trim((string) $tunnelId);
                if ($bulkAction === 'start') {
                    $pattern = 'cloudflared.*' . preg_quote($tunnelId, '/');
                    $check = shell_exec('pgrep -f ' . escapeshellarg($pattern));
                    if (!$check) {
                        $query = $pdo_saas->prepare("SELECT t.*, e.slug AS empresa_slug FROM empresas_tunnels t LEFT JOIN empresas e ON e.id = t.empresa_id WHERE t.tunnel_id = :id");
                        $query->bindValue(':id', $tunnelId);
                        $query->execute();
                        $t = $query->fetch(PDO::FETCH_ASSOC);
                        if ($t) {
                            $configDir = dirname(__DIR__) . '/tunnels';
                            $slug = !empty($t['empresa_slug']) ? $t['empresa_slug'] : $t['tunnel_nome'];
                            $configPath = $configDir . '/' . $slug . '.yml';
                            if (file_exists($configPath)) {
                                saas_iniciar_tunnel_background($slug, $tunnelId, $configPath);
                            }
                        }
                    }
                    $pdo_saas->prepare("UPDATE empresas_tunnels SET status = 'Ativo' WHERE tunnel_id = ?")->execute([$tunnelId]);
                    $count++;
                } elseif ($bulkAction === 'stop') {
                    $pattern = 'cloudflared.*' . preg_quote($tunnelId, '/');
                    exec('pkill -f ' . escapeshellarg($pattern));
                    $pdo_saas->prepare("UPDATE empresas_tunnels SET status = 'Inativo' WHERE tunnel_id = ?")->execute([$tunnelId]);
                    $count++;
                } elseif ($bulkAction === 'delete') {
                    $pattern = 'cloudflared.*' . preg_quote($tunnelId, '/');
                    exec('pkill -f ' . escapeshellarg($pattern));
                    $pdo_saas->prepare("DELETE FROM empresas_tunnels WHERE tunnel_id = ?")->execute([$tunnelId]);
                    $count++;
                }
            }

            admin_set_flash('success', $count . ' tunnel(s) processados.');
        }
    } catch (Exception $e) {
        admin_set_flash('danger', $e->getMessage());
    }

    admin_redirect('tuneis');
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tuneis_' . date('Y-m-d_His') . '.csv"');
    echo "ID,Empresa,Tunnel Nome,Tunnel ID,Dominio,Service URL,Tipo,Status,Criado em\n";

    $query = $pdo_saas->query("SELECT t.*, e.nome AS empresa_nome FROM empresas_tunnels t LEFT JOIN empresas e ON e.id = t.empresa_id ORDER BY t.id DESC");
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        echo '"' . implode('","', [
            $row['id'],
            str_replace('"', '""', $row['empresa_nome'] ?: '-'),
            str_replace('"', '""', $row['tunnel_nome']),
            $row['tunnel_id'] ?: '-',
            $row['dominio'],
            $row['service_url'],
            $row['tipo'] ?? 'cloudflared',
            $row['status'],
            $row['criado_em']
        ]) . "\"\n";
    }
    exit;
}

$tuneis = [];
$ngrokTunnels = [];
$metricas = ['total' => 0, 'rodando' => 0, 'parados' => 0];

try {
    $query = $pdo_saas->query("SELECT t.*, e.nome AS empresa_nome, e.slug AS empresa_slug FROM empresas_tunnels t LEFT JOIN empresas e ON e.id = t.empresa_id ORDER BY t.id DESC");
    $tuneis = $query->fetchAll(PDO::FETCH_ASSOC);

    $metricas['total'] = count($tuneis);
    foreach ($tuneis as $i => $t) {
        $rodando = saas_tunnel_em_execucao($t['tunnel_id']);
        $tuneis[$i]['rodando'] = $rodando;
        if ($rodando) $metricas['rodando']++; else $metricas['parados']++;
    }

    $ngrokApi = @file_get_contents('http://127.0.0.1:4040/api/tunnels', false, stream_context_create(['http' => ['timeout' => 2]]));
    if ($ngrokApi) {
        $ngrokData = json_decode($ngrokApi, true);
        if (!empty($ngrokData['tunnels'])) {
            $ngrokTunnels = $ngrokData['tunnels'];
        }
    }
} catch (Exception $e) {
    admin_set_flash('danger', 'Erro ao carregar tuneis: ' . $e->getMessage());
}

$empresas = $pdo_saas->query("SELECT id, nome, slug FROM empresas WHERE ativo = 'Sim' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.tunel-metrics { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
.tunel-metric { border: 1px solid #dbe5f1; background: #fff; border-radius: 12px; padding: 10px 12px; }
.tunel-metric span { display: block; color: #64748b; font-size: 0.8rem; }
.tunel-metric strong { font-size: 1.4rem; }
.toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.ngrok-box { background: linear-gradient(135deg, #1a1a2e, #16213e); color: #fff; border-radius: 14px; padding: 16px; margin-bottom: 14px; }
.ngrok-box h4 { margin: 0 0 10px; color: #f59e0b; }
.ngrok-item { background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; margin-bottom: 8px; }
.ngrok-item code { background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 4px; }
@media (max-width: 991px) { .tunel-metrics { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .tunel-metrics { grid-template-columns: 1fr; } }
</style>

<div class="toolbar">
    <h3 class="m-0"><i class="fa fa-network-wired mr-2"></i>Gerenciamento de Tuneis</h3>
    <div>
        <a href="?page=tuneis&export=csv" class="btn btn-soft btn-sm"><i class="fa fa-download mr-1"></i>CSV</a>
        <button type="button" class="btn btn-brand btn-sm" data-toggle="modal" data-target="#modalNovoTunnel"><i class="fa fa-plus mr-1"></i>Novo Tunnel</button>
    </div>
</div>

<div class="tunel-metrics">
    <article class="tunel-metric"><span>Total Cloudflared</span><strong><?= $metricas['total'] ?></strong></article>
    <article class="tunel-metric"><span>Rodando</span><strong><?= $metricas['rodando'] ?></strong></article>
    <article class="tunel-metric"><span>Parados</span><strong><?= $metricas['parados'] ?></strong></article>
    <article class="tunel-metric"><span>Ngrok Ativos</span><strong><?= count($ngrokTunnels) ?></strong></article>
    <article class="tunel-metric"><span>SSH Ngrok</span><strong><?= count($ngrokTunnels) > 0 ? 'ON' : 'OFF' ?></strong></article>
</div>

<?php if (!empty($ngrokTunnels)): ?>
<div class="ngrok-box">
    <h4><i class="fa fa-shield-alt mr-2"></i>Tuneis Ngrok Ativos</h4>
    <?php foreach ($ngrokTunnels as $t): ?>
        <div class="ngrok-item">
            <strong><?= admin_h($t['name']) ?></strong>
            <span class="ml-2 status-pill status-success"><?= admin_h($t['proto']) ?></span>
            <br><code><?= admin_h($t['public_url']) ?></code>
            <small class="ml-2">-> <?= admin_h($t['config']['addr'] ?? '-') ?></small>
        </div>
    <?php endforeach; ?>
    <div class="mt-2">
        <strong>SSH Access:</strong><br>
        <code>ssh iluminatto@0.tcp.sa.ngrok.io -p 14046</code>
    </div>
</div>
<?php endif; ?>

<form method="post" id="formBulk">
    <?= admin_csrf_input() ?>
    <input type="hidden" name="action" value="bulk_action">
    <input type="hidden" name="bulk_action" id="bulkAction" value="">

    <section class="panel-card">
        <div class="p-2 border-bottom d-flex align-items-center gap-2">
            <input type="checkbox" id="selectAll">
            <select class="form-control form-control-sm" style="width:auto" id="bulkSelect">
                <option value="">Acao em massa</option>
                <option value="start">Iniciar selecionados</option>
                <option value="stop">Parar selecionados</option>
                <option value="delete">Excluir selecionados</option>
            </select>
            <button type="submit" class="btn btn-sm btn-soft" id="btnBulk" disabled>Aplicar</button>
        </div>
        <div class="table-responsive">
            <table class="table table-modern table-hover mb-0">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
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
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhum tunnel cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($tuneis as $t): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_tunnels[]" value="<?= admin_h($t['tunnel_id']) ?>" class="tunnel-check"></td>
                            <td>#<?= (int) $t['id'] ?></td>
                            <td><strong><?= admin_h($t['empresa_nome'] ?: '-') ?></strong></td>
                            <td><strong><?= admin_h($t['tunnel_nome']) ?></strong><br><small><code><?= admin_h($t['tunnel_id'] ?: '-') ?></code></small></td>
                            <td><a href="https://<?= admin_h($t['dominio']) ?>" target="_blank"><?= admin_h($t['dominio']) ?></a></td>
                            <td>
                                <span class="status-pill <?= $t['rodando'] ? 'status-success' : 'status-danger' ?>"><?= $t['rodando'] ? 'Rodando' : 'Parado' ?></span>
                            </td>
                            <td class="text-right">
                                <?php if ($t['rodando']): ?>
                                    <form method="post" class="d-inline">
                                        <?= admin_csrf_input() ?>
                                        <input type="hidden" name="action" value="stop_tunnel">
                                        <input type="hidden" name="tunnel_id" value="<?= admin_h($t['tunnel_id']) ?>">
                                        <button class="btn btn-sm btn-danger" title="Parar"><i class="fa fa-stop"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="d-inline">
                                        <?= admin_csrf_input() ?>
                                        <input type="hidden" name="action" value="start_tunnel">
                                        <input type="hidden" name="tunnel_id" value="<?= admin_h($t['tunnel_id']) ?>">
                                        <button class="btn btn-sm btn-success" title="Iniciar"><i class="fa fa-play"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir tunnel?');">
                                    <?= admin_csrf_input() ?>
                                    <input type="hidden" name="action" value="delete_tunnel">
                                    <input type="hidden" name="tunnel_id" value="<?= admin_h($t['tunnel_id']) ?>">
                                    <button class="btn btn-sm btn-soft" title="Excluir"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</form>

<div class="modal fade" id="modalNovoTunnel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= admin_csrf_input() ?>
                <input type="hidden" name="action" value="create_tunnel">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-plus mr-2"></i>Novo Tunnel</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Empresa (opcional)</label>
                        <select name="empresa_id" class="form-control">
                            <option value="">Sem empresa vinculada</option>
                            <?php foreach ($empresas as $e): ?>
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
                        <input type="text" name="service_url" class="form-control" value="http://127.0.0.1:8000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="fa fa-save mr-1"></i>Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.tunnel-check').forEach(c => c.checked = this.checked);
    updateBulkBtn();
});
document.querySelectorAll('.tunnel-check').forEach(c => c.addEventListener('change', updateBulkBtn));
document.getElementById('bulkSelect').addEventListener('change', function() {
    document.getElementById('bulkAction').value = this.value;
    updateBulkBtn();
});
function updateBulkBtn() {
    var checked = document.querySelectorAll('.tunnel-check:checked').length;
    var action = document.getElementById('bulkSelect').value;
    document.getElementById('btnBulk').disabled = !checked || !action;
}
</script>
