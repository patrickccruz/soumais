<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header("Location: autenticacao.php");
    exit;
}

// Definição para indicar que estamos em uma página dentro da pasta 'page'
$is_page = true;

// Conexão com o banco de dados
require_once '../includes/db.php';

// Processar ações de aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    $titulo = $_POST['titulo'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';
    
    // Arrays para links
    $links = $_POST['links'] ?? [];
    $links_descricao = $_POST['links_descricao'] ?? [];

    // Processar nova imagem se enviada
    $imagem_capa = null;
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] == 0) {
        $upload_dir = '../uploads/blog/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['imagem_capa']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['imagem_capa']['tmp_name'], $upload_path)) {
                $imagem_capa = 'uploads/blog/' . $new_filename;
                
                // Deletar imagem antiga
                $stmt = $conn->prepare("SELECT imagem_capa FROM blog_posts WHERE id = ?");
                $stmt->bind_param("i", $post_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($old_image = $result->fetch_assoc()) {
                    if (file_exists('../' . $old_image['imagem_capa'])) {
                        unlink('../' . $old_image['imagem_capa']);
                    }
                }
            }
        }
    }

    if ($post_id && $action) {
        // Atualizar post
        if ($imagem_capa) {
            $sql = "UPDATE blog_posts SET titulo = ?, conteudo = ?, imagem_capa = ?, status = ?, comentario_admin = ?, data_aprovacao = ? WHERE id = ?";
            $status = $action === 'aprovar' ? 'aprovado' : 'rejeitado';
            $data_aprovacao = $action === 'aprovar' ? date('Y-m-d H:i:s') : null;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $titulo, $conteudo, $imagem_capa, $status, $comentario, $data_aprovacao, $post_id);
        } else {
            $sql = "UPDATE blog_posts SET titulo = ?, conteudo = ?, status = ?, comentario_admin = ?, data_aprovacao = ? WHERE id = ?";
            $status = $action === 'aprovar' ? 'aprovado' : 'rejeitado';
            $data_aprovacao = $action === 'aprovar' ? date('Y-m-d H:i:s') : null;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $titulo, $conteudo, $status, $comentario, $data_aprovacao, $post_id);
        }
        
        if ($stmt->execute()) {
            // Buscar informações do autor do post
            $autor_query = $conn->prepare("SELECT user_id FROM blog_posts WHERE id = ?");
            $autor_query->bind_param("i", $post_id);
            $autor_query->execute();
            $autor_result = $autor_query->get_result();
            $autor = $autor_result->fetch_assoc();

            if ($autor) {
                // Criar notificação para o autor
                $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)");
                
                if ($action === 'aprovar') {
                    $tipo = 'aprovacao';
                    $notif_titulo = 'Post Aprovado';
                    $mensagem = "Seu post \"$titulo\" foi aprovado!";
                    $link = null;
                } else {
                    $tipo = 'rejeicao';
                    $notif_titulo = 'Post Rejeitado';
                    $mensagem = "Seu post \"$titulo\" foi rejeitado. Motivo: $comentario";
                    $link = null;
                }
                
                $notif_stmt->bind_param("issss", $autor['user_id'], $tipo, $notif_titulo, $mensagem, $link);
                $notif_stmt->execute();
            }

            // Atualizar links
            // Primeiro, remover links existentes
            $conn->query("DELETE FROM blog_links WHERE post_id = $post_id");
            
            // Depois, inserir novos links
            if (!empty($links)) {
                $stmt = $conn->prepare("INSERT INTO blog_links (post_id, url, descricao) VALUES (?, ?, ?)");
                foreach ($links as $i => $url) {
                    if (!empty($url) && !empty($links_descricao[$i])) {
                        $stmt->bind_param("iss", $post_id, $url, $links_descricao[$i]);
                        $stmt->execute();
                    }
                }
            }

            $_SESSION['success'] = $action === 'aprovar' ? "Post aprovado com sucesso!" : "Post rejeitado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao atualizar o post: " . $stmt->error;
        }
        
        header("Location: gerenciar-posts.php");
        exit;
    }
}

// Buscar posts pendentes
$sql = "SELECT p.*, u.name as author_name, u.email as author_email 
        FROM blog_posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'pendente'
        ORDER BY p.data_criacao DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $posts = $result->fetch_all(MYSQLI_ASSOC);
}

