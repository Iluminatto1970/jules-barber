<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

require_once __DIR__ . '/../lib/crud_template.php';

class DashboardCrud extends CrudTemplate {
    protected $tableName = 'dashboard';
    protected $primaryKey = 'id';
    protected $title = 'Dashboard';
    protected $singular = 'Dashboard';
    protected $plural = 'Dashboards';
    
    public function getDashboardData() {
        $metrics = [
            'empresas_total' => 0,
            'empresas_ativas' => 0,
            'empresas_trial' => 0,
            'assinaturas_suspensas' => 0,
            'planos_ativos' => 0,
            'tuneis_total' => 0,
        ];

        $recentEmpresas = [];
        $planosResumo = [];

        try {
            $metrics['empresas_total'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
            $metrics['empresas_ativas'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas WHERE ativo = 'Sim'")->fetchColumn();
            $metrics['empresas_trial'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas_assinaturas WHERE status = 'Trial'")->fetchColumn();
            $metrics['assinaturas_suspensas'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas_assinaturas WHERE status = 'Suspensa'")->fetchColumn();
            $metrics['planos_ativos'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM planos WHERE ativo = 'Sim'")->fetchColumn();
            $metrics['tuneis_total'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas_tunnels")->fetchColumn();

            $query = $pdo_saas->query("SELECT
                    e.id,
                    e.nome,
                    e.slug,
                    e.banco,
                    e.ativo,
                    d.dominio AS dominio_principal,
                    a.status AS assinatura_status,
                    a.trial_ate,
                    a.ciclo_ate,
                    p.nome AS plano_nome
                FROM empresas e
                LEFT JOIN empresas_dominios d ON d.empresa_id = e.id AND d.principal = 1
                LEFT JOIN empresas_assinaturas a ON a.empresa_id = e.id
                LEFT JOIN planos p ON p.id = a.plano_id
                ORDER BY e.id DESC
                LIMIT 8");
            $recentEmpresas = $query->fetchAll(PDO::FETCH_ASSOC);

            $query = $pdo_saas->query("SELECT
                    p.nome,
                    p.slug,
                    COUNT(a.id) AS total_empresas,
                    SUM(CASE WHEN a.status = 'Trial' THEN 1 ELSE 0 END) AS em_trial,
                    SUM(CASE WHEN a.status = 'Ativa' THEN 1 ELSE 0 END) AS ativas
                FROM planos p
                LEFT JOIN empresas_assinaturas a ON a.plano_id = p.id
                GROUP BY p.id, p.nome, p.slug
                ORDER BY total_empresas DESC, p.nome ASC");
            $planosResumo = $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            admin_set_flash('danger', 'Nao foi possivel carregar o dashboard completo: ' . $e->getMessage());
        }

        return [
            'metrics' => $metrics,
            'recentEmpresas' => $recentEmpresas,
            'planosResumo' => $planosResumo,
        ];
    }
    
    public function render() {
        $data = $this->getDashboardData();
        
        $metricCards = [
            [
                'label' => 'Empresas cadastradas',
                'value' => $data['metrics']['empresas_total'],
                'icon' => 'fa-building',
                'color' => '#0ea5e9',
                'hint' => 'Total de tenants na plataforma',
            ],
            [
                'label' => 'Empresas ativas',
                'value' => $data['metrics']['empresas_ativas'],
                'icon' => 'fa-circle-check',
                'color' => '#10b981',
                'hint' => 'Empresas com status ativo',
            ],
            [
                'label' => 'Assinaturas em trial',
                'value' => $data['metrics']['empresas_trial'],
                'icon' => 'fa-hourglass-half',
                'color' => '#f59e0b',
                'hint' => 'Empresas em periodo de avaliacao',
            ],
            [
                'label' => 'Assinaturas suspensas',
                'value' => $data['metrics']['assinaturas_suspensas'],
                'icon' => 'fa-ban',
                'color' => '#ef4444',
                'hint' => 'Requer acao de suporte/comercial',
            ],
        ];
        
        function dashboard_badge($ativo, $assinatura) {
            if ($ativo !== 'Sim') {
                return ['status-danger', 'Inativa'];
            }

            if ($assinatura === 'Ativa') {
                return ['status-success', 'Ativa'];
            }

            if ($assinatura === 'Trial') {
                return ['status-warning', 'Trial'];
            }

            if ($assinatura === 'Suspensa') {
                return ['status-danger', 'Suspensa'];
            }

            if ($assinatura === 'Cancelada') {
                return ['status-muted', 'Cancelada'];
            }

            return ['status-muted', 'Sem assinatura'];
        }
        ?>

        <style>
            .metric-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
                margin-bottom: 16px;
            }

            .metric-card {
                border: 1px solid var(--border);
                border-radius: 16px;
                background: #fff;
                padding: 14px;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .metric-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }

            .metric-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
            }

            .metric-label {
                color: #64748b;
                font-size: 0.82rem;
                margin-bottom: 3px;
            }

            .metric-value {
                font-size: 1.65rem;
                line-height: 1.1;
                margin: 0;
            }

            .metric-hint {
                margin: 7px 0 0;
                color: #94a3b8;
                font-size: 0.78rem;
            }

            .actions-shortcuts {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
                margin: 14px 0 16px;
            }

            .shortcut-item {
                border: 1px solid #d9e2ef;
                border-radius: 12px;
                padding: 10px 12px;
                background: #fff;
                text-decoration: none;
                color: #0f172a;
                font-size: 0.9rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .shortcut-item:hover {
                text-decoration: none;
                border-color: #bcd0e5;
                background: #f8fbff;
                color: #0f172a;
            }

            .dashboard-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
                gap: 14px;
            }

            .box-head {
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .box-head h3 {
                margin: 0;
                font-size: 1.01rem;
            }

            .box-body {
                padding: 0;
            }

            .plan-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 15px;
                border-bottom: 1px solid #edf2f7;
            }

            .plan-row:last-child {
                border-bottom: 0;
            }

            .plan-row small {
                color: #64748b;
            }

            @media (max-width: 1200px) {
                .metric-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .dashboard-grid {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            @media (max-width: 767px) {
                .metric-grid {
                    grid-template-columns: 1fr;
                }

                .actions-shortcuts {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="metric-grid">
            <?php foreach ($metricCards as $card): ?>
                <article class="metric-card">
                    <div class="metric-head">
                        <span class="metric-label"><?= admin_h($card['label']) ?></span>
                        <span class="metric-icon" style="background: <?= admin_h($card['color']) ?>;"><i class="fa <?= admin_h($card['icon']) ?>"></i></span>
                    </div>
                    <h2 class="metric-value"><?= (int) $card['value'] ?></h2>
                    <p class="metric-hint"><?= admin_h($card['hint']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="actions-shortcuts">
            <a class="shortcut-item" href="?page=empresas"><i class="fa fa-plus-circle text-success"></i> Cadastrar empresa</a>
            <a class="shortcut-item" href="?page=planos"><i class="fa fa-layer-group text-info"></i> Ajustar planos e limites</a>
            <a class="shortcut-item" href="?page=tuneis"><i class="fa fa-network-wired text-warning"></i> Monitorar tuneis</a>
        </div>

        <div class="dashboard-grid">
            <section class="panel-card">
                <header class="box-head">
                    <h3><i class="fa fa-building mr-2"></i>Empresas recentes</h3>
                    <a href="?page=empresas" class="btn btn-sm btn-soft">Ver todas</a>
                </header>
                <div class="box-body table-responsive">
                    <table class="table table-modern table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>Dominio principal</th>
                                <th>Plano</th>
                                <th>Status</th>
                                <th>Expira em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['recentEmpresas'])): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhuma empresa cadastrada no momento.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['recentEmpresas'] as $empresa):
                                    $badge = dashboard_badge($empresa['ativo'], $empresa['assinatura_status']);
                                ?>
                                    <tr>
                                        <td>#<?= (int) $empresa['id'] ?></td>
                                        <td>
                                            <strong><?= admin_h($empresa['nome']) ?></strong><br>
                                            <small class="text-muted"><?= admin_h($empresa['slug']) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($empresa['dominio_principal'])): ?>
                                                <small><?= admin_h($empresa['dominio_principal']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Nao definido</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= admin_h($empresa['plano_nome'] ?: 'Sem plano') ?></td>
                                        <td><span class="status-pill <?= admin_h($badge[0]) ?>"><?= admin_h($badge[1]) ?></span></td>
                                        <td>
                                            <?php
                                            $dataExpiracao = $empresa['assinatura_status'] === 'Ativa'
                                                ? $empresa['ciclo_ate']
                                                : $empresa['trial_ate'];
                                            $vencida = ($empresa['assinatura_status'] === 'Ativa' && !empty($empresa['ciclo_ate']) && date('Y-m-d') > $empresa['ciclo_ate']);
                                            ?>
                                            <?= admin_h(admin_format_date($dataExpiracao)) ?>
                                            <?php if ($vencida): ?>
                                                <span class="status-pill status-danger mt-1">Vencida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <a href="?page=empresas&id=<?= (int) $empresa['id'] ?>" class="btn btn-sm btn-soft"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel-card">
                <header class="box-head">
                    <h3><i class="fa fa-chart-pie mr-2"></i>Distribuicao de planos</h3>
                </header>
                <div class="box-body">
                    <?php if (empty($data['planosResumo'])): ?>
                        <div class="p-3 text-muted">Nenhum plano encontrado.</div>
                    <?php else: ?>
                        <?php foreach ($data['planosResumo'] as $plano): ?>
                            <div class="plan-row">
                                <div>
                                    <strong><?= admin_h($plano['nome']) ?></strong><br>
                                    <small><?= admin_h($plano['slug']) ?></small>
                                </div>
                                <div class="text-right">
                                    <span class="status-pill status-info"><?= (int) $plano['total_empresas'] ?> empresas</span><br>
                                    <small>Ativas: <?= (int) $plano['ativas'] ?> | Trial: <?= (int) $plano['em_trial'] ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="p-3 border-top">
                        <a class="btn btn-soft btn-sm btn-block" href="?page=assinaturas"><i class="fa fa-credit-card mr-1"></i>Gerenciar assinaturas</a>
                    </div>
                </div>
            </section>
        </div>
        <?php
    }
    
    public function get($id = null) {
        if ($id === null) {
            $this->render();
        } else {
            $this->render();
        }
    }
    
    public function create() {
        admin_redirect('?page=dashboard');
    }
    
    public function update($id) {
        admin_redirect('?page=dashboard');
    }
    
    public function delete($id) {
        admin_set_flash('danger', 'Dashboard nao pode ser excluida');
        admin_redirect('?page=dashboard');
    }
    
    public function checkNgrok() {
        $tunnelFiles = [
            '/home/iluminatto/Documents/Dev/Barbearia/BARBEARIA PRO/app/app/sistema/saas/tunnels/superadm.yml',
            '/home/iluminatto/.cloudflared/config.yml'
        ];
        
        foreach ($tunnelFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                preg_match('/hostname:\s*(.+)/', $content, $match);
                if (isset($match[1])) {
                    $hostname = trim($match[1]);
                    echo 'https://' . $hostname;
                    return;
                }
            }
        }
        
        echo '';
    }
}

$crud = new DashboardCrud();
$action = $_GET['action'] ?? 'list';

if ($action === 'check_ngrok') {
    $crud->checkNgrok();
} else {
    echo $crud->get();
}