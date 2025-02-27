<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'db.php';
    echo "Conexão com o banco de dados estabelecida com sucesso!<br>";
    
    // Testar se a tabela usuarios existe
    $result = $conn->query("SHOW TABLES LIKE 'usuarios'");
    if ($result->num_rows > 0) {
        echo "Tabela 'usuarios' encontrada!<br>";
    } else {
        echo "Tabela 'usuarios' não encontrada!<br>";
    }
    
    // Testar se a tabela notificacoes existe
    $result = $conn->query("SHOW TABLES LIKE 'notificacoes'");
    if ($result->num_rows > 0) {
        echo "Tabela 'notificacoes' encontrada!<br>";
    } else {
        echo "Tabela 'notificacoes' não encontrada!<br>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?> 