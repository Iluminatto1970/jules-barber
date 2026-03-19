<?php

// Script para analisar estrutura do banco de dados
// Este script conecta no banco e lista todas as tabelas e suas colunas

require_once __DIR__ . '/conexao.php';

function analisar_tabelas($pdo, $banco) {
    echo "=== ANALISANDO BANCO DE DADOS: {$banco} ===\n\n";
    
    try {
        // Obter todas as tabelas
        $query = $pdo->query("SHOW TABLES");
        $tabelas = $query->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tabelas)) {
            echo "Nenhuma tabela encontrada no banco {$banco}.\n";
            return;
        }
        
        echo "Tabelas encontradas: " . count($tabelas) . "\n\n";
        
        $tabelas_analisadas = [];
        
        foreach ($tabelas as $tabela) {
            echo "Tabela: {$tabela}\n";
            
            // Obter colunas da tabela
            $query_cols = $pdo->query("DESCRIBE {$tabela}");
            $colunas = $query_cols->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($colunas as $coluna) {
                $tipo = $coluna['Type'];
                $nulo = $coluna['Null'];
                $padrao = $coluna['Default'];
                $extra = $coluna['Extra'];
                
                echo "  - {$coluna['Field']} ({$tipo})";
                if ($extra != '') echo " [{$extra}]";
                if ($padrao != '') echo " (padrao: {$padrao})";
                if ($nulo == 'NO') echo " (NOT NULL)";
                echo "\n";
            }
            
            // Obter indices da tabela
            $query_idx = $pdo->query("SHOW INDEX FROM {$tabela}");
            $indices = $query_idx->fetchAll(PDO::FETCH_ASSOC);
            
            $indices_agrupados = [];
            foreach ($indices as $indice) {
                $nome = $indice['Key_name'];
                $coluna = $indice['Column_name'];
                $seq = $indice['Seq_in_index'];
                $indices_agrupados[$nome][$seq] = $coluna;
            }
            
            if (!empty($indices_agrupados)) {
                echo "  Indices:\n";
                foreach ($indices_agrupados as $nome => $cols) {
                    ksort($cols);
                    $colunas_str = implode(', ', $cols);
                    $unico = in_array($nome, ['PRIMARY', 'UNIQUE']) ? ' (UNICO)' : '';
                    echo "    - {$nome}: {$colunas_str}{$unico}\n";
                }
            }
            
            echo "\n";
            $tabelas_analisadas[] = $tabela;
        }
        
        echo "=== RESUMO ===\n";
        echo "Total de tabelas analisadas: " . count($tabelas_analisadas) . "\n";
        
    } catch (Exception $e) {
        echo "Erro ao analisar tabelas: " . $e->getMessage() . "\n";
    }
}

// Analisar banco tenant
if ($pdo) {
    echo "=== BANCO TENANT (BARBEARIA) ===\n";
    analisar_tabelas($pdo, $banco);
    echo "\n";
}

// Analisar banco SaaS
if ($pdo_saas) {
    echo "=== BANCO SAAS (BARBEARIA_SAAS) ===\n";
    analisar_tabelas($pdo_saas, $saas_banco);
    echo "\n";
}

echo "=== ANALISE CONCLUIDA ===\n";