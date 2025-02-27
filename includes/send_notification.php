<?php
require_once __DIR__ . '/../db.php';

function enviarNotificacao($userId, $tipo, $titulo, $mensagem, $link = null) {
    // Configurar timezone do MySQL
    $conn->query("SET time_zone = '-03:00'");

    // Garantir que o link comece com 'page/' se fornecido
    if ($link !== null && !empty($link)) {
        // Remover 'page/' do início se existir
        $link = preg_replace('/^page\//', '', $link);
        // Adicionar 'page/' no início
        $link = 'page/' . $link;
    }

    $sql = "INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link, data_criacao, lida) VALUES (?, ?, ?, ?, ?, NOW(), FALSE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $tipo, $titulo, $mensagem, $link);
    $success = $stmt->execute();

    $stmt->close();
    $conn->close();

    return $success;
}

// Exemplo de uso:
// enviarNotificacao(
//     1, // ID do usuário
//     'aprovacao', // Tipo: aprovacao, rejeicao, comentario, sistema
//     'Script Aprovado!', // Título
//     'Seu script foi aprovado com sucesso.', // Mensagem
//     'gerenciar-posts.php?id=123' // Link opcional (não precisa incluir 'page/')
// ); 