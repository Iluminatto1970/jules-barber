<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class ServicosCrud extends CRUD_Template {
    protected $tableName = 'servicos';
    protected $primaryKey = 'id';
    protected $title = 'Serviços';
    protected $singular = 'Serviço';
    protected $plural = 'Serviços';
    protected $modulePath = 'servicos';
    protected $imagePath = 'img/servicos/';
    protected $defaultImage = 'sem-foto.jpg';
    
    protected $fields = [
        'nome' => ['type' => 'text', 'label' => 'Nome', 'required' => true],
        'categoria' => ['type' => 'select', 'label' => 'Categoria', 'required' => true, 'relation' => 'cat_servicos'],
        'valor' => ['type' => 'number', 'label' => 'Valor', 'required' => true],
        'dias_retorno' => ['type' => 'number', 'label' => 'Dias Retorno'],
        'comissao' => ['type' => 'number', 'label' => 'Comissão'],
        'foto' => ['type' => 'file', 'label' => 'Foto'],
    ];
    
    protected $listColumns = ['id', 'nome', 'categoria', 'valor', 'dias_retorno', 'comissao', 'ativo'];
    protected $listLabels = ['ID', 'Nome', 'Categoria', 'Valor', 'Dias Retorno', 'Comissão', 'Status'];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function renderList() {
        global $pdo, $tipo_comissao;
        
        $items = $this->getAll($this->tableName, 'id DESC');
        
        $tipo_comissao_display = $tipo_comissao == 'Porcentagem' ? '%' : 'R$';
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
            $query2 = $pdo->query("SELECT nome FROM cat_servicos WHERE id = '{$item['categoria']}'");
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $nome_cat = count($res2) > 0 ? $res2[0]['nome'] : 'Sem Referência';
            
            $valorF = number_format($item['valor'], 2, ',', '.');
            $comissaoF = $tipo_comissao == 'Porcentagem' 
                ? number_format($item['comissao'], 0, ',', '.') . '%'
                : 'R$ ' . number_format($item['comissao'], 2, ',', '.');
            ?>
            <tr class="<?= $item['ativo'] == 'Não' ? 'text-muted' : '' ?>">
                <td>
                    <img src="img/servicos/<?= $item['foto'] ?: 'sem-foto.jpg' ?>" width="27px" class="mr-2">
                    <?= htmlspecialchars($item['nome']) ?>
                </td>
                <td><?= htmlspecialchars($nome_cat) ?></td>
                <td>R$ <?= $valorF ?></td>
                <td><?= $item['dias_retorno'] ?></td>
                <td><?= $comissaoF ?></td>
                <td>
                    <span class="status-pill <?= $item['ativo'] == 'Sim' ? 'status-success' : 'status-danger' ?>">
                        <?= $item['ativo'] ?>
                    </span>
                </td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>', '<?= $item['valor'] ?>', '<?= $item['categoria'] ?>', '<?= $item['dias_retorno'] ?>', '<?= htmlspecialchars($item['foto']) ?>', '<?= $item['comissao'] ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= $valorF ?>', '<?= htmlspecialchars($nome_cat) ?>', '<?= $item['dias_retorno'] ?>', '<?= $item['ativo'] ?>', '<?= htmlspecialchars($item['foto']) ?>', '<?= $comissaoF ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
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
    
    public function save($id = null) {
        global $pdo, $pdo_saas, $saas_plano_ctx, $empresa_id;
        
        saas_plano_exigir_ativo($saas_plano_ctx, $pdo_saas, $empresa_id, 'servicos/salvar');
        
        $nome = $_POST['nome'];
        $valor = $_POST['valor'];
        $categoria = $_POST['categoria'];
        $dias_retorno = $_POST['dias_retorno'];
        $comissao = $_POST['comissao'];
        
        if ($categoria == "0") {
            echo 'Selecione uma Categoria';
            exit();
        }
        
        $foto = $this->defaultImage;
        if (!empty($id)) {
            $query = $pdo->query("SELECT foto FROM {$this->tableName} WHERE id = '$id'");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            if (count($res) > 0) {
                $foto = $res[0]['foto'] ?: $this->defaultImage;
            }
        }
        
        if (!empty($_FILES['foto']['name'])) {
            $nome_img = date('d-m-Y H:i:s') . '-' . $_FILES['foto']['name'];
            $nome_img = preg_replace('/[ :]+/', '-', $nome_img);
            $caminho = '../../' . $this->imagePath . $nome_img;
            $imagem_temp = $_FILES['foto']['tmp_name'];
            $ext = pathinfo($nome_img, PATHINFO_EXTENSION);
            
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
                if ($foto != $this->defaultImage) {
                    @unlink('../../' . $this->imagePath . $foto);
                }
                $foto = $nome_img;
                move_uploaded_file($imagem_temp, $caminho);
            } else {
                echo 'Extensão de Imagem não permitida!';
                exit();
            }
        }
        
        if ($id == "") {
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET nome = :nome, valor = :valor, categoria = :categoria, dias_retorno = :dias_retorno, foto = '$foto', comissao = :comissao, ativo = 'Sim'");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET nome = :nome, valor = :valor, categoria = :categoria, dias_retorno = :dias_retorno, foto = '$foto', comissao = :comissao WHERE id = '$id'");
        }
        
        $query->bindValue(":nome", $nome);
        $query->bindValue(":valor", $valor);
        $query->bindValue(":categoria", $categoria);
        $query->bindValue(":dias_retorno", $dias_retorno);
        $query->bindValue(":comissao", $comissao);
        $query->execute();
        
        echo 'Salvo com Sucesso';
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

$crud = new ServicosCrud();

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
    case 'toggle':
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? 'Sim';
        $crud->toggleStatus($id, $status);
        break;
    default:
        $crud->renderList();
}