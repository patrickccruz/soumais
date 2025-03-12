<?php
/**
 * Script para restaurar permissões do banco de dados
 * CUIDADO: Este script deve ser executado apenas pelo administrador!
 */

// Verifica se está sendo executado via linha de comando
if (PHP_SAPI !== 'cli') {
    die("Este script deve ser executado via linha de comando por segurança. 
    Use: php restaurar-permissoes.php");
}

echo "==== Script de Restauração de Permissões do Banco ====\n";
echo "ATENÇÃO: Este script recriará os usuários do banco de dados.\n";
echo "Prosseguir? (S/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) != 's') {
    echo "Operação cancelada.\n";
    exit;
}

// Credenciais do root (necessário para fazer alterações nos usuários)
echo "Digite a senha atual do usuário root do MySQL: ";
$root_password = trim(fgets($handle));

// Credenciais para o usuário sou_digital
$sou_digital_password = "SuaSenhaSegura123!";
echo "Confirma a senha para o usuário sou_digital? ($sou_digital_password) (S/n): ";
$line = trim(fgets($handle));
if (strtolower($line) != 's') {
    echo "Digite a nova senha para o usuário sou_digital: ";
    $sou_digital_password = trim(fgets($handle));
}

try {
    // Conexão com o MySQL usando root
    $conn = new mysqli('localhost', 'root', $root_password);
    
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com root: " . $conn->connect_error);
    }
    
    echo "Conectado ao MySQL como root. Criando/restaurando usuários...\n";
    
    // Criação do banco se não existir
    $conn->query("CREATE DATABASE IF NOT EXISTS sou_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "- Banco de dados 'sou_digital' verificado/criado.\n";
    
    // Verificar se o usuário sou_digital existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM mysql.user WHERE user = ? AND host = ?");
    $stmt->bind_param("ss", $user, $host);
    $user = 'sou_digital';
    $host = 'localhost';
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    // Se o usuário existe, remover para recriar
    if ($count > 0) {
        $conn->query("DROP USER 'sou_digital'@'localhost'");
        echo "- Usuário 'sou_digital' removido para recriação.\n";
    }
    
    // Criar usuário sou_digital
    $conn->query("CREATE USER 'sou_digital'@'localhost' IDENTIFIED BY '$sou_digital_password'");
    echo "- Usuário 'sou_digital' criado.\n";
    
    // Conceder permissões ao usuário sou_digital
    $conn->query("GRANT ALL PRIVILEGES ON sou_digital.* TO 'sou_digital'@'localhost'");
    $conn->query("FLUSH PRIVILEGES");
    echo "- Permissões concedidas ao usuário 'sou_digital'.\n";
    
    // Verificar o usuário root
    echo "Verificando permissões do usuário root...\n";
    
    // Tentar conexão com root sem senha
    $test = @new mysqli('localhost', 'root', '');
    if (!$test->connect_error) {
        echo "- O usuário root pode se conectar sem senha! Isso é um risco de segurança.\n";
        echo "  Recomendamos definir uma senha para o usuário root.\n";
        echo "  Definir senha para root agora? (S/n): ";
        $line = trim(fgets($handle));
        if (strtolower($line) == 's') {
            echo "Digite a nova senha para o usuário root: ";
            $new_root_password = trim(fgets($handle));
            $conn->query("ALTER USER 'root'@'localhost' IDENTIFIED BY '$new_root_password'");
            $conn->query("FLUSH PRIVILEGES");
            echo "- Senha do root alterada com sucesso.\n";
        }
    } else {
        echo "- O usuário root já tem senha definida. Bom para segurança!\n";
    }
    
    $conn->close();
    echo "==== Processo concluído com sucesso! ====\n";
    echo "As credenciais do banco foram configuradas:\n";
    echo "- Usuário: sou_digital\n";
    echo "- Senha: $sou_digital_password\n";
    echo "- Banco: sou_digital\n";
    echo "\nVerifique se o arquivo db.php contém as credenciais corretas.\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?> 