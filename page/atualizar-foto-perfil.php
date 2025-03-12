<?php
session_start();
require_once '../db.php';
require_once '../includes/upload_functions.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: autenticacao.php');
    exit;
}

// Configurar diretório de logs
$log_dir = dirname(__DIR__) . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

$log_file = $log_dir . '/perfil_upload.log';
$timestamp = date('Y-m-d H:i:s');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Nenhum arquivo enviado");
        }

        // Tipos de imagem permitidos
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
        
        // Tamanho máximo (5MB)
        $max_size = 5 * 1024 * 1024;
        
        // Diretório para salvar as imagens
        $user_id = $_SESSION['user']['id'];
        $upload_dir = dirname(__DIR__) . '/uploads/profiles/' . $user_id;
        
        // Registrar início do processo
        file_put_contents($log_file, "\n[$timestamp] === INÍCIO DO UPLOAD DE FOTO DE PERFIL (User ID: $user_id) ===\n", FILE_APPEND);
        
        // Remover foto antiga se existir
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($oldImage = $result->fetch_assoc()) {
            if (!empty($oldImage['profile_image'])) {
                file_put_contents($log_file, "[$timestamp] Removendo imagem antiga: " . $oldImage['profile_image'] . "\n", FILE_APPEND);
                $old_path = dirname(__DIR__) . '/' . $oldImage['profile_image'];
                if (file_exists($old_path)) {
                    unlink($old_path);
                    file_put_contents($log_file, "[$timestamp] Imagem antiga removida com sucesso\n", FILE_APPEND);
                } else {
                    file_put_contents($log_file, "[$timestamp] Arquivo antigo não encontrado: $old_path\n", FILE_APPEND);
                }
            }
        }

        // Processar upload usando a função padronizada
        $result = process_file_upload(
            $_FILES['profile_image'],
            $upload_dir . '/profile_image',
            $allowed_types,
            $max_size,
            'profile_',
            $log_file
        );
        
        if ($result['success']) {
            // Atualizar banco de dados com o novo caminho
            $relativePath = str_replace(dirname(__DIR__) . '/', '', $result['path']);
            file_put_contents($log_file, "[$timestamp] Caminho relativo para banco: $relativePath\n", FILE_APPEND);
            
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $relativePath, $user_id);
            
            if (!$stmt->execute()) {
                // Se falhar, remove o arquivo que acabou de ser enviado
                file_put_contents($log_file, "[$timestamp] ERRO ao atualizar banco: " . $stmt->error . "\n", FILE_APPEND);
                if (file_exists($result['path'])) {
                    unlink($result['path']);
                    file_put_contents($log_file, "[$timestamp] Arquivo removido após falha no banco\n", FILE_APPEND);
                }
                throw new Exception("Erro ao atualizar banco de dados: " . $stmt->error);
            }
            
            file_put_contents($log_file, "[$timestamp] Banco de dados atualizado com sucesso\n", FILE_APPEND);
            $_SESSION['update_success'] = "Imagem de perfil atualizada com sucesso";
        } else {
            file_put_contents($log_file, "[$timestamp] Falha no upload: " . $result['message'] . "\n", FILE_APPEND);
            throw new Exception($result['message']);
        }
    }
} catch (Exception $e) {
    file_put_contents($log_file, "[$timestamp] EXCEÇÃO: " . $e->getMessage() . "\n", FILE_APPEND);
    $_SESSION['update_error'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    file_put_contents($log_file, "[$timestamp] === FIM DO PROCESSO DE UPLOAD ===\n", FILE_APPEND);
}

header("Location: meu-perfil.php");
exit;
?>
