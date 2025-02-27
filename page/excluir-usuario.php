<?php
session_start();
require_once '../db.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: user-login.php');
    exit;
}

// Verificação de permissão de administrador
if (!isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Verificação de CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Método inválido para exclusão";
    header('Location: manage_users.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token de segurança inválido";
    header('Location: manage_users.php');
    exit;
}

try {
    // Validação do ID
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        throw new Exception("ID de usuário inválido");
    }

    // Verificar se não está tentando excluir o próprio usuário
    if ($id === $_SESSION['user']['id']) {
        throw new Exception("Não é possível excluir o próprio usuário");
    }

    if (!isset($conn) || !$conn) {
        throw new Exception("Falha na conexão com o banco de dados");
    }

    // Verificar se o usuário existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao verificar usuário: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Usuário não encontrado");
    }

    $stmt->close();

    // Excluir o usuário
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erro na preparação da exclusão: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao excluir usuário: " . $stmt->error);
    }

    // Log da exclusão
    error_log("Usuário ID {$id} excluído por " . $_SESSION['user']['username']);
    
    $_SESSION['success_message'] = "Usuário excluído com sucesso";

} catch (Exception $e) {
    error_log("Erro na exclusão de usuário: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

header('Location: autenticacao.php');
exit;
?>
