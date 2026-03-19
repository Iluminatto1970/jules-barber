<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class SaidasCrud extends CRUD_Template {
    protected $tableName = 'saidas';
    protected $primaryKey = 'id';
    protected $title = 'Saídas de Estoque';
    protected $singular = 'Saída';
    protected $plural = 'Saídas';
    protected $modulePath = 'saidas';
    
    protected $fields = [
        'produto' => ['type' => 'select', 'label' => 'Produto', 'required' => true, 'relation' => 'produtos'],
        'quantidade' => ['type' => 'number', 'label' => 'Quantidade', 'required' => true],
        'motivo' => ['type' => 'text', 'label' => 'Motivo'],
    ];
    
    protected $listColumns = ['id', 'produto', 'quantidade', 'motivo', 'usuario', 'data'];
    protected $listLabels = ['ID', 'Produto', 'Quantidade', 'Motivo', 'Usuário', 'Data'];
    
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
        </tr> 
        </thead> 
        <tbody>	
        <?php if (empty($items)): ?>
            <tr><td colspan="<?= count($this->listLabels) ?>" class="text-center text-muted py-4">Nenhum registro encontrado!</td></tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
            <?php
            $query2 = $pdo->query("SELECT nome, foto FROM produtos WHERE id = '{$item['produto']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_produto = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Referência';
            $foto_produto = count($res2) > 0 ? $res2[0]['foto'] : 'sem-foto.jpg';
            
            $query2 = $pdo->query("SELECT nome FROM usuarios WHERE id = '{$item['usuario']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_usuario = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Referência';
            ?>
            <tr>
                <td>
                    <img src="img/produtos/<?= $foto_produto ?>" width="27px" class="mr-2">
                    <?= htmlspecialchars($nome_produto) ?>
                </td>
                <td><?= $item['quantidade'] ?></td>
                <td><?= htmlspecialchars($item['motivo']) ?></td>
                <td><?= htmlspecialchars($nome_usuario) ?></td>
                <td><?= date('d/m/Y', strtotime($item['data'])) ?></td>
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
        global $pdo, $id_usuario;
        
        $produto = $_POST['produto'];
        $quantidade = $_POST['quantidade'];
        $motivo = $_POST['motivo'];
        
        $query = $pdo->query("SELECT estoque FROM produtos WHERE id = '$produto'");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $estoque_atual = count($res) > 0 ? $res[0]['estoque'] : 0;
        
        if ($estoque_atual < $quantidade) {
            echo 'Estoque insuficiente!';
            exit();
        }
        
        $query = $pdo->prepare("INSERT INTO {$this->tableName} SET produto = :produto, quantidade = :quantidade, motivo = :motivo, usuario = :usuario, data = curDate()");
        $query->bindValue(":produto", $produto);
        $query->bindValue(":quantidade", $quantidade);
        $query->bindValue(":motivo", $motivo);
        $query->bindValue(":usuario", $id_usuario);
        $query->execute();
        
        $query = $pdo->prepare("UPDATE produtos SET estoque = estoque - :quantidade WHERE id = :produto");
        $query->bindValue(":quantidade", $quantidade);
        $query->bindValue(":produto", $produto);
        $query->execute();
        
        echo 'Salvo com Sucesso';
    }
}

$crud = new SaidasCrud();

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