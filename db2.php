<?php
// Arquivo DB2.php - Teste alternativo de conexão
// Criado: 2025-03-13

// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Credenciais
$servername = "localhost";
$username = "sou_digital";
$password = "SuaSenhaSegura123!";
$dbname = "sou_digital";

// Conexão simples
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset
$conn->set_charset("utf8mb4");

// Variável de teste
$conexao_ok = true;
?> 