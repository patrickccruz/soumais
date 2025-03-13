<?php
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    $_SESSION['error'] = "ID do post inválido.";
    header("Location: ../index.php");
    exit;
}

$post_id = (int)$_POST['post_id'];

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'sou_digital');
if ($conn->connect_error) {
    $_SESSION['error'] = "Erro de conexão com o banco de dados.";
    header("Location: ../index.php");
    exit;
}

// Buscar informações do post (para pegar o caminho da imagem)
$stmt = $conn->prepare("SELECT imagem_capa FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

// Excluir registros relacionados primeiro (devido às chaves estrangeiras)
$conn->query("DELETE FROM blog_reacoes WHERE post_id = $post_id");
$conn->query("DELETE FROM blog_comentarios WHERE post_id = $post_id");
$conn->query("DELETE FROM blog_links WHERE post_id = $post_id");

// Excluir o post
$stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    // Se houver uma imagem, tentar excluí-la
    if ($post && $post['imagem_capa']) {
        $imagem_path = "../" . $post['imagem_capa'];
        if (file_exists($imagem_path)) {
            unlink($imagem_path);
        }
    }
    $_SESSION['success'] = "Post excluído com sucesso.";
} else {
    $_SESSION['error'] = "Erro ao excluir o post.";
}

$stmt->close();
$conn->close();

header("Location: ../index.php");
exit;
?> 