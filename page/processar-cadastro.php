<?php
// Iniciar sessão
session_start();

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir conexão com o banco
require_once __DIR__ . '/../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar e sanitizar dados
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        // Validações
        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            throw new Exception("Todos os campos são obrigatórios");
        }
        
        // Verificar se usuário já existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Este nome de usuário ou email já está em uso");
        }
        
        // Hash da senha
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Inserir novo usuário
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssss", $name, $username, $email, $password_hash);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Conta criada com sucesso! Faça login para continuar.";
            header("Location: autenticacao.php");
            exit;
        } else {
            throw new Exception("Erro ao criar conta: " . $conn->error);
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: cadastro.php");
        exit;
    }
} else {
    header("Location: cadastro.php");
    exit;
}
?>
