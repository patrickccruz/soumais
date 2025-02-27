<?php
session_start();
require_once __DIR__ . '/../db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header('Location: ../index.php');
    exit;
}

// ... existing code ...



