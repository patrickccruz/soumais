<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Conexão com Banco de Dados</h1>";

// Tentar conexão usando credenciais diretamente
echo "<h2>Conexão 1: Usando credenciais diretamente do db.php</h2>";

$servername = "localhost";
$username = "sou_digital";
$password = "SuaSenhaSegura123!";
$dbname = "sou_digital";

try {
    echo "Tentando conectar com: servidor=$servername, usuário=$username, banco=$dbname<br>";
    $conn1 = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn1->connect_error) {
        throw new Exception("Falha na conexão: " . $conn1->connect_error);
    }
    
    echo "<span style='color:green'>✓ Conexão bem-sucedida!</span><br>";
    $conn1->close();
} catch (Exception $e) {
    echo "<span style='color:red'>✗ Erro: " . $e->getMessage() . "</span><br>";
}

// Tentar conexão usando include
echo "<h2>Conexão 2: Usando include</h2>";

try {
    echo "Tentando conectar usando ../includes/db.php<br>";
    include_once '../includes/db.php';
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Falha na conexão após include: " . ($conn->connect_error ?? "Variável \$conn não disponível"));
    }
    
    echo "<span style='color:green'>✓ Conexão bem-sucedida!</span><br>";
    
    // Tentar acessar tabela users (teste para verificar se as tabelas existem)
    echo "<h3>Testando acesso à tabela 'users'</h3>";
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    
    if ($result === false) {
        throw new Exception("Erro ao acessar tabela 'users': " . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    echo "Total de usuários na tabela: " . $row['total'] . "<br>";
    
} catch (Exception $e) {
    echo "<span style='color:red'>✗ Erro: " . $e->getMessage() . "</span><br>";
}

// Tentar conexão alternativa com usuário root (usada em várias páginas)
echo "<h2>Conexão 3: Usando usuário 'root'</h2>";

try {
    echo "Tentando conectar com: servidor=localhost, usuário=root, senha='', banco=sou_digital<br>";
    $conn3 = new mysqli('localhost', 'root', '', 'sou_digital');
    
    if ($conn3->connect_error) {
        throw new Exception("Falha na conexão: " . $conn3->connect_error);
    }
    
    echo "<span style='color:green'>✓ Conexão bem-sucedida!</span><br>";
    $conn3->close();
} catch (Exception $e) {
    echo "<span style='color:red'>✗ Erro: " . $e->getMessage() . "</span><br>";
}

echo "<h2>Verificação de Arquivos e Permissões</h2>";
echo "<pre>";
echo "Existência de upload_functions.php: " . (file_exists('../includes/upload_functions.php') ? "Sim" : "Não") . "\n";
echo "Permissão de upload_functions.php: " . (file_exists('../includes/upload_functions.php') ? substr(sprintf('%o', fileperms('../includes/upload_functions.php')), -4) : "N/A") . "\n";
echo "Existência de diretório de uploads: " . (is_dir('../uploads') ? "Sim" : "Não") . "\n";
echo "Permissão de diretório de uploads: " . (is_dir('../uploads') ? substr(sprintf('%o', fileperms('../uploads')), -4) : "N/A") . "\n";
echo "Função process_file_upload existe: " . (function_exists('process_file_upload') ? "Sim" : "Não") . "\n";
echo "</pre>";

// Verificar funções
echo "<h2>Testes de Funções</h2>";
if (!function_exists('process_file_upload')) {
    require_once '../includes/upload_functions.php';
    echo "Função process_file_upload após include: " . (function_exists('process_file_upload') ? "Disponível" : "Não Disponível") . "<br>";
}

echo "<h2>Informações do PHP</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";

echo "<p><a href='../index.php'>Voltar ao Início</a></p>";
?> 