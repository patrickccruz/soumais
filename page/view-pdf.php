<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

// Definição para indicar que estamos em uma página dentro da pasta 'page'
$is_page = true;

if (isset($_GET['file'])) {
    $file = '../' . $_GET['file'];
    
    // Verificar se o arquivo existe e é um PDF
    if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
        // Verificar se o arquivo está dentro do diretório de uploads
        $realpath = realpath($file);
        $uploads_dir = realpath('../uploads');
        
        if (strpos($realpath, $uploads_dir) === 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.basename($file).'"');
            readfile($file);
            exit;
        } else {
            echo "Acesso negado: O arquivo não está no diretório permitido.";
        }
    } else {
        echo "Arquivo não encontrado ou não é um PDF.";
    }
} else {
    echo "Nenhum arquivo especificado.";
}
?> 