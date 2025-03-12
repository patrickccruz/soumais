<?php
// Testar conexão direta
echo "Tentando conexão direta:\n";
try {
    $conn_direct = new mysqli('localhost', 'sou_digital', 'SuaSenhaSegura123!', 'sou_digital');
    if ($conn_direct->connect_error) {
        echo "Erro na conexão direta: " . $conn_direct->connect_error . "\n";
    } else {
        echo "Conexão direta bem-sucedida!\n";
    }
} catch (Exception $e) {
    echo "Exceção na conexão direta: " . $e->getMessage() . "\n";
}

// Testar conexão via arquivo
echo "\nTentando conexão via arquivo:\n";
try {
    include_once './includes/db.php';
    if (isset($conn) && !$conn->connect_error) {
        echo "Conexão via arquivo bem-sucedida!\n";
    } else {
        echo "Erro na conexão via arquivo\n";
    }
} catch (Exception $e) {
    echo "Exceção na conexão via arquivo: " . $e->getMessage() . "\n";
}

// Testar se as tabelas existem
echo "\nVerificando tabelas:\n";
$tables = ["users", "reembolsos", "notificacoes", "reports"];
$conn_to_use = isset($conn) ? $conn : (isset($conn_direct) ? $conn_direct : null);

if ($conn_to_use) {
    foreach ($tables as $table) {
        $result = $conn_to_use->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "Tabela $table existe\n";
        } else {
            echo "Tabela $table NÃO existe\n";
        }
    }
} else {
    echo "Não foi possível verificar as tabelas - sem conexão\n";
}

// Verificar se o usuário do banco de dados tem permissões
echo "\nVerificando permissões do usuário:\n";
if ($conn_to_use) {
    try {
        $result = $conn_to_use->query("SHOW GRANTS FOR CURRENT_USER()");
        if ($result) {
            while ($row = $result->fetch_row()) {
                echo $row[0] . "\n";
            }
        } else {
            echo "Não foi possível verificar permissões\n";
        }
    } catch (Exception $e) {
        echo "Erro ao verificar permissões: " . $e->getMessage() . "\n";
    }
}

// Mostrar informações do PHP
echo "\nInformações do PHP:\n";
echo "Versão do PHP: " . phpversion() . "\n";
echo "Extensão mysqli carregada: " . (extension_loaded('mysqli') ? 'Sim' : 'Não') . "\n";
?> 