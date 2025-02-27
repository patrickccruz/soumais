<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        $name = $_POST['name'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $name, $email, $userId);
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "Perfil atualizado com sucesso.";
            } else {
                $_SESSION['update_success'] = "Erro ao atualizar o perfil: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['update_success'] = "Erro na preparação da consulta: " . $conn->error;
        }
    } else {
        $_SESSION['update_success'] = "Usuário não está logado corretamente.";
    }
    
    header("Location: meu-perfil.php");
    exit;
}
?>
