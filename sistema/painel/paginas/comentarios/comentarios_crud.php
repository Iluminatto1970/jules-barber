<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class ComentariosCrud extends CRUD_Template {
    protected $tableName = 'comentarios';
    protected $primaryKey = 'id';
    protected $title = 'Comentários';
    protected $singular = 'Comentário';
    protected $plural = 'Comentários';
    protected $modulePath = 'comentarios';
    
    protected $listColumns = ['id', 'nome', 'qualidade', 'servico', 'nota', 'data'];
    protected $listLabels = ['ID', 'Cliente', 'Qualidade', 'Serviço', 'Nota', 'Data'];
    
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
        <th>Status</th>
        <th>Ações</th>
        </tr> 
        </thead> 
        <tbody>	
        <?php if (empty($items)): ?>
            <tr><td colspan="<?= count($this->listLabels) + 2 ?>" class="text-center text-muted py-4">Nenhum registro encontrado!</td></tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
            <?php
            $query2 = $pdo->query("SELECT nome FROM servicos WHERE id = '{$item['servico']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_servico = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Referência';
            
            $nota_stars = str_repeat('★', $item['nota']) . str_repeat('☆', 5 - $item['nota']);
            ?>
            <tr class="<?= $item['ativo'] == 'Não' ? 'text-muted' : '' ?>">
                <td>#<?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($item['nome']) ?></td>
                <td><?= htmlspecialchars($item['qualidade']) ?></td>
                <td><?= htmlspecialchars($nome_servico) ?></td>
                <td><?= $nota_stars ?></td>
                <td><?= date('d/m/Y', strtotime($item['data'])) ?></td>
                <td>
                    <span class="status-pill <?= $item['ativo'] == 'Sim' ? 'status-success' : 'status-danger' ?>">
                        <?= $item['ativo'] ?>
                    </span>
                </td>
                <td>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['qualidade']) ?>', '<?= htmlspecialchars($nome_servico) ?>', '<?= $nota_stars ?>', '<?= date('d/m/Y', strtotime($item['data'])) ?>', '<?= $item['ativo'] ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
                    <big><a href="#" onclick="excluir('<?= $item['id'] ?>')" title="Excluir"><i class="fa fa-trash-o text-danger"></i></a></big>
                    <big><a href="#" onclick="ativar('<?= $item['id'] ?>', '<?= $item['ativo'] == 'Sim' ? 'Não' : 'Sim' ?>')" title="<?= $item['ativo'] == 'Sim' ? 'Desativar' : 'Ativar' ?>"><i class="fa <?= $item['ativo'] == 'Sim' ? 'fa-check-square' : 'fa-square-o' ?> text-success"></i></a></big>
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
        $query = $pdo->prepare("UPDATE {$this->tableName} SET ativo = ? WHERE id = ?");
        $query->execute([$status, $id]);
        echo 'Alterado com Sucesso';
    }
}

$crud = new ComentariosCrud();

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
        $status = $_POST['status'] ?? 'Sim';
        $crud->toggleStatus($id, $status);
        break;
    default:
        $crud->renderList();
}