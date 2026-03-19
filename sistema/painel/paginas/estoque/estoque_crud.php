<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class EstoqueCrud extends CRUD_Template {
    protected $tableName = 'produtos';
    protected $primaryKey = 'id';
    protected $title = 'Estoque de Produtos';
    protected $singular = 'Produto';
    protected $plural = 'Produtos';
    protected $modulePath = 'estoque';
    protected $imagePath = 'img/produtos/';
    protected $defaultImage = 'sem-foto.jpg';
    
    protected $listColumns = ['id', 'nome', 'categoria', 'estoque', 'valor_compra', 'valor_venda'];
    protected $listLabels = ['ID', 'Produto', 'Categoria', 'Estoque', 'Valor Compra', 'Valor Venda'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
        global $pdo;
        
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
            <?php
            $query2 = $pdo->query("SELECT nome FROM cat_produtos WHERE id = '{$item['categoria']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_cat = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Categoria';
            
            $estoque_class = $item['estoque'] <= 0 ? 'text-danger font-weight-bold' : ($item['estoque'] <= $item['estoque_minimo'] ? 'text-warning' : '');
            ?>
            <tr>
                <td>
                    <img src="img/produtos/<?= $item['foto'] ?: 'sem-foto.jpg' ?>" width="27px" class="mr-2">
                    <?= htmlspecialchars($item['nome']) ?>
                </td>
                <td><?= htmlspecialchars($nome_cat) ?></td>
                <td class="<?= $estoque_class ?>"><?= $item['estoque'] ?></td>
                <td>R$ <?= number_format($item['valor_compra'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($item['valor_venda'], 2, ',', '.') ?></td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>', '<?= $item['estoque'] ?>', '<?= $item['estoque_minimo'] ?>', '<?= $item['valor_compra'] ?>', '<?= $item['valor_venda'] ?>', '<?= $item['categoria'] ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= $item['estoque'] ?>', '<?= $item['estoque_minimo'] ?>', '<?= htmlspecialchars($nome_cat) ?>', 'R$ <?= number_format($item['valor_compra'], 2, ',', '.') ?>', 'R$ <?= number_format($item['valor_venda'], 2, ',', '.') ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
}

$crud = new EstoqueCrud();
$crud->renderList();