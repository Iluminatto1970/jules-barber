<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class ClientesCrud extends CRUD_Template {
    protected $tableName = 'clientes';
    protected $primaryKey = 'id';
    protected $title = 'Clientes';
    protected $singular = 'Cliente';
    protected $plural = 'Clientes';
    protected $modulePath = 'clientes';
    
    protected $fields = [
        'nome' => ['type' => 'text', 'label' => 'Nome', 'required' => true],
        'telefone' => ['type' => 'text', 'label' => 'Telefone'],
        'endereco' => ['type' => 'text', 'label' => 'Endereço'],
        'data_nasc' => ['type' => 'date', 'label' => 'Nascimento'],
        'cartoes' => ['type' => 'number', 'label' => 'Cartões'],
    ];
    
    protected $listColumns = ['id', 'nome', 'telefone', 'data_cad', 'data_nasc', 'data_retorno', 'cartoes'];
    protected $listLabels = ['ID', 'Nome', 'Telefone', 'Cadastro', 'Nascimento', 'Retorno', 'Cartões'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
        global $pdo;
        
        $items = $this->getAll($this->tableName, 'id DESC');
        $data_atual = date('Y-m-d');
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
            $data_cadF = date('d/m/Y', strtotime($item['data_cad']));
            $data_nascF = $item['data_nasc'] ? date('d/m/Y', strtotime($item['data_nasc'])) : 'Sem Data';
            $data_retornoF = $item['data_retorno'] ? date('d/m/Y', strtotime($item['data_retorno'])) : '';
            
            $classe_retorno = (!empty($item['data_retorno']) && strtotime($item['data_retorno']) < strtotime($data_atual)) ? 'text-danger' : '';
            
            $query2 = $pdo->query("SELECT nome FROM servicos WHERE id = '{$item['ultimo_servico']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_servico = count($res2) > 0 ? $res2[0]['nome'] : 'Nenhum!';
            ?>
            <tr>
                <td><?= htmlspecialchars($item['nome']) ?></td>
                <td>
                    <a href="https://wa.me/55<?= preg_replace('/[ ()-]+/', '', $item['telefone']) ?>" target="_blank">
                        <?= htmlspecialchars($item['telefone']) ?>
                    </a>
                </td>
                <td><?= $data_cadF ?></td>
                <td><?= $data_nascF ?></td>
                <td class="<?= $classe_retorno ?>"><?= $data_retornoF ?></td>
                <td><?= $item['cartoes'] ?></td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['telefone']) ?>', '<?= htmlspecialchars($item['endereco']) ?>', '<?= $item['data_nasc'] ?>', '<?= $item['cartoes'] ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['telefone']) ?>', '<?= $item['cartoes'] ?>', '<?= $data_cadF ?>', '<?= $data_nascF ?>', '<?= htmlspecialchars($item['endereco']) ?>', '<?= $data_retornoF ?>', '<?= htmlspecialchars($nome_servico) ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
        global $pdo, $pdo_saas, $saas_plano_ctx, $empresa_id;
        
        saas_plano_exigir_ativo($saas_plano_ctx, $pdo_saas, $empresa_id, 'clientes/salvar');
        
        $nome = $_POST['nome'];
        $telefone = $_POST['telefone'];
        $endereco = $_POST['endereco'];
        $data_nasc = $_POST['data_nasc'];
        $cartoes = $_POST['cartoes'] ?? 0;
        
        if ($id == "") {
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET nome = :nome, telefone = :telefone, endereco = :endereco, data_nasc = :data_nasc, cartoes = :cartoes, data_cad = curDate()");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET nome = :nome, telefone = :telefone, endereco = :endereco, data_nasc = :data_nasc, cartoes = :cartoes WHERE id = '$id'");
        }
        
        $query->bindValue(":nome", $nome);
        $query->bindValue(":telefone", $telefone);
        $query->bindValue(":endereco", $endereco);
        $query->bindValue(":data_nasc", $data_nasc);
        $query->bindValue(":cartoes", $cartoes);
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

$crud = new ClientesCrud();

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