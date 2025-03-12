<?php
session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes

// Habilitar exibição de erros (apenas para testes)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Criar diretório de log se não existir
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Configurar log
$log_file = $log_dir . '/upload_debug.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($log_file, "\n[$timestamp] ==== PROCESSANDO UPLOAD SIMPLES ====\n", FILE_APPEND);

// Registrar parâmetros do PHP
file_put_contents($log_file, "[$timestamp] upload_max_filesize: " . ini_get('upload_max_filesize') . "\n", FILE_APPEND);
file_put_contents($log_file, "[$timestamp] post_max_size: " . ini_get('post_max_size') . "\n", FILE_APPEND);

// Verificar se um arquivo foi enviado
if (!isset($_FILES['arquivo'])) {
    file_put_contents($log_file, "[$timestamp] Array FILES não contém chave 'arquivo'\n", FILE_APPEND);
    header("Location: teste-upload-arquivo.php?error=Nenhum arquivo enviado (FILES não contém a chave 'arquivo')");
    exit;
}

$upload = $_FILES['arquivo'];
file_put_contents($log_file, "[$timestamp] Informações do upload: " . print_r($upload, true) . "\n", FILE_APPEND);

// Verificar erros de upload
if ($upload['error'] != UPLOAD_ERR_OK) {
    $error_message = 'Erro no upload: ';
    
    switch ($upload['error']) {
        case UPLOAD_ERR_INI_SIZE:
            $error_message .= 'O arquivo excede o tamanho máximo permitido pelo PHP (upload_max_filesize)';
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
    header("Location: teste-upload-arquivo.php?error=" . urlencode($error_message));
    exit;
}

// Verificar se arquivo existe e pode ser lido
if (!file_exists($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
    $error_message = "Arquivo temporário não existe ou não é um upload válido";
    file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
    header("Location: teste-upload-arquivo.php?error=" . urlencode($error_message));
    exit;
}

// Criar diretório de uploads se não existir
$upload_dir = __DIR__ . '/uploads/test_files';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $error_message = "Não foi possível criar o diretório de uploads";
        file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
        header("Location: teste-upload-arquivo.php?error=" . urlencode($error_message));
        exit;
    }
}

// Gerar nome único para o arquivo
$filename = uniqid() . '_' . basename($upload['name']);
$destination = $upload_dir . '/' . $filename;

file_put_contents($log_file, "[$timestamp] Tentando mover para: $destination\n", FILE_APPEND);

// Tentar mover o arquivo
if (move_uploaded_file($upload['tmp_name'], $destination)) {
    // Sucesso
    file_put_contents($log_file, "[$timestamp] Upload bem-sucedido: $destination\n", FILE_APPEND);
    
    // Definir permissões
    chmod($destination, 0644);
    
    // Redirecionar para a página de sucesso
    header("Location: teste-upload-arquivo.php?status=success&path=" . urlencode($destination));
    exit;
} else {
    // Falha
    $error_message = "Falha ao mover o arquivo enviado";
    file_put_contents($log_file, "[$timestamp] $error_message\n", FILE_APPEND);
    
    // Verificar permissões do diretório
    $perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
    $is_writable = is_writable($upload_dir) ? "Sim" : "Não";
    file_put_contents($log_file, "[$timestamp] Permissões do diretório: $perms, Gravável: $is_writable\n", FILE_APPEND);
    
    header("Location: teste-upload-arquivo.php?error=" . urlencode($error_message));
    exit;
}
?> 