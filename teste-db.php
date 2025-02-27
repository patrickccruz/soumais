<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>Teste de Conexão com o Banco de Dados</h1>';

// Incluir arquivo de conexão
echo '<h2>Tentando incluir db.php</h2>';
try {
    require_once 'db.php';
    echo '<div style="color:green">✓ Arquivo db.php incluído com sucesso</div>';
} catch (Exception $e) {
    echo '<div style="color:red">✗ Erro ao incluir db.php: ' . $e->getMessage() . '</div>';
    die();
}

// Verificar conexão
echo '<h2>Verificando conexão com o banco de dados</h2>';
if (isset($conn) && !$conn->connect_error) {
    echo '<div style="color:green">✓ Conexão com o banco estabelecida com sucesso</div>';
    echo '<p>Servidor: ' . htmlspecialchars($servername) . '</p>';
    echo '<p>Base de dados: ' . htmlspecialchars($dbname) . '</p>';
    echo '<p>Usuário: ' . htmlspecialchars($username) . '</p>';
} else {
    echo '<div style="color:red">✗ Falha na conexão: ' . ($conn->connect_error ?? 'Erro desconhecido') . '</div>';
    die();
}

// Listar tabelas
echo '<h2>Tabelas disponíveis no banco:</h2>';
$result = $conn->query("SHOW TABLES");
if ($result) {
    if ($result->num_rows > 0) {
        echo '<ul>';
        while ($row = $result->fetch_row()) {
            echo '<li>' . htmlspecialchars($row[0]) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<div style="color:orange">! Nenhuma tabela encontrada no banco de dados</div>';
    }
} else {
    echo '<div style="color:red">✗ Erro ao listar tabelas: ' . $conn->error . '</div>';
}

// Verificar tabela de usuários
echo '<h2>Verificando tabela de usuários:</h2>';

// Verificar tabela 'users'
$users_exists = $conn->query("SHOW TABLES LIKE 'users'");
if ($users_exists->num_rows > 0) {
    echo '<div style="color:green">✓ Tabela "users" encontrada</div>';
    
    // Exibir estrutura da tabela users
    echo '<h3>Estrutura da tabela users:</h3>';
    $result = $conn->query("DESCRIBE users");
    if ($result && $result->num_rows > 0) {
        echo '<table border="1" cellpadding="5">';
        echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Contar usuários
        $count = $conn->query("SELECT COUNT(*) as total FROM users");
        if ($count) {
            $row = $count->fetch_assoc();
            echo '<p>Total de usuários na tabela: ' . $row['total'] . '</p>';
        }
    } else {
        echo '<div style="color:red">✗ Erro ao obter estrutura da tabela users: ' . $conn->error . '</div>';
    }
} else {
    echo '<div style="color:orange">! Tabela "users" não encontrada</div>';
}

// Verificar tabela 'usuarios'
$usuarios_exists = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($usuarios_exists->num_rows > 0) {
    echo '<div style="color:green">✓ Tabela "usuarios" encontrada</div>';
    
    // Exibir estrutura da tabela usuarios
    echo '<h3>Estrutura da tabela usuarios:</h3>';
    $result = $conn->query("DESCRIBE usuarios");
    if ($result && $result->num_rows > 0) {
        echo '<table border="1" cellpadding="5">';
        echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($row['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Contar usuários
        $count = $conn->query("SELECT COUNT(*) as total FROM usuarios");
        if ($count) {
            $row = $count->fetch_assoc();
            echo '<p>Total de usuários na tabela: ' . $row['total'] . '</p>';
        }
    } else {
        echo '<div style="color:red">✗ Erro ao obter estrutura da tabela usuarios: ' . $conn->error . '</div>';
    }
} else {
    echo '<div style="color:orange">! Tabela "usuarios" não encontrada</div>';
}

// Fechar conexão
$conn->close();
echo '<div style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px;">Conexão fechada</div>';
?> 