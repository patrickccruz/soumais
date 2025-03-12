<?php
echo "<h1>Teste de PHP</h1>";
echo "<p>O PHP está funcionando corretamente se você puder ver esta mensagem.</p>";
echo "<p>Data e hora atual: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";

// Testar se conseguimos incluir o arquivo db.php
echo "<h2>Teste de inclusão do arquivo db.php</h2>";
try {
    include_once 'db.php';
    echo "<p style='color:green'>✓ Arquivo db.php incluído com sucesso!</p>";
    
    // Verificar conexão
    if (isset($conn) && !$conn->connect_error) {
        echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida!</p>";
        
        // Testar consulta simples
        try {
            $result = $conn->query("SELECT VERSION() as version");
            if ($result && $row = $result->fetch_assoc()) {
                echo "<p>Versão do MySQL: " . htmlspecialchars($row['version']) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro ao executar consulta: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Erro na conexão com o banco de dados</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao incluir arquivo db.php: " . $e->getMessage() . "</p>";
}

// Informações de diagnóstico
echo "<h2>Informações do Sistema</h2>";
echo "<pre>";
echo "Diretório atual: " . __DIR__ . "\n";
echo "Caminho completo deste arquivo: " . __FILE__ . "\n";
echo "Servidor Web: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Usuário do processo: " . exec('whoami') . "\n";
echo "</pre>";

echo "<p><a href='index.php'>Voltar ao Início</a></p>";
?> 