// Função para buscar links de um post
function getPostLinks($conn, $post_id) {
    $links = [];
    $stmt = $conn->prepare("SELECT url, descricao FROM blog_links WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($link = $result->fetch_assoc()) {
        $links[] = $link;
    }
    return $links;
}

// Função para formatar data
function formatarData($data) {
    if (!$data) return '';
    return date('d/m/Y H:i', strtotime($data));
}

include_once '../includes/header.php'; 
include_once '../includes/sidebar.php';
?>

<!-- CSS específico e scripts -->
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Gerenciar Posts Pendentes</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                <li class="breadcrumb-item active">Gerenciar Posts</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($posts) && count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <!-- Cabeçalho do Post -->
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <i class="bi bi-file-text me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($post['titulo']); ?>
                                        </h5>
                                        <div class="text-muted small">
                                            <i class="bi bi-person me-1"></i> Por <?php echo htmlspecialchars($post['author_name']); ?> 
                                            <span class="mx-2">|</span>
                                            <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($post['author_email']); ?>
                                            <span class="mx-2">|</span>
                                            <i class="bi bi-clock me-1"></i> <?php echo formatarData($post['data_criacao']); ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning px-3 py-2">
                                        <i class="bi bi-hourglass-split me-1"></i> Aguardando Revisão
                                    </span>
                                </div>

                                <form action="gerenciar-posts.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                    
                                    <!-- Prévia do Post -->
                                    <div class="row">
                                        <!-- Coluna da Imagem -->
                                        <div class="col-md-4 mb-4">
                                            <?php if ($post['imagem_capa']): ?>
                                                <div class="position-relative">
                                                    <img src="../uploads/blog/<?php echo $post['id']; ?>/<?php echo basename(htmlspecialchars($post['imagem_capa'])); ?>" 
                                                         class="img-fluid rounded shadow-sm" 
                                                         alt="Imagem do post">
                                                    <div class="position-absolute bottom-0 start-0 p-3 w-100 bg-dark bg-opacity-50 text-white">
                                                        <small><i class="bi bi-camera me-1"></i> Imagem atual</small>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <label class="form-label text-muted small">
                                                        <i class="bi bi-arrow-repeat me-1"></i> Alterar imagem (opcional)
                                                    </label>
                                                    <input type="file" name="imagem_capa" class="form-control form-control-sm" accept="image/*">
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Coluna do Conteúdo -->
                                        <div class="col-md-8">
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-type-h1 me-1"></i> Título do Post
                                                </label>
                                                <input type="text" name="titulo" class="form-control" 
                                                       value="<?php echo htmlspecialchars($post['titulo']); ?>" required>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-text-paragraph me-1"></i> Conteúdo
                                                </label>
                                                <textarea name="conteudo" class="post-content-editor"><?php echo htmlspecialchars($post['conteudo']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Área de Feedback do Admin -->
                                    <div class="bg-light p-4 rounded-3 mb-4">
                                        <h6 class="fw-bold mb-3">
                                            <i class="bi bi-chat-square-text me-2 text-primary"></i>
                                            Feedback para o Autor
                                        </h6>
                                        <textarea name="comentario" class="form-control" rows="4" 
                                            placeholder="Forneça um feedback construtivo sobre o post. Suas observações ajudarão o autor a melhorar o conteúdo."
                                        ><?php echo htmlspecialchars($post['comentario_admin'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Botões de Ação -->
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="submit" name="action" value="aprovar" class="btn btn-success btn-lg px-5">
                                            <i class="bi bi-check-circle me-2"></i> Aprovar Post
                                        </button>
                                        <button type="submit" name="action" value="rejeitar" class="btn btn-danger btn-lg px-5">
                                            <i class="bi bi-x-circle me-2"></i> Rejeitar Post
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <h4 class="mt-4 mb-3">Nenhum post pendente!</h4>
                            <p class="text-muted mb-4">Todos os posts foram revisados. Bom trabalho!</p>
                            <a href="../index.php" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i> Voltar para o Início
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>

<!-- Scripts do TinyMCE -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
            selector: '.post-content-editor',
            height: 300,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        });
    });
</script> 