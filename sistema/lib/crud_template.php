<?php

// Template Base para CRUD Operations
// Este arquivo fornece funções comuns para operações CRUD em todo o sistema

class CRUD_Template {
    
    // Conexão com o banco de dados
    private $conn;
    
    // Construtor
    public function __construct() {
        // Incluir conexão com o banco
        include_once 'conexao.php';
        $this->conn = $conn;
    }
    
    // Função genérica para obter estrutura da tabela
    public function getTableStructure($table) {
        try {
            $stmt = $this->conn->prepare("DESCRIBE $table");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para obter todos os registros
    public function getAll($table, $order = 'id DESC', $limit = '') {
        try {
            $sql = "SELECT * FROM $table ORDER BY $order";
            if (!empty($limit)) {
                $sql .= " LIMIT $limit";
            }
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para obter registro por ID
    public function getById($table, $id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para criar registro
    public function create($table, $data) {
        try {
            // Construir consulta SQL
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            
            // Executar com os dados
            if ($stmt->execute(array_values($data))) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para atualizar registro
    public function update($table, $id, $data) {
        try {
            // Construir cláusula SET
            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "$key = ?";
            }
            $setClause = implode(', ', $setClause);
            
            $sql = "UPDATE $table SET $setClause WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            
            // Executar com os dados
            $values = array_values($data);
            $values[] = $id;
            
            return $stmt->execute($values);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para deletar registro
    public function delete($table, $id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM $table WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para buscar registros
    public function search($table, $searchTerm, $columns = [], $order = 'id DESC') {
        try {
            if (empty($columns)) {
                $columns = ['*'];
            }
            
            $whereClause = [];
            foreach ($columns as $column) {
                $whereClause[] = "$column LIKE ?";
            }
            $whereClause = implode(' OR ', $whereClause);
            
            $sql = "SELECT * FROM $table WHERE $whereClause ORDER BY $order";
            $stmt = $this->conn->prepare($sql);
            
            $searchPattern = "%$searchTerm%";
            $params = array_fill(0, count($columns), $searchPattern);
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para contar registros
    public function count($table, $where = '') {
        try {
            $sql = "SELECT COUNT(*) as total FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Função genérica para validar dados
    public function validateData($table, $data, $isUpdate = false) {
        // Obter estrutura da tabela
        $structure = $this->getTableStructure($table);
        if (!$structure) {
            return ['success' => false, 'message' => 'Estrutura da tabela não encontrada'];
        }
        
        $errors = [];
        
        foreach ($structure as $field) {
            $fieldName = $field['Field'];
            $type = $field['Type'];
            $null = $field['Null'];
            $key = $field['Key'];
            $default = $field['Default'];
            
            // Pular chave primária para atualizações
            if ($isUpdate && $key === 'PRI') {
                continue;
            }
            
            // Verificar campos obrigatórios
            if ($null === 'NO' && $default === null && !isset($data[$fieldName])) {
                $errors[] = "Campo '$fieldName' é obrigatório";
            }
            
            // Verificar tipos de dados (validação básica)
            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];
                
                // Verificar strings vazias quando campo NÃO pode ser NULL
                if ($value === '' && $null === 'NO') {
                    $errors[] = "Campo '$fieldName' não pode estar vazio";
                }
                
                // Verificar campos numéricos
                if (strpos($type, 'int') !== false || strpos($type, 'decimal') !== false) {
                    if (!is_numeric($value)) {
                        $errors[] = "Campo '$fieldName' deve ser numérico";
                    }
                }
                
                // Verificar tamanho de strings
                if (strpos($type, 'varchar') !== false) {
                    preg_match('/varchar\((\d+)\)/', $type, $matches);
                    if (isset($matches[1]) && strlen($value) > $matches[1]) {
                        $errors[] = "Campo '$fieldName' excede o tamanho máximo de {$matches[1]} caracteres";
                    }
                }
            }
        }
        
        if (empty($errors)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }
    }
    
    // Função genérica para gerar interface CRUD
    public function generateCRUDInterface($table, $title, $columns = []) {
        // Esta função geraria HTML para interface CRUD
        // Por enquanto, retornamos uma estrutura simples
        return [
            'table' => $table,
            'title' => $title,
            'columns' => $columns ?: $this->getTableStructure($table),
            'routes' => [
                'list' => "?page=$table",
                'create' => "?page=$table&action=create",
                'edit' => "?page=$table&action=edit&id=",
                'delete' => "?page=$table&action=delete&id="
            ]
        ];
    }
    
    // Função genérica para lidar com operações CRUD
    public function handleCRUD($table, $action, $data = [], $id = null) {
        switch ($action) {
            case 'list':
                return $this->getAll($table);
            case 'create':
                $validation = $this->validateData($table, $data);
                if (!$validation['success']) {
                    return ['success' => false, 'message' => $validation['message']];
                }
                return $this->create($table, $data);
            case 'update':
                $validation = $this->validateData($table, $data, true);
                if (!$validation['success']) {
                    return ['success' => false, 'message' => $validation['message']];
                }
                return $this->update($table, $id, $data);
            case 'delete':
                return $this->delete($table, $id);
            case 'search':
                return $this->search($table, $data['search'], $data['columns']);
            default:
                return ['success' => false, 'message' => 'Ação inválida'];
        }
    }
}
?>