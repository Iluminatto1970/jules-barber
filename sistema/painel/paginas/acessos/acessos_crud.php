<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class AcessosCrud extends CRUD_Template {
    protected $tableName = 'acessos';
    protected $primaryKey = 'id';
    protected $title = 'Permissões de Acesso';
    protected $singular = 'Permissão';
    protected $plural = 'Permissões';
    protected $modulePath = 'acessos';
    
    protected $fields = [
        'nome' => ['type' => 'text', 'label' => 'Nome', 'required' => true],
        'chave' => ['type' => 'text', 'label' => 'Chave', 'required' => true],
        'icon' => ['type' => 'text', 'label' => 'Ícone'],
    ];
    
    protected $listColumns = ['id', 'nome', 'chave', 'icon'];
    protected $listLabels = ['ID', 'Nome', 'Chave', 'Ícone'];
    
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
                <td><?= htmlspecialchars($item['chave']) ?></td>
                <td><i class="fa <?= htmlspecialchars($item['icon']) ?>"></i></td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['chave']) ?>', '<?= htmlspecialchars($item['icon']) ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['chave']) ?>', '<?= htmlspecialchars($item['icon']) ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
        $chave = $_POST['chave'];
        $icon = $_POST['icon'];
        
        if ($id == "") {
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET nome = :nome, chave = :chave, icon = :icon");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET nome = :nome, chave = :chave, icon = :icon WHERE id = '$id'");
        }
        
        $query->bindValue(":nome", $nome);
        $query->bindValue(":chave", $chave);
        $query->bindValue(":icon", $icon);
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

$crud = new AcessosCrud();

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