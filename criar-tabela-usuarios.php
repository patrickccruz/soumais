<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>Inicialização do Banco de Dados - Sistema Sou Digital</h1>';

// Incluir arquivo de conexão
echo '<h2>Conectando ao banco de dados...</h2>';
try {
    require_once 'db.php';
    echo '<div style="color:green">✓ Conexão estabelecida com sucesso</div>';
} catch (Exception $e) {
    echo '<div style="color:red">✗ Erro ao conectar ao banco de dados: ' . $e->getMessage() . '</div>';
    die();
}

// Verificar se a tabela de usuários existe
$tabela_users_existe = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
$tabela_usuarios_existe = $conn->query("SHOW TABLES LIKE 'usuarios'")->num_rows > 0;

echo '<h2>Verificando tabelas existentes:</h2>';
echo '<ul>';
echo '<li>Tabela "users": ' . ($tabela_users_existe ? '<span style="color:green">Existe</span>' : '<span style="color:orange">Não existe</span>') . '</li>';
echo '<li>Tabela "usuarios": ' . ($tabela_usuarios_existe ? '<span style="color:green">Existe</span>' : '<span style="color:orange">Não existe</span>') . '</li>';
echo '</ul>';

// Decidir qual tabela usar ou criar
$tabela_a_usar = null;

if ($tabela_users_existe) {
    $tabela_a_usar = 'users';
    echo '<div style="color:blue">A tabela "users" será usada</div>';
} else if ($tabela_usuarios_existe) {
    $tabela_a_usar = 'usuarios';
    echo '<div style="color:blue">A tabela "usuarios" será usada</div>';
} else {
    $tabela_a_usar = 'users'; // Padrão para criação
    echo '<div style="color:blue">Nenhuma tabela de usuários encontrada. A tabela "users" será criada.</div>';
    
    // Criar a tabela 'users'
    $sql_criar_tabela = "CREATE TABLE users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        profile_image VARCHAR(255),
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql_criar_tabela) === TRUE) {
        echo '<div style="color:green">✓ Tabela "users" criada com sucesso</div>';
    } else {
        echo '<div style="color:red">✗ Erro ao criar tabela "users": ' . $conn->error . '</div>';
        die();
    }
}

// Verificar se existe pelo menos um usuário administrador
$sql_check_admin = "SELECT COUNT(*) as total FROM {$tabela_a_usar} WHERE is_admin = 1";
$result = $conn->query($sql_check_admin);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_admins = $row['total'];
    
    echo '<h2>Verificando usuários administradores:</h2>';
    if ($total_admins > 0) {
        echo '<div style="color:green">✓ ' . $total_admins . ' usuário(s) administrador(es) encontrado(s)</div>';
    } else {
        echo '<div style="color:orange">! Nenhum usuário administrador encontrado</div>';
        
        // Criar um usuário administrador padrão
        echo '<h2>Criando usuário administrador padrão...</h2>';
        
        $admin_name = "Administrador";
        $admin_username = "admin";
        $admin_email = "admin@soudigital.com.br";
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Senha padrão - deve ser alterada após o primeiro login
        
        $sql_insert_admin = "INSERT INTO {$tabela_a_usar} (name, username, email, password, is_admin) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql_insert_admin);
        
        if ($stmt) {
            $stmt->bind_param("ssss", $admin_name, $admin_username, $admin_email, $admin_password);
            if ($stmt->execute()) {
                echo '<div style="color:green">✓ Usuário administrador criado com sucesso</div>';
                echo '<div style="background-color:#ffe; padding:10px; border:1px solid #dda; margin-top:10px;">';
                echo '<strong>Credenciais do administrador:</strong><br>';
                echo 'Usuário: admin<br>';
                echo 'Senha: admin123<br>';
                echo '<span style="color:red">IMPORTANTE: Altere esta senha após o primeiro login!</span>';
                echo '</div>';
            } else {
                echo '<div style="color:red">✗ Erro ao criar usuário administrador: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            echo '<div style="color:red">✗ Erro ao preparar consulta: ' . $conn->error . '</div>';
        }
    }
}

// Verificar estrutura da tabela
echo '<h2>Verificando estrutura da tabela ' . $tabela_a_usar . ':</h2>';
$result = $conn->query("DESCRIBE {$tabela_a_usar}");

if ($result && $result->num_rows > 0) {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>';
    
    $campos = [];
    while ($row = $result->fetch_assoc()) {
        $campos[$row['Field']] = $row;
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
    
    // Verificar campos necessários
    $campos_necessarios = ['id', 'name', 'username', 'email', 'password', 'is_admin'];
    $campos_faltando = [];
    
    foreach ($campos_necessarios as $campo) {
        if (!isset($campos[$campo])) {
            $campos_faltando[] = $campo;
        }
    }
    
    if (count($campos_faltando) > 0) {
        echo '<div style="color:orange; margin-top:10px;">! Campos necessários faltando: ' . implode(', ', $campos_faltando) . '</div>';
        
        // Adicionar campos faltantes
        echo '<h3>Adicionando campos faltantes:</h3>';
        
        foreach ($campos_faltando as $campo) {
            $sql_add_campo = "";
            
            switch ($campo) {
                case 'id':
                    $sql_add_campo = "ALTER TABLE {$tabela_a_usar} ADD id INT(11) AUTO_INCREMENT PRIMARY KEY";
                    break;
                case 'name':
                    $sql_add_campo = "ALTER TABLE {$tabela_a_usar} ADD name VARCHAR(100) NOT NULL";
                    break;
                case 'username':
                    $sql_add_campo = "ALTER TABLE {$tabela_a_usar} ADD username VARCHAR(50) NOT NULL UNIQUE";
                    break;
                case 'email':
                    $sql_add_campo = "ALTER TABLE {$tabela_a_usar} ADD email VARCHAR(100) NOT NULL UNIQUE";
                    break;
                case 'password':
                    $sql_add_campo = "ALTER TABLE {$tabela_a_usar} ADD password VARCHAR(255) NOT NULL";
                    break;
                case 'is_admin':
                    $sql_add_campo = "ALTER TABLE {$tabela_a_usar} ADD is_admin TINYINT(1) DEFAULT 0";
                    break;
            }
            
            if (!empty($sql_add_campo)) {
                if ($conn->query($sql_add_campo) === TRUE) {
                    echo '<div style="color:green">✓ Campo "' . $campo . '" adicionado com sucesso</div>';
                } else {
                    echo '<div style="color:red">✗ Erro ao adicionar campo "' . $campo . '": ' . $conn->error . '</div>';
                }
            }
        }
    } else {
        echo '<div style="color:green; margin-top:10px;">✓ Todos os campos necessários estão presentes</div>';
    }
}

// Fechar conexão
$conn->close();

echo '<div style="margin-top:20px; padding:15px; background-color:#dfd; border:1px solid #ada;">';
echo '<h2>Configuração concluída!</h2>';
echo '<p>Agora você pode <a href="page/autenticacao.php">fazer login</a> no sistema.</p>';
echo '</div>';
?> 