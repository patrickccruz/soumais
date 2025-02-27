<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(['error' => 'Acesso não autorizado']));
}

header('Content-Type: application/json');

// Verifica se um arquivo foi enviado
if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
    http_response_code(400);
    die(json_encode(['error' => 'Nenhum arquivo enviado ou erro no upload']));
}

$file = $_FILES['file'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

// Verifica o tipo do arquivo
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    die(json_encode(['error' => 'Tipo de arquivo não permitido']));
}

// Define o diretório de upload
$upload_dir = '../uploads/blog/editor/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Gera um nome único para o arquivo
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move o arquivo para o diretório de upload
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Retorna a URL do arquivo no formato esperado pelo TinyMCE
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    echo json_encode([
        'location' => $site_url . '/uploads/blog/editor/' . $filename
    ]);
} else {
    http_response_code(500);
    die(json_encode(['error' => 'Erro ao salvar o arquivo']));
} 