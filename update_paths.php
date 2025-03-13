<?php
// Script para atualizar os caminhos de arquivo no banco de dados
require_once __DIR__ . '/db.php';

// Iniciar uma sessão para evitar que o script seja executado várias vezes
session_start();

// Verificar se está sendo executado pela linha de comando
$is_cli = (php_sapi_name() === 'cli');
$confirm = false;

if ($is_cli) {
    // Verificar argumento na linha de comando
    foreach ($argv as $arg) {
        if ($arg === 'confirm=yes') {
            $confirm = true;
            break;
        }
    }
} else {
    // Verificar parâmetro na URL
    $confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
}

// Verificar se o script já foi executado
$executed = false;
if (!$is_cli && isset($_SESSION['update_paths_executed']) && $_SESSION['update_paths_executed'] === true) {
    $executed = true;
}

if ($confirm && !$executed) {
    // Atualizar todos os registros que têm prefixo 'soudigital/'
    $sql = "UPDATE reembolsos SET arquivo_path = REPLACE(arquivo_path, 'soudigital/', '') WHERE arquivo_path LIKE 'soudigital/%'";
    
    if ($conn->query($sql) === TRUE) {
        $affected_rows = $conn->affected_rows;
        echo "<p>Sucesso! {$affected_rows} registros foram atualizados.</p>";
        
        // Marcar como executado apenas se não estiver em CLI
        if (!$is_cli) {
            $_SESSION['update_paths_executed'] = true;
        }
    } else {
        echo "<p>Erro ao atualizar registros: " . $conn->error . "</p>";
    }
} else {
    // Contar registros com prefixo 'soudigital/'
    $sql = "SELECT COUNT(*) as total FROM reembolsos WHERE arquivo_path LIKE 'soudigital/%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_to_update = $row['total'];
    
    echo "<h1>Atualização de Caminhos de Arquivos</h1>";
    
    if ($executed) {
        echo "<p>Este script já foi executado anteriormente. Se você deseja executá-lo novamente, limpe os cookies do navegador ou sua sessão.</p>";
    } else if ($total_to_update > 0) {
        echo "<p>Este script irá atualizar {$total_to_update} registros, removendo o prefixo 'soudigital/' dos caminhos de arquivo.</p>";
        echo "<p><a href='?confirm=yes' style='background-color: #f44336; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Clique aqui para confirmar a atualização</a></p>";
        echo "<p>Aviso: Esta ação é irreversível. Certifique-se de ter feito backup do banco de dados antes de continuar.</p>";
    } else {
        echo "<p>Não há registros para atualizar. Todos os caminhos já estão corretos.</p>";
    }
    
    // Mostrar estatísticas
    echo "<h2>Estatísticas:</h2>";
    
    // Contar registros com prefixo 'soudigital/'
    echo "<p>Registros com prefixo 'soudigital/': {$total_to_update}</p>";
    
    // Contar registros sem prefixo 'soudigital/'
    $sql = "SELECT COUNT(*) as total FROM reembolsos WHERE arquivo_path NOT LIKE 'soudigital/%' AND arquivo_path IS NOT NULL";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    echo "<p>Registros sem prefixo 'soudigital/': {$row['total']}</p>";
    
    // Exemplo dos primeiros 5 registros
    $sql = "SELECT id, arquivo_path FROM reembolsos WHERE arquivo_path LIKE 'soudigital/%' LIMIT 5";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<h3>Exemplos dos primeiros 5 registros que serão atualizados:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Caminho Atual</th><th>Novo Caminho</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $new_path = str_replace('soudigital/', '', $row['arquivo_path']);
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['arquivo_path']}</td>";
            echo "<td>{$new_path}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

$conn->close();
?> 