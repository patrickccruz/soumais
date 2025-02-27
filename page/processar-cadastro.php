<?php
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
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validações
        if (empty($name) || empty($username) || empty($password)) {
            throw new Exception("Todos os campos são obrigatórios");
        }
        
        if ($password !== $confirm_password) {
            throw new Exception("As senhas não coincidem");
        }
        
        // Verificar se usuário já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Este nome de usuário já está em uso");
        }
        
        // Hash da senha
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Inserir novo usuário
        $stmt = $conn->prepare("INSERT INTO usuarios (name, username, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $name, $username, $password_hash);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Conta criada com sucesso! Faça login para continuar.";
            header("Location: ../login.php");
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
