<?php
session_start();
require_once '../db.php';
require_once '../includes/upload_functions.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: user-login.php');
    exit;
}

// Configurações de upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Nenhum arquivo enviado");
        }

        $file = $_FILES['profile_image'];
        
        // Validações básicas
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erro no upload: " . $file['error']);
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("Arquivo muito grande. Máximo permitido: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB");
        }

        // Validar tipo de arquivo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!is_allowed_file_type($mimeType, ALLOWED_MIME_TYPES)) {
            throw new Exception("Tipo de arquivo não permitido");
        }

        // Gerar nome único para o arquivo
        $newFileName = generate_unique_filename($file['name'], 'profile_');
        
        // Obter caminho de upload
        $uploadPath = get_upload_path('profile', ['user_id' => $_SESSION['user']['id']]);
        $fullPath = $uploadPath . '/' . $newFileName;
        
        // Remover foto antiga se existir
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user']['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($oldImage = $result->fetch_assoc()) {
            if ($oldImage['profile_image']) {
                remove_file_and_empty_dir('../' . $oldImage['profile_image']);
            }
        }

        // Mover novo arquivo
        if (!move_uploaded_file_safe($file['tmp_name'], $fullPath)) {
            throw new Exception("Falha ao mover arquivo");
        }

        // Atualizar banco de dados
        $relativePath = str_replace('../', '', $fullPath);
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $relativePath, $_SESSION['user']['id']);
        
        if (!$stmt->execute()) {
            // Se falhar, remove o arquivo que acabou de ser enviado
            unlink($fullPath);
            throw new Exception("Erro ao atualizar banco de dados: " . $stmt->error);
        }

        $_SESSION['update_success'] = "Imagem de perfil atualizada com sucesso";
    }
} catch (Exception $e) {
    $_SESSION['update_error'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}

header("Location: meu-perfil.php");
exit;
?>
