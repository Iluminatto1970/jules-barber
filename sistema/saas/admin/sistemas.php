<?php
if (!defined('SAAS_ADMIN_APP')) {
    exit;
}

$action = $_GET['action'] ?? '';
$sistema_id = $_GET['sistema'] ?? 0;

if ($action === 'get' && isset($_POST['id'])) {
    $query = $pdo_saas->prepare("SELECT * FROM sistemas WHERE id = ?");
    $query->execute([$_POST['id']]);
    $data = $query->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit;
}

if ($action === 'save' && $_POST) {
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'];
    $slug = $_POST['slug'];
    $descricao = $_POST['descricao'] ?? '';
    $pasta = $_POST['pasta'];
    $banco = $_POST['banco'];
    $db_host = $_POST['db_host'] ?? '127.0.0.1';
    $db_usuario = $_POST['db_usuario'];
    $db_senha = $_POST['db_senha'] ?? '';
    
    if ($id) {
        $query = $pdo_saas->prepare("UPDATE sistemas SET nome=?, slug=?, descricao=?, pasta=?, banco_dados=?, db_host=?, db_usuario=?, db_senha=? WHERE id=?");
        $query->execute([$nome, $slug, $descricao, $pasta, $banco, $db_host, $db_usuario, $db_senha, $id]);
    } else {
        $query = $pdo_saas->prepare("INSERT INTO sistemas (nome, slug, descricao, pasta, banco_dados, db_host, db_usuario, db_senha) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $query->execute([$nome, $slug, $descricao, $pasta, $banco, $db_host, $db_usuario, $db_senha]);
    }
    echo 'Salvo com Sucesso';
    exit;
}

if ($action === 'delete' && isset($_POST['id'])) {
    $query = $pdo_saas->prepare("DELETE FROM sistemas WHERE id = ?");
    $query->execute([$_POST['id']]);
    echo 'Excluido com Sucesso';
    exit;
}

$query = $pdo_saas->query("SELECT * FROM sistemas ORDER BY nome ASC");
$sistemas = $query->fetchAll(PDO::FETCH_ASSOC);

$sistema_atual = null;
$db_connection = null;
$is_sqlite = false;

if ($sistema_id > 0) {
    foreach ($sistemas as $sis) {
        if ($sis['id'] == $sistema_id) {
            $sistema_atual = $sis;
            break;
        }
    }
    
    if ($sistema_atual && $sistema_atual['banco_dados']) {
        $db_path = '/home/iluminatto/' . $sistema_atual['pasta'] . '/';
        
        if (strpos($sistema_atual['pasta'], 'bio_link') !== false) {
            $db_connection = new PDO('sqlite:' . $db_path . 'bio.db');
            $is_sqlite = true;
        } else {
            try {
                $db_connection = new PDO(
                    'mysql:host=' . $sistema_atual['db_host'] . ';dbname=' . $sistema_atual['banco_dados'],
                    $sistema_atual['db_usuario'],
                    $sistema_atual['db_senha']
                );
                $db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                $db_connection = null;
            }
        }
    }
}

$tabelas_sistema = [];
if ($db_connection) {
    if ($is_sqlite) {
        $tables = $db_connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $count = $db_connection->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $tabelas_sistema[] = ['nome' => $table, 'registros' => $count];
        }
    } else {
        $tables = $db_connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $count = $db_connection->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $tabelas_sistema[] = ['nome' => $table, 'registros' => $count];
        }
    }
}
?>
<style>
    .system-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
    .system-card { background: #fff; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; transition: transform 0.2s; }
    .system-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .system-header { padding: 16px 20px; background: linear-gradient(135deg, #0f766e, #14b8a6); color: white; }
    .system-header h4 { margin: 0; font-size: 1.1rem; }
    .system-body { padding: 16px 20px; }
    .system-stat { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .system-actions { padding: 12px 20px; background: #f8fafc; display: flex; gap: 8px; }
    
    .detail-header { background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 20px; border-radius: 16px; margin-bottom: 20px; }
    .detail-header h3 { margin: 0 0 10px 0; }
    .detail-header a { color: #67e8f9; }
    
    .crud-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
    .crud-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; transition: all 0.2s; }
    .crud-card:hover { border-color: #0f766e; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.1); }
    .crud-card h5 { margin: 0 0 10px 0; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    .crud-card .count { background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; color: #64748b; }
    .crud-card .count span { font-weight: 700; color: #0f766e; }
    
    .table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-top: 20px; }
    .table-card-header { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .table-card-body { max-height: 400px; overflow: auto; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; background: #f1f5f9; font-size: 0.85rem; color: #64748b; position: sticky; top: 0; }
    .data-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
    .data-table tr:hover { background: #f8fafc; }
    .data-table .actions { display: flex; gap: 8px; }
    .data-table .actions button { padding: 4px 8px; font-size: 0.8rem; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #374151; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
    .form-group input:focus { outline: none; border-color: #0f766e; }
</style>

<?php if ($sistema_id > 0 && $sistema_atual): ?>
    <div class="detail-header">
        <h3><i class="fa fa-<?= strpos($sistema_atual['pasta'], 'bio') !== false ? 'link' : (strpos($sistema_atual['pasta'], 'chat') !== false ? 'comments' : 'cut') ?> mr-2"></i><?= htmlspecialchars($sistema_atual['nome']) ?></h3>
        <p class="mb-2"><?= htmlspecialchars($sistema_atual['descricao']) ?></p>
        <p class="mb-0"><small>Pasta: <code><?= htmlspecialchars($sistema_atual['pasta']) ?></code> | Banco: <code><?= htmlspecialchars($sistema_atual['banco_dados']) ?></code></small></p>
        <a href="?page=sistemas"><i class="fa fa-arrow-left"></i> Voltar para Sistemas</a>
    </div>
    
    <?php if ($db_connection): ?>
        <div class="crud-grid">
            <?php foreach ($tabelas_sistema as $tbl): ?>
                <div class="crud-card">
                    <h5><i class="fa fa-table text-teal"></i> <?= htmlspecialchars($tbl['nome']) ?>
                        <span class="count"><span><?= $tbl['registros'] ?></span> registros</span>
                    </h5>
                    <button class="btn btn-sm btn-soft" onclick="verTabela('<?= htmlspecialchars($tbl['nome']) ?>')">
                        <i class="fa fa-eye"></i> Ver Dados
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="tabelaView" style="display:none;">
            <div class="table-card">
                <div class="table-card-header">
                    <h5 class="mb-0" id="tableTitle">Tabela</h5>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="novaLinha()"><i class="fa fa-plus"></i> Novo</button>
                        <button class="btn btn-sm btn-soft" onclick="$('#tabelaView').hide()"><i class="fa fa-times"></i> Fechar</button>
                    </div>
                </div>
                <div class="table-card-body">
                    <table class="data-table" id="dataTable">
                        <thead><tr id="tableHead"></tr></thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        let currentTable = '';
        let currentData = [];
        
        function verTabela(tabela) {
            currentTable = tabela;
            $.post('?page=sistemas&action=get_tabela', {
                sistema: <?= $sistema_id ?>,
                tabela: tabela
            }, function(ret) {
                ret = JSON.parse(ret);
                if (ret.success) {
                    currentData = ret.dados;
                    let html = '';
                    ret.colunas.forEach(function(col) {
                        html += '<th>' + col + '</th>';
                    });
                    html += '<th>Ações</th>';
                    $('#tableHead').html(html);
                    
                    html = '';
                    ret.dados.forEach(function(linha, idx) {
                        html += '<tr>';
                        ret.colunas.forEach(function(col) {
                            html += '<td>' + (linha[col] !== null ? linha[col] : '<em class="text-muted">NULL</em>') + '</td>';
                        });
                        html += '<td class="actions">';
                        html += '<button class="btn btn-sm btn-soft" onclick="editarLinha(' + idx + ')"><i class="fa fa-edit"></i></button>';
                        html += '<button class="btn btn-sm btn-soft text-danger" onclick="excluirLinha(' + idx + ')"><i class="fa fa-trash"></i></button>';
                        html += '</td></tr>';
                    });
                    $('#tableBody').html(html);
                    $('#tableTitle').text(tabela);
                    $('#tabelaView').show();
                } else {
                    alert(ret.msg);
                }
            });
        }
        
        function novaLinha() {
            alert('Em breve: Formulário para nova linha em ' + currentTable);
        }
        
        function editarLinha(idx) {
            alert('Em breve: Editar linha ' + idx + ' de ' + currentTable);
        }
        
        function excluirLinha(idx) {
            if (confirm('Confirmar exclusão?')) {
                $.post('?page=sistemas&action=delete_row', {
                    sistema: <?= $sistema_id ?>,
                    tabela: currentTable,
                    id: currentData[idx].id
                }, function(ret) {
                    ret = JSON.parse(ret);
                    if (ret.success) {
                        verTabela(currentTable);
                    } else {
                        alert(ret.msg);
                    }
                });
            }
        }
        </script>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> Não foi possível conectar ao banco de dados deste sistema.
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fa fa-layer-group text-teal mr-2"></i>Sistemas Cadastrados</h4>
        <button class="btn btn-primary" onclick="novoSistema()">
            <i class="fa fa-plus"></i> Novo Sistema
        </button>
    </div>

    <div class="system-grid">
        <?php foreach ($sistemas as $sis): ?>
            <?php
            $tunnel_file = '/home/iluminatto/' . $sis['pasta'] . '/';
            $tunnels = [];
            if (is_dir($tunnel_file)) {
                $yml_files = glob($tunnel_file . '*.yml');
                foreach ($yml_files as $file) {
                    if (strpos($file, 'tunnel') !== false || strpos($file, 'cloudflared') !== false) {
                        $content = file_get_contents($file);
                        preg_match('/hostname:\s*(.+)/', $content, $match);
                        $tunnels[] = ['nome' => basename($file), 'hostname' => isset($match[1]) ? trim($match[1]) : 'N/A'];
                    }
                }
            }
            ?>
            <div class="system-card">
                <div class="system-header">
                    <h4><i class="fa fa-<?= strpos($sis['pasta'], 'bio') !== false ? 'link' : (strpos($sis['pasta'], 'chat') !== false ? 'comments' : 'cut') ?> mr-2"></i><?= htmlspecialchars($sis['nome']) ?></h4>
                    <small><?= htmlspecialchars($sis['slug']) ?></small>
                </div>
                <div class="system-body">
                    <div class="system-stat">
                        <label>Pasta</label>
                        <value><small><?= htmlspecialchars($sis['pasta']) ?></small></value>
                    </div>
                    <div class="system-stat">
                        <label>Banco</label>
                        <value><small><?= htmlspecialchars($sis['banco_dados']) ?></small></value>
                    </div>
                    <?php if (!empty($tunnels)): ?>
                    <div class="system-stat">
                        <label>Tuneis</label>
                        <value><?php foreach($tunnels as $t): ?><span class="badge badge-info mr-1"><?= htmlspecialchars($t['hostname']) ?></span><?php endforeach; ?></value>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="system-actions">
                    <a href="?page=sistemas&sistema=<?= $sis['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-cog"></i> Gerenciar
                    </a>
                    <button class="btn btn-sm btn-soft" onclick="editarSistema(<?= $sis['id'] ?>)">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-soft text-danger" onclick="excluirSistema(<?= $sis['id'] ?>)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal Sistema -->
<div class="modal" id="modalSistema" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sistema</h5>
                <button type="button" class="close" onclick="$('#modalSistema').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="formSistema">
                    <input type="hidden" name="id" id="sis_id">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" id="sis_nome" required>
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" id="sis_slug" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" id="sis_descricao" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Pasta</label>
                        <input type="text" name="pasta" id="sis_pasta" required>
                    </div>
                    <div class="form-group">
                        <label>Banco de Dados</label>
                        <input type="text" name="banco" id="sis_banco">
                    </div>
                    <div class="form-group">
                        <label>DB Host</label>
                        <input type="text" name="db_host" id="sis_db_host" value="127.0.0.1">
                    </div>
                    <div class="form-group">
                        <label>DB Usuário</label>
                        <input type="text" name="db_usuario" id="sis_db_usuario">
                    </div>
                    <div class="form-group">
                        <label>DB Senha</label>
                        <input type="text" name="db_senha" id="sis_db_senha">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#modalSistema').modal('hide')">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarSistema()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
function novoSistema() {
    $('#sis_id').val('');
    $('#sis_nome, #sis_slug, #sis_descricao, #sis_pasta, #sis_banco, #sis_db_host, #sis_db_usuario, #sis_db_senha').val('');
    $('#modalSistema').modal('show');
}

function editarSistema(id) {
    $.post('?page=sistemas&action=get', {id: id}, function(data) {
        data = JSON.parse(data);
        $('#sis_id').val(data.id);
        $('#sis_nome').val(data.nome);
        $('#sis_slug').val(data.slug);
        $('#sis_descricao').val(data.descricao);
        $('#sis_pasta').val(data.pasta);
        $('#sis_banco').val(data.banco_dados);
        $('#sis_db_host').val(data.db_host);
        $('#sis_db_usuario').val(data.db_usuario);
        $('#sis_db_senha').val(data.db_senha);
        $('#modalSistema').modal('show');
    });
}

function salvarSistema() {
    var dados = $('#formSistema').serialize();
    $.post('?page=sistemas&action=save', dados, function(ret) {
        if (ret.trim() === 'Salvo com Sucesso') {
            $('#modalSistema').modal('hide');
            location.reload();
        } else {
            alert(ret);
        }
    });
}

function excluirSistema(id) {
    if (confirm('Excluir sistema?')) {
        $.post('?page=sistemas&action=delete', {id: id}, function(ret) {
            if (ret.trim() === 'Excluido com Sucesso') location.reload();
            else alert(ret);
        });
    }
}
</script>

<?php
if (isset($_GET['action']) && $_GET['action'] === 'get_tabela' && $sistema_id > 0) {
    header('Content-Type: application/json');
    
    $tabela = $_POST['tabela'] ?? '';
    if (!$tabela || !$db_connection) {
        echo json_encode(['success' => false, 'msg' => 'Erro']);
        exit;
    }
    
    try {
        if ($is_sqlite) {
            $stmt = $db_connection->query("SELECT * FROM $tabela LIMIT 100");
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $colunas = !empty($dados) ? array_keys($dados[0]) : [];
        } else {
            $stmt = $db_connection->query("SELECT * FROM $tabela LIMIT 100");
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $colunas = !empty($dados) ? array_keys($dados[0]) : [];
        }
        echo json_encode(['success' => true, 'dados' => $dados, 'colunas' => $colunas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_row' && $sistema_id > 0) {
    header('Content-Type: application/json');
    
    $tabela = $_POST['tabela'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if (!$tabela || !$id || !$db_connection) {
        echo json_encode(['success' => false, 'msg' => 'Erro']);
        exit;
    }
    
    try {
        if ($is_sqlite) {
            $db_connection->exec("DELETE FROM $tabela WHERE id = $id");
        } else {
            $stmt = $db_connection->prepare("DELETE FROM $tabela WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}
?>