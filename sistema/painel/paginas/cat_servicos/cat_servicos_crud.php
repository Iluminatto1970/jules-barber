<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class CatServicosCrud extends CRUD_Template {
    protected $tableName = 'cat_servicos';
    protected $primaryKey = 'id';
    protected $title = 'Categorias de Serviços';
    protected $singular = 'Categoria de Serviço';
    protected $plural = 'Categorias de Serviços';
    protected $modulePath = 'cat_servicos';
    
    protected $fields = [
        'nome' => ['type' => 'text', 'label' => 'Nome', 'required' => true],
    ];
    
    protected $listColumns = ['id', 'nome', 'data_cad'];
    protected $listLabels = ['ID', 'Nome', 'Cadastro'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
        $items = $this->getAll($this->tableName, 'nome ASC');
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
            <tr>
                <td>#<?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($item['nome']) ?></td>
                <td><?= date('d/m/Y', strtotime($item['data_cad'])) ?></td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= date('d/m/Y', strtotime($item['data_cad'])) ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
    
    public function save($id = null) {
        global $pdo;
        
        $nome = $_POST['nome'];
        
        if ($id == "") {
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET nome = :nome, data_cad = curDate()");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET nome = :nome WHERE id = '$id'");
        }
        
        $query->bindValue(":nome", $nome);
        $query->execute();
        
        echo 'Salvo com Sucesso';
    }
    
    public function delete($id) {
        global $pdo;
        
        $query = $pdo->prepare("DELETE FROM {$this->tableName} WHERE id = ?");
        $query->execute([$id]);
        echo 'Excluído com Sucesso';
    }
}

$crud = new CatServicosCrud();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $crud->renderList();
        break;
    case 'save':
        $id = $_POST['id'] ?? null;
        $crud->save($id);
        break;
    case 'delete':
        $id = $_POST['id'] ?? null;
        $crud->delete($id);
        break;
    default:
        $crud->renderList();
}