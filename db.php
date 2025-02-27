<?php
$servername = "localhost";
$username = "sou_digital";  // Usuário do banco de dados
$password = "SuaSenhaSegura123!";  // Senha do banco de dados
$dbname = "sou_digital";  // Nome do banco de dados

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>