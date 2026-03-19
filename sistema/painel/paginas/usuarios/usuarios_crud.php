<?php
require_once(__DIR__ . '/../../lib/crud_template.php');

class UsuariosCrud extends CRUD_Template {
    protected $tableName = 'usuarios';
    protected $primaryKey = 'id';
    protected $title = 'Usuários';
    protected $singular = 'Usuário';
    protected $plural = 'Usuários';
    protected $modulePath = 'usuarios';
    protected $imagePath = 'img/perfil/';
    protected $defaultImage = 'sem-foto.jpg';
    
    protected $fields = [
        'nome' => ['type' => 'text', 'label' => 'Nome', 'required' => true],
        'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
        'telefone' => ['type' => 'text', 'label' => 'Telefone'],
        'cpf' => ['type' => 'text', 'label' => 'CPF'],
        'cargo' => ['type' => 'select', 'label' => 'Nível', 'required' => true, 'relation' => 'cargos'],
        'endereco' => ['type' => 'text', 'label' => 'Endereço'],
        'atendimento' => ['type' => 'select', 'label' => 'Atendimento', 'options' => ['Sim', 'Não']],
        'foto' => ['type' => 'file', 'label' => 'Foto'],
    ];
    
    protected $listColumns = ['id', 'nome', 'email', 'telefone', 'nivel', 'data', 'ativo'];
    protected $listLabels = ['ID', 'Nome', 'Email', 'Telefone', 'Nível', 'Cadastro', 'Status'];
    
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
            <tr class="<?= $item['ativo'] == 'Não' ? 'text-muted' : '' ?>">
                <td>#<?= (int) $item['id'] ?></td>
                <td>
                    <img src="img/perfil/<?= $item['foto'] ?: 'sem-foto.jpg' ?>" width="27px" class="mr-2">
                    <?= htmlspecialchars($item['nome']) ?>
                </td>
                <td><?= htmlspecialchars($item['email']) ?></td>
                <td><?= htmlspecialchars($item['telefone']) ?></td>
                <td><?= htmlspecialchars($item['nivel']) ?></td>
                <td><?= date('d/m/Y', strtotime($item['data'])) ?></td>
                <td>
                    <span class="status-pill <?= $item['ativo'] == 'Sim' ? 'status-success' : 'status-danger' ?>">
                        <?= $item['ativo'] ?>
                    </span>
                </td>
                <td>
                    <big><a href="#" onclick="editar('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['email']) ?>', '<?= htmlspecialchars($item['telefone']) ?>', '<?= htmlspecialchars($item['cpf']) ?>', '<?= htmlspecialchars($item['nivel']) ?>', '<?= htmlspecialchars($item['endereco']) ?>', '<?= htmlspecialchars($item['foto']) ?>', '<?= htmlspecialchars($item['atendimento']) ?>')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
                    <big><a href="#" onclick="mostrar('<?= htmlspecialchars($item['nome']) ?>', '<?= htmlspecialchars($item['email']) ?>', '<?= htmlspecialchars($item['cpf']) ?>', '<?= $item['nivel'] == 'Administrador' ? '******' : '******' ?>', '<?= htmlspecialchars($item['nivel']) ?>', '<?= date('d/m/Y', strtotime($item['data'])) ?>', '<?= $item['ativo'] ?>', '<?= htmlspecialchars($item['telefone']) ?>', '<?= htmlspecialchars($item['endereco']) ?>', '<?= htmlspecialchars($item['foto']) ?>', '<?= htmlspecialchars($item['atendimento']) ?>')" title="Ver Dados"><i class="fa fa-info-circle text-secondary"></i></a></big>
                    <big><a href="#" onclick="excluir('<?= $item['id'] ?>')" title="Excluir"><i class="fa fa-trash-o text-danger"></i></a></big>
                    <big><a href="#" onclick="ativar('<?= $item['id'] ?>', '<?= $item['ativo'] == 'Sim' ? 'Não' : 'Sim' ?>')" title="<?= $item['ativo'] == 'Sim' ? 'Desativar' : 'Ativar' ?>"><i class="fa <?= $item['ativo'] == 'Sim' ? 'fa-check-square' : 'fa-square-o' ?> text-success"></i></a></big>
                    <big><a href="#" onclick="permissoes('<?= $item['id'] ?>', '<?= htmlspecialchars($item['nome']) ?>')" title="Definir Permissões"><i class="fa fa-lock" style="color:blue; margin-left:3px"></i></a></big>
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
        
        saas_plano_exigir_ativo($saas_plano_ctx, $pdo_saas, $empresa_id, 'usuarios/salvar');
        saas_plano_exigir_recurso($saas_plano_ctx, 'menu_pessoas', 'Seu plano não permite gerenciar usuários.', $pdo_saas, $empresa_id);
        
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $cpf = $_POST['cpf'];
        $cargo = $_POST['cargo'];
        $endereco = $_POST['endereco'];
        $atendimento = $_POST['atendimento'];
        
        if ($id == "") {
            saas_plano_exigir_limite_total_tabela($pdo, $saas_plano_ctx, 'limite_usuarios', 'usuarios', 'Limite de usuários do plano atingido.', '1=1', $pdo_saas, $empresa_id);
        }
        
        if ($cargo == "0") {
            echo 'Cadastre um Cargo para o Usuário';
            exit();
        }
        
        $query = $pdo->query("SELECT * FROM {$this->tableName} WHERE email = '$email'");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($res) > 0 && $id != $res[0]['id']) {
            echo 'Email já Cadastrado, escolha outro!!';
            exit();
        }
        
        $query = $pdo->query("SELECT * FROM {$this->tableName} WHERE cpf = '$cpf'");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($res) > 0 && $id != $res[0]['id']) {
            echo 'CPF já Cadastrado, escolha outro!!';
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
            $senha = '123';
            $senha_crip = md5($senha);
            $query = $pdo->prepare("INSERT INTO {$this->tableName} SET nome = :nome, email = :email, cpf = :cpf, senha = '$senha', senha_crip = '$senha_crip', nivel = '$cargo', data = curDate(), ativo = 'Sim', telefone = :telefone, endereco = :endereco, foto = '$foto', atendimento = '$atendimento'");
        } else {
            $query = $pdo->prepare("UPDATE {$this->tableName} SET nome = :nome, email = :email, cpf = :cpf, nivel = '$cargo', telefone = :telefone, endereco = :endereco, foto = '$foto', atendimento = '$atendimento' WHERE id = '$id'");
        }
        
        $query->bindValue(":nome", $nome);
        $query->bindValue(":email", $email);
        $query->bindValue(":cpf", $cpf);
        $query->bindValue(":telefone", $telefone);
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
    
    public function toggleStatus($id, $status) {
        global $pdo;
        
        $query = $pdo->prepare("UPDATE {$this->tableName} SET ativo = ? WHERE id = ?");
        $query->execute([$status, $id]);
        
        echo 'Alterado com Sucesso';
    }
}

$crud = new UsuariosCrud();

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