<?php
session_start();
include '../db.php';

$userId = $_SESSION['user']['id'];

// Remove a imagem do perfil no banco de dados
$stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Opcional: Remover o arquivo do servidor
// $user = $stmt->fetch_assoc();
// $filePath = '../uploads/' . $user['profile_image'];
// if (file_exists($filePath)) {
//     unlink($filePath);
// }

$_SESSION['update_success'] = "Imagem de perfil removida com sucesso.";
header("Location: meu-perfil.php");
exit;
?>
