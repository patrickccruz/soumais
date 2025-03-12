<?php
echo "<h1>Teste de Inclusão do db2.php</h1>";

// Incluir o arquivo db2.php
echo "<p>Tentando incluir db2.php...</p>";
include_once 'db2.php';

// Verificar se a variável de teste existe
if (isset($conexao_ok) && $conexao_ok === true) {
    echo "<p style='color:green'>✓ Arquivo db2.php incluído e executado com sucesso!</p>";
    
    // Verificar a conexão
    if (isset($conn) && !$conn->connect_error) {
        echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida!</p>";
        
        // Teste de consulta
        $result = $conn->query("SELECT VERSION() as version");
        if ($result && $row = $result->fetch_assoc()) {
            echo "<p>Versão do MySQL: " . htmlspecialchars($row['version']) . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Problema na conexão com o banco de dados</p>";
    }
} else {
    echo "<p style='color:red'>✗ O arquivo foi incluído, mas a variável de teste não está definida corretamente</p>";
}

// Informações de sistema
echo "<h2>Informações do Sistema</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Include Path: " . get_include_path() . "\n";
echo "Current Directory: " . __DIR__ . "\n";
echo "</pre>";

// Link para outros testes
echo "<p>";
echo "<a href='info.php'>Ver phpinfo()</a> | ";
echo "<a href='teste-php.php'>Voltar ao teste principal</a>";
echo "</p>";
?> 