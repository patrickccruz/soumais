<?php
// Script para verificar os caminhos de arquivo no banco de dados
require_once __DIR__ . '/db.php';

// Conectar ao banco de dados (não é necessário chamar a função, pois o $conn já está disponível no db.php)
// $conn = conectar();

// Consultar os primeiros 10 reembolsos para verificar os caminhos
$sql = "SELECT id, arquivo_path FROM reembolsos LIMIT 10";
$result = $conn->query($sql);

echo "<h1>Verificação de Caminhos de Arquivos</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Caminho Original</th><th>Caminho Corrigido</th></tr>";

while ($row = $result->fetch_assoc()) {
    $original_path = $row['arquivo_path'];
    
    // Simular a correção que seria feita
    $fixed_path = str_replace('soudigital/', '', "/{$original_path}");
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$original_path}</td>";
    echo "<td>{$fixed_path}</td>";
    echo "</tr>";
}

echo "</table>";

// Contar registros com prefixo 'soudigital/'
$sql2 = "SELECT COUNT(*) as total FROM reembolsos WHERE arquivo_path LIKE 'soudigital/%'";
$result2 = $conn->query($sql2);
$row2 = $result2->fetch_assoc();

echo "<p>Total de registros com prefixo 'soudigital/': {$row2['total']}</p>";

// Contar registros sem prefixo 'soudigital/'
$sql3 = "SELECT COUNT(*) as total FROM reembolsos WHERE arquivo_path NOT LIKE 'soudigital/%' AND arquivo_path IS NOT NULL";
$result3 = $conn->query($sql3);
$row3 = $result3->fetch_assoc();

echo "<p>Total de registros sem prefixo 'soudigital/': {$row3['total']}</p>";

$conn->close();
?> 