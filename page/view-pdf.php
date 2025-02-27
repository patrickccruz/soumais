<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

if (isset($_GET['file'])) {
    $file = '../' . $_GET['file'];
    
    if (file_exists($file)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.basename($file).'"');
        readfile($file);
        exit;
    } else {
        echo "Arquivo não encontrado.";
    }
} else {
    echo "Nenhum arquivo especificado.";
}
?> 