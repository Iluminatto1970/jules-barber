<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class ComissoesCrud extends CRUD_Template {
    protected $tableName = 'comissoes';
    protected $primaryKey = 'id';
    protected $title = 'Comissões';
    protected $singular = 'Comissão';
    protected $plural = 'Comissões';
    protected $modulePath = 'comissoes';
    
    protected $listColumns = ['id', 'funcionario', 'servico', 'valor', 'data', 'status'];
    protected $listLabels = ['ID', 'Funcionário', 'Serviço', 'Valor', 'Data', 'Status'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
        global $pdo;
        
        $items = $this->getAll($this->tableName, 'id DESC');
        ?>
        <small>
        <table class="table table-hover" id="tabela">
        <thead> 
        <tr> 
        <?php foreach ($this->listLabels as $label): ?>
        <th><?= $label ?></th>
        <?php endforeach; ?>
        <th>Ações</th>
        </tr> 
        </thead> 
        <tbody>	
        <?php if (empty($items)): ?>
            <tr><td colspan="<?= count($this->listLabels) + 1 ?>" class="text-center text-muted py-4">Nenhum registro encontrado!</td></tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
            <?php
            $query2 = $pdo->query("SELECT nome FROM usuarios WHERE id = '{$item['funcionario']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_func = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Referência';
            
            $query2 = $pdo->query("SELECT nome FROM servicos WHERE id = '{$item['servico']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_serv = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Referência';
            
            $status_class = $item['status'] == 'Pago' ? 'status-success' : 'status-warning';
            ?>
            <tr>
                <td>#<?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($nome_func) ?></td>
                <td><?= htmlspecialchars($nome_serv) ?></td>
                <td>R$ <?= number_format($item['valor'], 2, ',', '.') ?></td>
                <td><?= date('d/m/Y', strtotime($item['data'])) ?></td>
                <td><span class="status-pill <?= $status_class ?>"><?= $item['status'] ?></span></td>
                <td>
                    <?php if ($item['status'] != 'Pago'): ?>
                    <big><a href="#" onclick="baixar('<?= $item['id'] ?>')" title="Baixar"><i class="fa fa-check text-success"></i></a></big>
                    <?php endif; ?>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($nome_func) ?>', '<?= htmlspecialchars($nome_serv) ?>', 'R$ <?= number_format($item['valor'], 2, ',', '.') ?>', '<?= date('d/m/Y', strtotime($item['data'])) ?>', '<?= $item['status'] ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
                    <big><a href="#" onclick="excluir('<?= $item['id'] ?>')" title="Excluir"><i class="fa fa-trash-o text-danger"></i></a></big>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        </table>
        </small>
        <script>
            $(document).ready(function() {
                $('#tabela').DataTable({ "ordering": false, "stateSave": true });
            });
        </script>
        <?php
    }
    
    public function delete($id) {
        global $pdo;
        $query = $pdo->prepare("DELETE FROM {$this->tableName} WHERE id = ?");
        $query->execute([$id]);
        echo 'Excluído com Sucesso';
    }
    
    public function toggleStatus($id, $status) {
        global $pdo;
        $query = $pdo->prepare("UPDATE {$this->tableName} SET status = ? WHERE id = ?");
        $query->execute([$status, $id]);
        echo 'Alterado com Sucesso';
    }
}

$crud = new ComissoesCrud();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $crud->renderList();
        break;
    case 'delete':
        $id = $_POST['id'] ?? null;
        $crud->delete($id);
        break;
    case 'toggle':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? 'Pago';
        $crud->toggleStatus($id, $status);
        break;
    default:
        $crud->renderList();
}