<?php
session_start();
require_once '../db.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: autenticacao.php');
    exit;
}

// Verificação de permissão de administrador
if (!isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Verificação de CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token de segurança inválido";
    header('Location: gerenciar-usuarios.php');
    exit;
}

try {
    // Validação e sanitização das entradas
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Validações básicas
    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        throw new Exception("Todos os campos obrigatórios devem ser preenchidos");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }

    if (strlen($password) < 8) {
        throw new Exception("A senha deve ter no mínimo 8 caracteres");
    }

    // Verificar se o username ou email já existem
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Nome de usuário ou email já cadastrado");
    }
    $stmt->close();

    // Processar imagem de perfil
    $profile_image = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Tipo de arquivo não permitido. Use apenas imagens (JPG, PNG, GIF)");
        }

        $upload_dir = '../uploads/profile/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            throw new Exception("Erro ao fazer upload da imagem");
        }

        $profile_image = 'uploads/profile/' . $new_filename;
    }

    // Hash da senha
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Inserir novo usuário
    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, profile_image, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $name, $email, $username, $hashed_password, $profile_image, $is_admin);
    
    if (!$stmt->execute()) {
        // Se houver erro e uma imagem foi enviada, remove a imagem
        if ($profile_image && file_exists('../' . $profile_image)) {
            unlink('../' . $profile_image);
        }
        throw new Exception("Erro ao criar usuário: " . $stmt->error);
    }

    // Log da criação
    error_log("Novo usuário criado: {$username} por " . $_SESSION['user']['username']);
    
    $_SESSION['success'] = "Usuário criado com sucesso";
    header('Location: gerenciar-usuarios.php');
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: gerenciar-usuarios.php');
    exit;
}
?> 