<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(['error' => 'Acesso não autorizado']));
}

header('Content-Type: application/json');

// Função para registrar logs
function logUpload($message) {
    $log_file = __DIR__ . '/upload_error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Verifica se um arquivo foi enviado
if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
    $error = 'Nenhum arquivo enviado ou erro no upload: ';
    if (isset($_FILES['file'])) {
        $error .= $_FILES['file']['error'];
    } else {
        $error .= 'FILES não definido';
    }
    logUpload($error);
    http_response_code(400);
    die(json_encode(['error' => $error]));
}

$file = $_FILES['file'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

// Verifica o tipo do arquivo
if (!in_array($file['type'], $allowed_types)) {
    $error = 'Tipo de arquivo não permitido: ' . $file['type'];
    logUpload($error);
    http_response_code(400);
    die(json_encode(['error' => $error]));
}

// Define o diretório de upload
$upload_dir = __DIR__ . '/uploads/blog/editor/';
if (!is_dir($upload_dir)) {
    try {
        if (!mkdir($upload_dir, 0777, true)) {
            $error = 'Erro ao criar diretório: ' . $upload_dir;
            logUpload($error);
            http_response_code(500);
            die(json_encode(['error' => $error]));
        }
        logUpload('Diretório criado com sucesso: ' . $upload_dir);
    } catch (Exception $e) {
        $error = 'Exceção ao criar diretório: ' . $e->getMessage();
        logUpload($error);
        http_response_code(500);
        die(json_encode(['error' => $error]));
    }
}

// Gera um nome único para o arquivo
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move o arquivo para o diretório de upload
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    logUpload('Arquivo enviado com sucesso: ' . $filepath);
    
    // Retorna a URL do arquivo no formato esperado pelo TinyMCE
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $url_path = '/soudigital/uploads/blog/editor/' . $filename;
    logUpload('URL gerada: ' . $site_url . $url_path);
    
    echo json_encode([
        'location' => $site_url . $url_path
    ]);
} else {
    $error = 'Erro ao salvar o arquivo. Tmp: ' . $file['tmp_name'] . ', Destino: ' . $filepath;
    logUpload($error);
    http_response_code(500);
    die(json_encode(['error' => $error]));
} 