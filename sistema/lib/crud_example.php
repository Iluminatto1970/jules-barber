<?php

// Exemplo de uso do CRUD Template
// Este arquivo demonstra como usar a classe CRUD_Template

include_once 'crud_template.php';

// Criar instância do CRUD
$crud = new CRUD_Template();

// Exemplo: Trabalhando com a tabela 'admins' (super admin)
$table = 'admins';
$title = 'Administradores';

// 1. Obter estrutura da tabela
$structure = $crud->getTableStructure($table);
echo "<h3>Estrutura da tabela $table:</h3>";
echo "<pre>" . print_r($structure, true) . "</pre>";

// 2. Obter todos os registros
$records = $crud->getAll($table);
echo "<h3>Todos os registros de $table:</h3>";
echo "<pre>" . print_r($records, true) . "</pre>";

// 3. Criar novo registro (exemplo)
$newData = [
    'nome' => 'João Silva',
    'email' => 'joao@exemplo.com',
    'senha' => password_hash('123456', PASSWORD_DEFAULT),
    'nivel' => 1
];

$validation = $crud->validateData($table, $newData);
echo "<h3>Validação dos dados:</h3>";
echo "<pre>" . print_r($validation, true) . "</pre>";

// 4. Contar registros
$count = $crud->count($table);
echo "<h3>Total de registros em $table: $count</h3>";

// 5. Gerar interface CRUD
$crudInterface = $crud->generateCRUDInterface($table, $title);
echo "<h3>Interface CRUD para $table:</h3>";
echo "<pre>" . print_r($crudInterface, true) . "</pre>";

// 6. Buscar registros
$searchResults = $crud->search($table, 'admin', ['nome', 'email']);
echo "<h3>Resultados da busca em $table:</h3>";
echo "<pre>" . print_r($searchResults, true) . "</pre>";

// 7. Exemplo de CRUD completo
$action = 'list'; // operações: list, create, update, delete, search
$data = [
    'search' => 'admin',
    'columns' => ['nome', 'email']
];

$result = $crud->handleCRUD($table, $action, $data);
echo "<h3>Resultado da operação CRUD ($action):</h3>";
echo "<pre>" . print_r($result, true) . "</pre>";

?>

<h2>Documentação do CRUD Template</h2>

<ul>
<li><strong>getAll($table, $order, $limit)</strong> - Obtem todos os registros de uma tabela</li>
<li><strong>getById($table, $id)</strong> - Obtem registro por ID</li>
<li><strong>create($table, $data)</strong> - Cria novo registro</li>
<li><strong>update($table, $id, $data)</strong> - Atualiza registro existente</li>
<li><strong>delete($table, $id)</strong> - Deleta registro</li>
<li><strong>search($table, $searchTerm, $columns)</strong> - Busca registros</li>
<li><strong>count($table, $where)</strong> - Conta registros</li>
<li><strong>validateData($table, $data, $isUpdate)</strong> - Valida dados</li>
<li><strong>generateCRUDInterface($table, $title, $columns)</strong> - Gera interface CRUD</li>
<li><strong>handleCRUD($table, $action, $data, $id)</strong> - Lida com operações CRUD</li>
</ul>

<p><a href="<?= $_SERVER['PHP_SELF'] ?>">Recarregar exemplo</a></p>