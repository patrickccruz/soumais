<?php
session_start();
require_once '../db.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['password_error'] = "Você precisa estar logado para alterar a senha.";
    header('Location: meu-perfil.php');
    exit;
}

// Verificação de CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['password_error'] = "Token de segurança inválido. Por favor, tente novamente.";
        error_log("CSRF token inválido. Recebido: " . ($_POST['csrf_token'] ?? 'não definido') . 
                 ", Esperado: " . ($_SESSION['csrf_token'] ?? 'não definido'));
        header("Location: meu-perfil.php");
        exit;
    }
}

$stmt = null;
try {
    if ($_SERVER["REQUEST_METHOD"] === 'POST') {
        // Validação básica
        if (!isset($_SESSION['user']['id'])) {
            throw new Exception("Usuário não autenticado.");
        }

        // Sanitização e validação das entradas
        $userId = filter_var($_SESSION['user']['id'], FILTER_VALIDATE_INT);
        if ($userId === false) {
            throw new Exception("ID de usuário inválido");
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $renewPassword = $_POST['renew_password'] ?? '';

        // Validações de senha
        if (empty($currentPassword) || empty($newPassword) || empty($renewPassword)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        if (strlen($newPassword) < 8) {
            throw new Exception("A nova senha deve ter pelo menos 8 caracteres.");
        }

        if ($newPassword !== $renewPassword) {
            throw new Exception("As novas senhas não coincidem.");
        }

        // Verificar a senha atual
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar consulta: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $stmt = null;

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception("Senha atual incorreta.");
        }

        // Atualizar a senha
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Erro na preparação da atualização: " . $conn->error);
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt->bind_param("si", $newPasswordHash, $userId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar a senha: " . $stmt->error);
        }

        $stmt->close();
        $stmt = null;

        // Log da alteração
        error_log("Senha alterada com sucesso para o usuário ID: " . $userId);
        
        $_SESSION['password_success'] = "Senha alterada com sucesso.";
    }
} catch (Exception $e) {
    error_log("Erro na alteração de senha: " . $e->getMessage());
    $_SESSION['password_error'] = $e->getMessage();
} finally {
    if ($stmt !== null) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Limpar o token CSRF após o uso
unset($_SESSION['csrf_token']);

header("Location: meu-perfil.php");
exit;
?>

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link" href="../index.php">
        <i class="bi bi-journal-text"></i>
        <span>Gerador Script</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="reembolso.php">
        <i class="bx bx-money"></i>
        <span>Solicitação de reembolso</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="view-reembolsos.php">
        <i class="bx bx-list-ul"></i>
        <span>Visualizar Reembolsos</span>
      </a>
    </li>
  </ul>
</aside>
