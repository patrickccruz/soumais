<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir conexão com o banco
require_once 'db.php';

session_start();
include 'db_connection.php'; // Inclua a conexão com o banco de dados

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verifique as credenciais do usuário no banco de dados
    $stmt = $conn->prepare("SELECT id, name, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verifique a senha
        if (password_verify($password, $user['password'])) {
            // Armazene os dados do usuário na sessão
            $_SESSION['loggedin'] = true;
            $_SESSION['user'] = [
                'name' => $user['name'],
                'username' => $user['username']
            ];
            header("Location: ../index.php");
            exit;
        } else {
            echo "Senha incorreta.";
        }
    } else {
        echo "Usuário não encontrado.";
    }
}
?>
