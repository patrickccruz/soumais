<?php
require_once __DIR__ . '/../includes/upload_functions.php';

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'sou_digital');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function logMigration($message) {
    echo date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
    flush();
}

function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0777, true)) {
            throw new Exception("Não foi possível criar o diretório: " . $path);
        }
        logMigration("Diretório criado: " . $path);
    }
}

function moveFile($old_path, $new_path) {
    ensureDirectoryExists(dirname($new_path));
    
    if (!file_exists($old_path)) {
        logMigration("Arquivo não encontrado: " . $old_path);
        return false;
    }
    
    if (copy($old_path, $new_path)) {
        if (unlink($old_path)) {
            logMigration("Arquivo movido: " . $old_path . " -> " . $new_path);
            return true;
        } else {
            logMigration("Arquivo copiado mas não foi possível remover o original: " . $old_path);
            return true;
        }
    } else {
        logMigration("Erro ao mover arquivo: " . $old_path);
        return false;
    }
}

try {
    // Criar diretórios base
    $base_dirs = [
        __DIR__ . '/../uploads/users',
        __DIR__ . '/../uploads/blog',
        __DIR__ . '/../uploads/reimbursements',
        __DIR__ . '/../uploads/orphaned'
    ];
    
    foreach ($base_dirs as $dir) {
        ensureDirectoryExists($dir);
    }

    // Migrar fotos de perfil
    logMigration("\nIniciando migração de fotos de perfil...");
    $stmt = $conn->prepare("SELECT id, profile_image FROM users WHERE profile_image IS NOT NULL AND profile_image != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;

    while ($user = $result->fetch_assoc()) {
        $old_path = __DIR__ . '/../' . $user['profile_image'];
        $upload_path = get_upload_path('profile', ['user_id' => $user['id']]);
        $new_filename = 'profile_' . basename($user['profile_image']);
        $new_path = $upload_path . '/' . $new_filename;
        
        if (moveFile($old_path, $new_path)) {
            $relative_path = str_replace(__DIR__ . '/../', '', $new_path);
            $update = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $update->bind_param("si", $relative_path, $user['id']);
            $update->execute();
            $count++;
        }
    }
    logMigration("Total de fotos de perfil migradas: " . $count);

    // Migrar imagens do blog
    logMigration("\nIniciando migração de imagens do blog...");
    $stmt = $conn->prepare("SELECT id, imagem_capa FROM blog_posts WHERE imagem_capa IS NOT NULL AND imagem_capa != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;

    while ($post = $result->fetch_assoc()) {
        $old_path = __DIR__ . '/../' . $post['imagem_capa'];
        $upload_path = get_upload_path('blog', ['post_id' => $post['id']]);
        $new_filename = 'cover_' . basename($post['imagem_capa']);
        $new_path = $upload_path . '/' . $new_filename;
        
        if (moveFile($old_path, $new_path)) {
            $relative_path = str_replace(__DIR__ . '/../', '', $new_path);
            $update = $conn->prepare("UPDATE blog_posts SET imagem_capa = ? WHERE id = ?");
            $update->bind_param("si", $relative_path, $post['id']);
            $update->execute();
            $count++;
        }
    }
    logMigration("Total de imagens do blog migradas: " . $count);

    // Migrar arquivos de reembolso
    logMigration("\nIniciando migração de arquivos de reembolso...");
    $stmt = $conn->prepare("SELECT id, arquivo_path FROM reembolsos WHERE arquivo_path IS NOT NULL AND arquivo_path != ''");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;

    while ($reembolso = $result->fetch_assoc()) {
        $arquivos = explode(',', $reembolso['arquivo_path']);
        $novos_arquivos = array();

        foreach ($arquivos as $arquivo) {
            $old_path = __DIR__ . '/../' . trim($arquivo);
            $upload_path = get_upload_path('reimbursement', ['reimbursement_id' => $reembolso['id']]);
            $new_filename = 'reembolso_' . basename($arquivo);
            $new_path = $upload_path . '/' . $new_filename;
            
            if (moveFile($old_path, $new_path)) {
                $novos_arquivos[] = str_replace(__DIR__ . '/../', '', $new_path);
                $count++;
            }
        }

        if (!empty($novos_arquivos)) {
            $novos_caminhos = implode(',', $novos_arquivos);
            $update = $conn->prepare("UPDATE reembolsos SET arquivo_path = ? WHERE id = ?");
            $update->bind_param("si", $novos_caminhos, $reembolso['id']);
            $update->execute();
        }
    }
    logMigration("Total de arquivos de reembolso migrados: " . $count);

    // Mover arquivos órfãos
    logMigration("\nMovendo arquivos órfãos...");
    $uploads_dir = __DIR__ . '/../uploads';
    $orphaned_dir = $uploads_dir . '/orphaned';
    $count = 0;

    $files = scandir($uploads_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || is_dir($uploads_dir . '/' . $file)) {
            continue;
        }

        // Ignorar arquivos especiais
        if ($file === '.htaccess' || $file === 'upload_log.txt') {
            continue;
        }

        $old_path = $uploads_dir . '/' . $file;
        $new_path = $orphaned_dir . '/' . $file;
        
        if (moveFile($old_path, $new_path)) {
            $count++;
        }
    }
    logMigration("Total de arquivos órfãos movidos: " . $count);

    // Remover diretórios vazios antigos
    logMigration("\nLimpando diretórios vazios...");
    $dirs_to_check = [
        __DIR__ . '/../uploads/blog',
        __DIR__ . '/../uploads/profile',
        __DIR__ . '/../uploads/editor'
    ];
    
    foreach ($dirs_to_check as $dir) {
        if (is_dir($dir) && count(scandir($dir)) <= 2) {
            if (rmdir($dir)) {
                logMigration("Diretório removido: " . $dir);
            }
        }
    }

    logMigration("\nMigração concluída com sucesso!");

} catch (Exception $e) {
    logMigration("ERRO: " . $e->getMessage());
} finally {
    $conn->close();
} 