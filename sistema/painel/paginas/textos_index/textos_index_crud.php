<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class TextosIndexCrud extends CRUD_Template {
    protected $tableName = 'textos_index';
    protected $primaryKey = 'id';
    protected $title = 'Textos da Página Inicial';
    protected $singular = 'Texto';
    protected $plural = 'Textos';
    protected $modulePath = 'textos_index';
    
    protected $fields = [
        'titulo' => ['type' => 'text', 'label' => 'Título'],
        'descricao' => ['type' => 'textarea', 'label' => 'Descrição'],
        'link' => ['type' => 'text', 'label' => 'Link'],
    ];
    
    protected $listColumns = ['id', 'titulo', 'link'];
    protected $listLabels = ['ID', 'Título', 'Link'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
        $items = $this->getAll($this->tableName, 'id ASC');
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
                <td><?= htmlspecialchars($item['titulo']) ?></td>
                <td><?= htmlspecialchars($item['link']) ?></td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['titulo']) ?>', '<?= htmlspecialchars($item['descricao']) ?>', '<?= htmlspecialchars($item['link']) ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['titulo']) ?>', '<?= htmlspecialchars($item['descricao']) ?>', '<?= htmlspecialchars($item['link']) ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
        
        $titulo = $_POST['titulo'];
        $descricao = $_POST['descricao'];
        $link = $_POST['link'];
        
        if ($id == "") {
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET titulo = :titulo, descricao = :descricao, link = :link");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET titulo = :titulo, descricao = :descricao, link = :link WHERE id = '$id'");
        }
        
        $query->bindValue(":titulo", $titulo);
        $query->bindValue(":descricao", $descricao);
        $query->bindValue(":link", $link);
        $query->execute();
        
        echo 'Salvo com Sucesso';
    }
}

$crud = new TextosIndexCrud();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $crud->renderList();
        break;
    case 'save':
        $id = $_POST['id'] ?? null;
        $crud->save($id);
        break;
    default:
        $crud->renderList();
}