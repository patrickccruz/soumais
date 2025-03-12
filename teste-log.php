<?php
// Teste de logs e diagnóstico de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se logs estão funcionando
error_log("Teste de log em " . date('Y-m-d H:i:s'));

// Mostrar configurações de log
echo "<h1>Diagnóstico de Páginas em Branco</h1>";

echo "<h2>Configurações de Erro do PHP</h2>";
echo "<pre>";
echo "display_errors = " . ini_get('display_errors') . "\n";
echo "error_reporting = " . ini_get('error_reporting') . "\n";
echo "error_log = " . ini_get('error_log') . "\n";
echo "log_errors = " . ini_get('log_errors') . "\n";
echo "</pre>";

// Verificar arquivos de log recentes
echo "<h2>Últimas Linhas do Log de Erros</h2>";
echo "<pre>";
$comando = 'tail -n 50 /var/log/apache2/error.log';
system($comando);
echo "</pre>";

// Verificar se consegue conectar ao banco
echo "<h2>Teste de Conexão ao Banco</h2>";
try {
    $conn = new mysqli("localhost", "sou_digital", "SuaSenhaSegura123!", "sou_digital");
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão: " . $conn->connect_error);
    }
    echo "<p style='color:green'>✓ Conexão com banco de dados bem-sucedida!</p>";
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro: " . $e->getMessage() . "</p>";
}

// Informações de depuração
echo "<h2>Informações do Sistema</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Diretório Atual: " . __DIR__ . "\n";
echo "Arquivos no diretório:\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo " - $file\n";
    }
}
echo "</pre>";
?> 