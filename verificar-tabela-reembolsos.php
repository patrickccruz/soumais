<?php
// Incluir a conexão com o banco de dados
require_once './includes/db.php';

echo "Verificando a tabela 'reembolsos':\n";

// Verificar se a tabela existe
$result = $conn->query("SHOW TABLES LIKE 'reembolsos'");
if ($result && $result->num_rows > 0) {
    echo "A tabela 'reembolsos' existe.\n\n";
    
    // Mostrar estrutura da tabela
    echo "Estrutura da tabela 'reembolsos':\n";
    $result = $conn->query("DESCRIBE reembolsos");
    if ($result) {
        echo "+-----------------+-------------+------+-----+---------+----------------+\n";
        echo "| Field           | Type        | Null | Key | Default | Extra          |\n";
        echo "+-----------------+-------------+------+-----+---------+----------------+\n";
        
        while ($row = $result->fetch_assoc()) {
            printf("| %-15s | %-11s | %-4s | %-3s | %-7s | %-14s |\n", 
                   $row['Field'], 
                   $row['Type'], 
                   $row['Null'], 
                   $row['Key'], 
                   $row['Default'] ?? 'NULL', 
                   $row['Extra']);
        }
        
        echo "+-----------------+-------------+------+-----+---------+----------------+\n";
    } else {
        echo "Erro ao descrever a tabela: " . $conn->error . "\n";
    }
    
    // Contar registros
    $result = $conn->query("SELECT COUNT(*) as total FROM reembolsos");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "\nTotal de registros na tabela 'reembolsos': " . $row['total'] . "\n";
    } else {
        echo "\nErro ao contar registros: " . $conn->error . "\n";
    }
} else {
    echo "A tabela 'reembolsos' NÃO existe!\n";
}
?> 