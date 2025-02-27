<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: page/autenticacao.php");
    exit;
}

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    $user = ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
}

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'sou_digital');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Buscar posts do blog
$sql = "SELECT p.*, u.name as author_name, 
        (SELECT COUNT(*) FROM blog_reacoes WHERE post_id = p.id) as total_reactions,
        (SELECT COUNT(*) FROM blog_comentarios WHERE post_id = p.id) as total_comments
        FROM blog_posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'aprovado'
        ORDER BY p.data_criacao DESC";

$result = $conn->query($sql);

// Armazenar os posts em um array
$posts = [];
if ($result && $result->num_rows > 0) {
    while ($post = $result->fetch_assoc()) {
        $posts[] = $post;
    }
}

include_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Página Inicial - Sou + Digital</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/Icon geral.png" rel="icon">
    <link href="assets/img/Icon geral.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .blog-post {
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        .blog-post:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .post-image-container {
            position: relative;
            overflow: hidden;
            height: 200px;
            cursor: pointer;
        }
        .post-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .post-image-container:hover .post-image {
            transform: scale(1.05);
        }
        .post-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .post-image-container:hover .post-overlay {
            opacity: 1;
        }
        .card-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .card-title a {
            color: #012970;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .card-title a:hover {
            color: #0d6efd;
        }
        .card-text {
            color: #6c757d;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .post-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .post-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .post-stats i {
            margin-right: 0.3rem;
        }
        .btn-novo-post {
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-novo-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
    </style>
</head>

<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <main id="main" class="main">
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Cabeçalho da Página -->
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                                <div>
                                    <h1 class="card-title fs-2 mb-1">
                                        <i class="bi bi-newspaper text-primary me-2"></i>
                                        Blog Sou + Digital
                                    </h1>
                                    <p class="text-muted mb-0">Compartilhe conhecimento, experiências e novidades</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="page/criar-post.php" class="btn btn-primary btn-novo-post">
                                        <i class="bi bi-plus-lg me-1"></i> Novo Post
                                    </a>
                                    <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true): ?>
                                    <a href="page/gerenciar-posts.php" class="btn btn-outline-primary btn-novo-post">
                                        <i class="bi bi-list-check me-1"></i> Gerenciar Posts
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div>
                                    <?php 
                                    echo $_SESSION['success'];
                                    unset($_SESSION['success']);
                                    ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div>
                                    <?php 
                                    echo $_SESSION['error'];
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <!-- Grid de Posts -->
                            <div class="row g-4">
                                <?php if (!empty($posts)): ?>
                                    <?php foreach ($posts as $post): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card blog-post h-100">
                                                <?php if ($post['imagem_capa']): ?>
                                                <a href="page/visualizar-post.php?id=<?php echo $post['id']; ?>" 
                                                   class="post-image-container">
                                                    <img src="uploads/blog/<?php echo $post['id']; ?>/<?php echo basename(htmlspecialchars($post['imagem_capa'])); ?>" 
                                                         class="post-image" 
                                                         alt="<?php echo htmlspecialchars($post['titulo']); ?>">
                                                    <div class="post-overlay d-flex align-items-center justify-content-center">
                                                        <span class="btn btn-light btn-sm">
                                                            <i class="bi bi-eye me-1"></i> Ver Post
                                                        </span>
                                                    </div>
                                                </a>
                                                <?php endif; ?>
                                                <div class="card-body d-flex flex-column">
                                                    <h5 class="card-title">
                                                        <a href="page/visualizar-post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($post['titulo']); ?>
                                                        </a>
                                                    </h5>
                                                    <p class="card-text flex-grow-1">
                                                        <?php 
                                                        $preview = strip_tags($post['conteudo']);
                                                        echo strlen($preview) > 150 ? substr($preview, 0, 150) . "..." : $preview;
                                                        ?>
                                                    </p>
                                                    <div class="post-meta mt-3 pt-3 border-top">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="author">
                                                                <i class="bi bi-person-circle me-1"></i>
                                                                <?php echo htmlspecialchars($post['author_name']); ?>
                                                                <div class="text-muted small">
                                                                    <i class="bi bi-calendar3 me-1"></i>
                                                                    <?php echo date('d/m/Y', strtotime($post['data_criacao'])); ?>
                                                                </div>
                                                            </div>
                                                            <div class="post-stats d-flex gap-3">
                                                                <span class="text-primary" title="Comentários">
                                                                    <i class="bi bi-chat-dots"></i> <?php echo $post['total_comments']; ?>
                                                                </span>
                                                                <span class="text-danger" title="Reações">
                                                                    <i class="bi bi-heart"></i> <?php echo $post['total_reactions']; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="empty-state">
                                            <i class="bi bi-journal-text"></i>
                                            <h4 class="mt-3 mb-2">Nenhum post publicado ainda</h4>
                                            <p class="text-muted mb-4">Seja o primeiro a compartilhar algo interessante!</p>
                                            <a href="page/criar-post.php" class="btn btn-primary btn-novo-post">
                                                <i class="bi bi-plus-lg me-1"></i> Criar Primeiro Post
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>
</body>
</html>