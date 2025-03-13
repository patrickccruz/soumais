<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

require_once '../includes/upload_functions.php';
$user = $_SESSION['user'];

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../includes/db.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $links = isset($_POST['links']) ? $_POST['links'] : [];
    $links_descricao = isset($_POST['links_descricao']) ? $_POST['links_descricao'] : [];

    // Verificar se os termos foram aceitos
    if (!isset($_POST['aceitar_termos']) || $_POST['aceitar_termos'] != '1') {
        $_SESSION['error'] = "Você precisa aceitar os termos de postagem para continuar.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (empty($titulo) || empty($conteudo)) {
        $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Primeiro inserir o post para obter o ID
    $status = isset($user['is_admin']) && $user['is_admin'] === true ? 'aprovado' : 'pendente';
    $data_aprovacao = $status === 'aprovado' ? date('Y-m-d H:i:s') : null;
    
    $stmt = $conn->prepare("INSERT INTO blog_posts (user_id, titulo, conteudo, status, data_aprovacao, data_criacao, imagem_capa) VALUES (?, ?, ?, ?, ?, NOW(), '')");
    $stmt->bind_param("issss", $user['id'], $titulo, $conteudo, $status, $data_aprovacao);
    
    if ($stmt->execute()) {
        $post_id = $conn->insert_id;
        
        // Upload da imagem de capa
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] == UPLOAD_ERR_OK) {
            require_once '../includes/upload_functions.php';
            
            // Tipos de imagem permitidos
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'];
            
            // Tamanho máximo (5MB)
            $max_size = 5 * 1024 * 1024;
            
            // Diretório para salvar as imagens
            $upload_dir = dirname(__DIR__) . '/uploads/blog/' . $post_id;
            
            // Processar upload
            $log_file = dirname(__DIR__) . '/logs/blog_upload.log';
            $result = process_file_upload(
                $_FILES['imagem_capa'],
                $upload_dir . '/cover_image',
                $allowed_types,
                $max_size,
                'cover_',
                $log_file
            );
            
            if ($result['success']) {
                // Atualizar o caminho da imagem no banco
                $imagem_path = $result['path'];
                $stmt = $conn->prepare("UPDATE blog_posts SET imagem_capa = ? WHERE id = ?");
                $stmt->bind_param("si", $imagem_path, $post_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar caminho da imagem: " . $stmt->error);
                }
            } else {
                // Falha no upload, mas continuamos mesmo assim (post sem imagem)
                $warning_message = "Aviso: " . $result['message'] . ". O post foi criado sem imagem de capa.";
            }
        }

        // Inserir links
        if (!empty($links)) {
            $stmt = $conn->prepare("INSERT INTO blog_links (post_id, url, descricao) VALUES (?, ?, ?)");
            foreach ($links as $i => $url) {
                if (!empty($url) && !empty($links_descricao[$i])) {
                    $stmt->bind_param("iss", $post_id, $url, $links_descricao[$i]);
                    $stmt->execute();
                }
            }
        }

        // Notificar todos os administradores apenas se não for um admin
        if ($status === 'pendente') {
            $admin_query = $conn->prepare("SELECT id FROM users WHERE is_admin = 1");
            $admin_query->execute();
            $admin_result = $admin_query->get_result();
            
            while ($admin = $admin_result->fetch_assoc()) {
                $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, 'sistema', ?, ?, ?)");
                $notif_titulo = "Novo Post para Aprovação";
                $mensagem = "O usuário " . $user['name'] . " criou um novo post: \"" . $titulo . "\"";
                $link = "gerenciar-posts.php";
                $notif_stmt->bind_param("isss", $admin['id'], $notif_titulo, $mensagem, $link);
                $notif_stmt->execute();
            }
        }

        $_SESSION['success'] = "Post criado com sucesso!";
        header("Location: gerenciar-posts.php");
        exit;
    } else {
        $_SESSION['error'] = "Erro ao criar o post.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$is_page = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<!-- CSS Específico da Página -->
<style>
    .preview-image {
        max-width: 100%;
        max-height: 300px;
        object-fit: cover;
        border-radius: 10px;
        display: none;
        margin-top: 1rem;
    }
    .link-group {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
    }
    #editor {
        height: 500px;
        background: white;
    }
    .ql-video {
        width: 100%;
        height: 400px;
    }
