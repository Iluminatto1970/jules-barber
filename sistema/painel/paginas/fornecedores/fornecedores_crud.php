<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class FornecedoresCrud extends CRUD_Template {
    protected $tableName = 'fornecedores';
    protected $primaryKey = 'id';
    protected $title = 'Fornecedores';
    protected $singular = 'Fornecedor';
    protected $plural = 'Fornecedores';
    protected $modulePath = 'fornecedores';
    
    protected $fields = [
        'nome' => ['type' => 'text', 'label' => 'Nome', 'required' => true],
        'cnpj' => ['type' => 'text', 'label' => 'CNPJ/CPF'],
        'telefone' => ['type' => 'text', 'label' => 'Telefone'],
        'email' => ['type' => 'email', 'label' => 'Email'],
        'endereco' => ['type' => 'text', 'label' => 'Endereço'],
    ];
    
    protected $listColumns = ['id', 'nome', 'cnpj', 'telefone', 'email', 'data_cad'];
    protected $listLabels = ['ID', 'Nome', 'CNPJ/CPF', 'Telefone', 'Email', 'Cadastro'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
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
            <tr>
                <td>#<?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($item['nome']) ?></td>
                <td><?= htmlspecialchars($item['cnpj']) ?></td>
                <td><?= htmlspecialchars($item['telefone']) ?></td>
                <td><?= htmlspecialchars($item['email']) ?></td>
                <td><?= date('d/m/Y', strtotime($item['data_cad'])) ?></td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['cnpj']) ?>', '<?= htmlspecialchars($item['telefone']) ?>', '<?= htmlspecialchars($item['email']) ?>', '<?= htmlspecialchars($item['endereco']) ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['cnpj']) ?>', '<?= htmlspecialchars($item['telefone']) ?>', '<?= htmlspecialchars($item['email']) ?>', '<?= htmlspecialchars($item['endereco']) ?>', '<?= date('d/m/Y', strtotime($item['data_cad'])) ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
        $cnpj = $_POST['cnpj'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $endereco = $_POST['endereco'];
        
        if ($id == "") {
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET nome = :nome, cnpj = :cnpj, telefone = :telefone, email = :email, endereco = :endereco, data_cad = curDate()");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET nome = :nome, cnpj = :cnpj, telefone = :telefone, email = :email, endereco = :endereco WHERE id = '$id'");
        }
        
        $query->bindValue(":nome", $nome);
        $query->bindValue(":cnpj", $cnpj);
        $query->bindValue(":telefone", $telefone);
        $query->bindValue(":email", $email);
        $query->bindValue(":endereco", $endereco);
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

$crud = new FornecedoresCrud();

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