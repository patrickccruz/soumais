<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico do MySQL/MariaDB</h1>";

// Função para fazer teste de conexão
function testar_conexao($host, $usuario, $senha, $banco = null) {
    try {
        $dsn = "mysql:host=$host";
        if ($banco) {
            $dsn .= ";dbname=$banco";
        }
        
        echo "<p>Tentando conexão PDO: $host, usuário: $usuario, banco: " . ($banco ?: 'nenhum') . "</p>";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $usuario, $senha, $options);
        
        echo "<p style='color:green'>✓ Conexão PDO bem-sucedida!</p>";
        
        // Ver informações do servidor
        $info = $pdo->query("SELECT VERSION() as version")->fetch();
        echo "<p>Versão do MySQL/MariaDB: " . $info['version'] . "</p>";
        
        // Verificar privilégios
        if ($banco) {
            echo "<h3>Privilégios para o usuário '$usuario' no banco '$banco':</h3>";
            echo "<pre>";
            
            $stmt = $pdo->query("SHOW GRANTS FOR CURRENT_USER");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                echo htmlspecialchars($row[0]) . "\n";
            }
            
            echo "</pre>";
            
            // Verificar tabelas
            echo "<h3>Tabelas no banco '$banco':</h3>";
            try {
                $stmt = $pdo->query("SHOW TABLES");
                echo "<ul>";
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
                }
                echo "</ul>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>Erro ao listar tabelas: " . $e->getMessage() . "</p>";
            }
        }
        
        return $pdo;
    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ Erro PDO: " . $e->getMessage() . "</p>";
        return null;
    }
}

// Função para testar conexão com mysqli
function testar_mysqli($host, $usuario, $senha, $banco = null) {
    echo "<p>Tentando conexão MySQLi: $host, usuário: $usuario, banco: " . ($banco ?: 'nenhum') . "</p>";
    
    try {
        $mysqli = new mysqli($host, $usuario, $senha, $banco);
        
        if ($mysqli->connect_error) {
            throw new Exception("Falha na conexão: " . $mysqli->connect_error);
        }
        
        echo "<p style='color:green'>✓ Conexão MySQLi bem-sucedida!</p>";
        
        // Verificar tabelas se tiver banco
        if ($banco) {
            echo "<h3>Testando query simples com MySQLi:</h3>";
            try {
                if ($result = $mysqli->query("SELECT COUNT(*) as total FROM users")) {
                    $row = $result->fetch_assoc();
                    echo "<p>Total de usuários: " . $row['total'] . "</p>";
                } else {
                    echo "<p style='color:red'>Erro ao executar query: " . $mysqli->error . "</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>Erro ao executar query: " . $e->getMessage() . "</p>";
            }
        }
        
        $mysqli->close();
        return true;
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro MySQLi: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Teste com sou_digital
echo "<h2>Teste com Usuário sou_digital</h2>";
$sou_digital_pdo = testar_conexao('localhost', 'sou_digital', 'SuaSenhaSegura123!', 'sou_digital');
testar_mysqli('localhost', 'sou_digital', 'SuaSenhaSegura123!', 'sou_digital');

// Teste com root
echo "<h2>Teste com Usuário root</h2>";
$root_pdo = testar_conexao('localhost', 'root', '', 'sou_digital');
testar_mysqli('localhost', 'root', '', 'sou_digital');

echo "<h2>Informações do PHP</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "PDO Drivers disponíveis: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
echo "Extensão MySQLi carregada: " . (extension_loaded('mysqli') ? 'Sim' : 'Não') . "\n";
echo "Extensão PDO carregada: " . (extension_loaded('pdo') ? 'Sim' : 'Não') . "\n";
echo "Extensão PDO MySQL carregada: " . (extension_loaded('pdo_mysql') ? 'Sim' : 'Não') . "\n";
echo "</pre>";

echo "<h2>Arquivo de Configuração (db.php)</h2>";
echo "<p>O arquivo <code>/var/www/html/soudigital/db.php</code> está configurado para usar:</p>";
echo "<ul>";
echo "<li>Servidor: localhost</li>";
echo "<li>Usuário: sou_digital</li>";
echo "<li>Banco: sou_digital</li>";
echo "<li>Com senha: Sim</li>";
echo "</ul>";

echo "<p>Observação: Em algumas páginas do sistema, a conexão estava sendo feita diretamente com o usuário 'root' sem senha, o que pode estar causando os erros 500.</p>";

echo "<h2>Próximos Passos</h2>";
echo "<ol>";
echo "<li>Se o usuário 'sou_digital' funciona, mas 'root' não, todas as páginas devem usar o arquivo db.php que foi atualizado</li>";
echo "<li>Se ambos os usuários falham, verifique os privilégios no MySQL e ajuste conforme necessário</li>";
echo "<li>Verifique se o arquivo .htaccess não está bloqueando o acesso às páginas</li>";
echo "</ol>";

?> 