<?php
// Incluir a conexão com o banco de dados
require_once './includes/db.php';

echo "Verificando a tabela 'reports':\n";

// Verificar se a tabela existe
$result = $conn->query("SHOW TABLES LIKE 'reports'");
if ($result && $result->num_rows > 0) {
    echo "A tabela 'reports' existe.\n\n";
    
    // Mostrar estrutura da tabela
    echo "Estrutura da tabela 'reports':\n";
    $result = $conn->query("DESCRIBE reports");
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
    $result = $conn->query("SELECT COUNT(*) as total FROM reports");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "\nTotal de registros na tabela 'reports': " . $row['total'] . "\n";
    } else {
        echo "\nErro ao contar registros: " . $conn->error . "\n";
    }
} else {
    echo "A tabela 'reports' NÃO existe!\n";
}

// Testar uma consulta simples
echo "\nTestando consulta que é usada em visualizar-relatorios.php:\n";
try {
    $result = $conn->query("SELECT reports.*, users.name as user_name, users.profile_image 
                            FROM reports 
                            JOIN users ON reports.user_id = users.id 
                            ORDER BY reports.data_chamado DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        echo "Consulta executada com sucesso. Encontrado " . $result->num_rows . " registro(s).\n";
        $row = $result->fetch_assoc();
        echo "Exemplo de dados: \n";
        echo "- Número do chamado: " . ($row['numero_chamado'] ?? 'N/A') . "\n";
        echo "- Cliente: " . ($row['cliente'] ?? 'N/A') . "\n";
        echo "- Usuário: " . ($row['user_name'] ?? 'N/A') . "\n";
    } else {
        echo "Consulta executada mas não retornou resultados ou houve erro: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Erro ao executar consulta: " . $e->getMessage() . "\n";
}
?> 