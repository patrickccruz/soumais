<?php
// Modificar limites em tempo de execução (não funciona para upload_max_filesize)
ini_set('post_max_size', '22M');
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes

// Criar diretório de log se não existir
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Configurar log
$log_file = $log_dir . '/upload_grande.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($log_file, "\n[$timestamp] ==== PROCESSANDO UPLOAD GRANDE ====\n", FILE_APPEND);

// Imprimir configurações em uso
file_put_contents($log_file, "[$timestamp] upload_max_filesize: " . ini_get('upload_max_filesize') . "\n", FILE_APPEND);
file_put_contents($log_file, "[$timestamp] post_max_size: " . ini_get('post_max_size') . "\n", FILE_APPEND);
file_put_contents($log_file, "[$timestamp] memory_limit: " . ini_get('memory_limit') . "\n", FILE_APPEND);

// Verificar se um arquivo foi enviado
if (empty($_FILES)) {
    file_put_contents($log_file, "[$timestamp] ERRO: Array FILES está vazio\n", FILE_APPEND);
    header("Location: teste-upload-grande.php?error=Nenhum arquivo foi enviado. Verifique o tamanho máximo permitido.");
    exit;
}

if (!isset($_FILES['arquivo'])) {
    file_put_contents($log_file, "[$timestamp] ERRO: Chave 'arquivo' não encontrada em FILES\n", FILE_APPEND);
    header("Location: teste-upload-grande.php?error=Nenhum arquivo foi enviado ou o nome do campo não corresponde ao esperado.");
    exit;
}

$upload = $_FILES['arquivo'];
file_put_contents($log_file, "[$timestamp] Informações do upload: " . print_r($upload, true) . "\n", FILE_APPEND);

// Verificar erros específicos de upload
if ($upload['error'] != UPLOAD_ERR_OK) {
    $error_message = 'Erro no upload: ';
    
    switch ($upload['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido pelo PHP (' . ini_get('upload_max_filesize') . ')';
            file_put_contents($log_file, "[$timestamp] ERRO: Arquivo excede tamanho máximo em php.ini\n", FILE_APPEND);
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido pelo formulário (MAX_FILE_SIZE)';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message .= 'O arquivo foi parcialmente enviado';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message .= 'Nenhum arquivo foi enviado';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_message .= 'Diretório temporário não encontrado';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_message .= 'Falha ao gravar o arquivo no disco';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_message .= 'Upload interrompido por uma extensão PHP';
            break;
        default:
            $error_message .= "Código de erro desconhecido: {$upload['error']}";
    }
    
    file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
    header("Location: teste-upload-grande.php?error=" . urlencode($error_message));
    exit;
}

// Verificar tipo do arquivo
$mime_type = '';
if (function_exists('mime_content_type')) {
    $mime_type = mime_content_type($upload['tmp_name']);
} elseif (extension_loaded('fileinfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($upload['tmp_name']);
} else {
    // Fallback para extensão do arquivo
    $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        $mime_type = 'application/pdf';
    }
}

file_put_contents($log_file, "[$timestamp] MIME detectado: $mime_type\n", FILE_APPEND);

$allowed_types = ['application/pdf', 'application/x-pdf', 'application/acrobat', 'application/vnd.pdf', 'text/pdf', 'text/x-pdf'];
if (!in_array($mime_type, $allowed_types) && !empty($mime_type)) {
    $error_message = "Tipo de arquivo não permitido: $mime_type. Use apenas PDF.";
    file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
    header("Location: teste-upload-grande.php?error=" . urlencode($error_message));
    exit;
}

// Verificar tamanho do arquivo (limite manual de 20MB)
$max_size = 20 * 1024 * 1024; // 20MB em bytes
if ($upload['size'] > $max_size) {
    $error_message = "O arquivo é muito grande (" . round($upload['size'] / (1024 * 1024), 2) . "MB). O tamanho máximo é 20MB.";
    file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
    header("Location: teste-upload-grande.php?error=" . urlencode($error_message));
    exit;
}

// Tudo OK, prosseguir com o upload
try {
    // Criar diretório de destino
    $upload_dir = __DIR__ . '/uploads/grandes';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Não foi possível criar o diretório de destino");
        }
        file_put_contents($log_file, "[$timestamp] Diretório de destino criado: $upload_dir\n", FILE_APPEND);
    }
    
    // Gerar nome de arquivo único
    $filename = 'pdf_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $upload['name']);
    $destination = $upload_dir . '/' . $filename;
    
    file_put_contents($log_file, "[$timestamp] Tentando mover arquivo para: $destination\n", FILE_APPEND);
    
    // Mover o arquivo
    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
        throw new Exception("Falha ao mover o arquivo enviado");
    }
    
    // Definir permissões corretas
    chmod($destination, 0644);
    
    // Gravação bem-sucedida
    file_put_contents($log_file, "[$timestamp] Upload bem-sucedido: $destination\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tamanho do arquivo: " . round($upload['size'] / (1024 * 1024), 2) . "MB\n", FILE_APPEND);
    
    // Redirecionar de volta com mensagem de sucesso
    header("Location: teste-upload-grande.php?status=success&path=" . urlencode('uploads/grandes/' . $filename));
    exit;
} catch (Exception $e) {
    $error_message = $e->getMessage();
    file_put_contents($log_file, "[$timestamp] ERRO: $error_message\n", FILE_APPEND);
    header("Location: teste-upload-grande.php?error=" . urlencode($error_message));
    exit;
}
?> 