</style>

<!-- Bibliotecas do Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="pagetitle">
                            <h1>Nova Publicação</h1>
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                                    <li class="breadcrumb-item active">Nova Publicação</li>
                                </ol>
                            </nav>
                        </div>

                        <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <?php if (!isset($user['is_admin']) || $user['is_admin'] !== true): ?>
                            Seu post será revisado por um administrador antes de ser publicado.
                            <?php else: ?>
                            Seu post será publicado imediatamente por você ser um administrador.
                            <?php endif; ?>
                        </div>

                        <form method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>

                            <div class="mb-3">
                                <label for="imagem_capa" class="form-label">Imagem de Capa</label>
                                <input type="file" class="form-control" id="imagem_capa" name="imagem_capa" accept="image/*" onchange="previewImage(this)">
                                <img id="preview" class="preview-image">
                            </div>

                            <div class="mb-3">
                                <label for="conteudo" class="form-label">Conteúdo</label>
                                <div id="editor"></div>
                                <input type="hidden" id="conteudo" name="conteudo">
                            </div>

                            <div id="links-container">
                                <h4 class="mb-3">Links Relacionados</h4>
                                <div class="link-group">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">URL do Link</label>
                                            <input type="url" class="form-control" name="links[]">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Descrição do Link</label>
                                            <input type="text" class="form-control" name="links_descricao[]">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-secondary mb-3" onclick="adicionarLink()">
                                <i class="bi bi-plus-circle"></i> Adicionar Link
                            </button>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="aceitar_termos" name="aceitar_termos" value="1" required>
                                    <label class="form-check-label" for="aceitar_termos">
                                        Li e aceito os <a href="termos-postagem.php" target="_blank">termos de postagem</a>
                                    </label>
                                    <div class="invalid-feedback">
                                        Você precisa aceitar os termos de postagem para continuar.
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Enviar para Aprovação</button>
                                <a href="../index.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>

<script>
    function previewImage(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Inicializar Quill somente após a página ser carregada completamente
    document.addEventListener('DOMContentLoaded', function() {
        // Inicialização do Quill
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            },
            placeholder: 'Escreva seu conteúdo aqui...'
        });
        
        // Quando o formulário for enviado, atualiza o campo hidden com o conteúdo HTML
        document.querySelector('form').addEventListener('submit', function(e) {
            var content = quill.root.innerHTML;
            if (!content || content.trim() === '') {
                e.preventDefault();
                alert('Por favor, preencha o conteúdo do post.');
                return;
            }
            document.getElementById('conteudo').value = content;
        });

        // Handler para upload de imagens
        quill.getModule('toolbar').addHandler('image', function() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = async function() {
                const file = input.files[0];
                if (file) {
                    try {
                        const formData = new FormData();
                        formData.append('file', file);

                        const response = await fetch('../upload.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) throw new Error('Erro no upload');

                        const data = await response.json();
                        const range = quill.getSelection(true);
                        quill.insertEmbed(range.index, 'image', data.location);
                    } catch (error) {
                        alert('Erro ao fazer upload da imagem: ' + error.message);
                    }
                }
            };
        });
    });

    function adicionarLink() {
        const container = document.getElementById('links-container');
        const linkGroup = document.createElement('div');
        linkGroup.className = 'link-group';
        linkGroup.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">URL do Link</label>
                    <input type="url" class="form-control" name="links[]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Descrição do Link</label>
                    <input type="text" class="form-control" name="links_descricao[]">
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
                <i class="bi bi-trash"></i> Remover Link
            </button>
        `;
        container.appendChild(linkGroup);
    }
</script> 