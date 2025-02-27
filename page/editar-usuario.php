<?php
session_start();
require_once '../db.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: user-login.php');
    exit;
}

// Verificação de permissão de administrador
if (!isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Anti CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido');
    }
}
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Inicialização de variáveis
$error_message = '';
$success_message = '';
$user = null;

try {
    if (!isset($conn) || !$conn) {
        throw new Exception("Falha na conexão com o banco de dados");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validação e sanitização das entradas
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;

        if ($id === false || empty($name) || empty($username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Dados inválidos fornecidos");
        }

        // Verificar se o username já existe (exceto para o usuário atual)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Este nome de usuário já está em uso");
        }

        // Processar upload da imagem de perfil
        $profile_image = $user['profile_image'] ?? null; // Mantém a imagem atual por padrão
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_image']['type'];
            $file_size = $_FILES['profile_image']['size'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.");
            }
            if ($file_size > $max_size) {
                throw new Exception("Arquivo muito grande. O tamanho máximo é 2MB.");
            }

            $upload_dir = '../uploads/users/' . $id . '/profile/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Remover imagem antiga se existir
                if ($profile_image && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                $profile_image = $upload_path;
            } else {
                throw new Exception("Erro ao fazer upload da imagem");
            }
        }

        // Preparar a query base
        $sql = "UPDATE users SET name=?, username=?, email=?, is_admin=?";
        $types = "sssi";
        $params = [$name, $username, $email, $is_admin];

        // Adicionar senha se fornecida
        if (!empty($password)) {
            $sql .= ", password=?";
            $types .= "s";
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $params[] = $hashed_password;
        }

        // Adicionar imagem de perfil se houver alteração
        if ($profile_image !== null) {
            $sql .= ", profile_image=?";
            $types .= "s";
            $params[] = $profile_image;
        }

        // Adicionar WHERE
        $sql .= " WHERE id=?";
        $types .= "i";
        $params[] = $id;

        // Atualizar usuário
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar usuário: " . $stmt->error);
        }

        // Log da alteração
        error_log("Usuário ID {$id} atualizado por " . $_SESSION['user']['username']);
        
        $_SESSION['success_message'] = "Usuário atualizado com sucesso";
        header("Location: gerenciar-usuarios.php");
        exit;

    } else {
        // Buscar dados do usuário para edição
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id === false) {
            throw new Exception("ID de usuário inválido");
        }

        $stmt = $conn->prepare("SELECT id, name, email, username, password, profile_image, is_admin FROM users WHERE id=?");
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }

        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao buscar usuário: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            throw new Exception("Usuário não encontrado");
        }
    }

} catch (Exception $e) {
    error_log("Erro na edição de usuário: " . $e->getMessage());
    $error_message = $e->getMessage();
} finally {
    // Fechar o stmt apenas se ele estiver definido e não fechado
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Função auxiliar para sanitização de saída
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$is_page = true; // Indica que estamos em uma página dentro do diretório 'page'
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Usuário</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                <li class="breadcrumb-item">Administração</li>
                <li class="breadcrumb-item active">Editar Usuário</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-person-gear"></i> Formulário de Edição
                        </h5>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?php echo h($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($user): ?>
                            <form method="post" action="editar-usuario.php" class="needs-validation" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="id" value="<?php echo h($user['id']); ?>">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="bi bi-person"></i> Nome Completo
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo h($user['name']); ?>" required
                                           pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]{2,}" 
                                           title="Nome deve conter apenas letras e espaços">
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle"></i> Por favor, insira um nome válido.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="bi bi-person-badge"></i> Nome de Usuário
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo h($user['username']); ?>" required
                                           pattern="[a-zA-Z0-9_]{3,}" 
                                           title="Nome de usuário deve conter apenas letras, números e underscore">
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle"></i> Por favor, insira um nome de usuário válido.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo h($user['email']); ?>" required>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle"></i> Por favor, insira um email válido.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-key"></i> Nova Senha
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               minlength="8" 
                                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                                               title="A senha deve conter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e caracteres especiais">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Deixe em branco para manter a senha atual. A nova senha deve ter no mínimo 8 caracteres.
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="bi bi-exclamation-circle"></i> A senha deve atender aos requisitos de segurança.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">
                                        <i class="bi bi-image"></i> Imagem de Perfil
                                    </label>
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <img src="<?php echo h($user['profile_image']); ?>" 
                                                 alt="Imagem atual" class="rounded-circle" 
                                                 style="width: 64px; height: 64px; object-fit: cover;">
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="profile_image" 
                                               name="profile_image" accept="image/*">
                                    </div>
                                    <div class="form-text">
                                        Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" 
                                               <?php echo ($user['is_admin'] ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="is_admin">
                                            <i class="bi bi-shield-lock"></i> Acesso de Administrador
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="gerenciar-usuarios.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Voltar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Salvar Alterações
                                    </button>
                                </div>
                            </form>

                            <!-- Indicador de força da senha -->
                            <div id="password-strength" class="mt-3 d-none">
                                <h6 class="text-muted mb-2">Força da Senha:</h6>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <ul class="list-unstyled mt-2 small">
                                    <li class="text-muted"><i class="bi bi-check"></i> Mínimo 8 caracteres</li>
                                    <li class="text-muted"><i class="bi bi-check"></i> Uma letra maiúscula</li>
                                    <li class="text-muted"><i class="bi bi-check"></i> Uma letra minúscula</li>
                                    <li class="text-muted"><i class="bi bi-check"></i> Um número</li>
                                    <li class="text-muted"><i class="bi bi-check"></i> Um caractere especial</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Usuário não encontrado.
                                <div class="mt-3">
                                    <a href="gerenciar-usuarios.php" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-arrow-left"></i> Voltar para Lista de Usuários
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($user): ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle"></i> Informações Adicionais
                        </h5>
                        <div class="user-info">
                            <p><strong><i class="bi bi-person-badge"></i> Nome de Usuário:</strong> <?php echo h($user['username']); ?></p>
                            <p><strong><i class="bi bi-key"></i> ID:</strong> <?php echo h($user['id']); ?></p>
                            <p><strong><i class="bi bi-shield"></i> Tipo de Acesso:</strong> 
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge bg-success">Administrador</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Usuário Padrão</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>

<script>
    // Validação do formulário com feedback visual melhorado
    (function() {
        'use strict';
        
        // Toggle de visibilidade da senha
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        }

        // Preview de imagem
        const profileImage = document.getElementById('profile_image');
        if (profileImage) {
            profileImage.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.querySelector('img.rounded-circle');
                        if (img) {
                            img.src = e.target.result;
                        } else {
                            const newImg = document.createElement('img');
                            newImg.src = e.target.result;
                            newImg.classList.add('rounded-circle');
                            newImg.style.width = '64px';
                            newImg.style.height = '64px';
                            newImg.style.objectFit = 'cover';
                            profileImage.parentNode.insertBefore(newImg, profileImage);
                        }
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }

        // Validação de força da senha
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.getElementById('password-strength');
        
        if (passwordInput && strengthIndicator) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                if (password.length > 0) {
                    strengthIndicator.classList.remove('d-none');
                    
                    const criteria = {
                        length: password.length >= 8,
                        uppercase: /[A-Z]/.test(password),
                        lowercase: /[a-z]/.test(password),
                        number: /[0-9]/.test(password),
                        special: /[!@#$%^&*]/.test(password)
                    };

                    const strength = Object.values(criteria).filter(Boolean).length;
                    const progressBar = strengthIndicator.querySelector('.progress-bar');
                    const items = strengthIndicator.querySelectorAll('li');

                    // Atualizar barra de progresso
                    progressBar.style.width = (strength * 20) + '%';
                    progressBar.className = 'progress-bar';
                    if (strength <= 2) progressBar.classList.add('bg-danger');
                    else if (strength <= 3) progressBar.classList.add('bg-warning');
                    else if (strength <= 4) progressBar.classList.add('bg-info');
                    else progressBar.classList.add('bg-success');

                    // Atualizar lista de critérios
                    items[0].className = criteria.length ? 'text-success' : 'text-muted';
                    items[1].className = criteria.uppercase ? 'text-success' : 'text-muted';
                    items[2].className = criteria.lowercase ? 'text-success' : 'text-muted';
                    items[3].className = criteria.number ? 'text-success' : 'text-muted';
                    items[4].className = criteria.special ? 'text-success' : 'text-muted';
                } else {
                    strengthIndicator.classList.add('d-none');
                }
            });
        }

        // Validação do formulário
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    // Adiciona animação de shake nos campos inválidos
                    form.querySelectorAll(':invalid').forEach(function(field) {
                        field.classList.add('animate__animated', 'animate__shakeX');
                        field.addEventListener('animationend', function() {
                            field.classList.remove('animate__animated', 'animate__shakeX');
                        });
                    });
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

</body>
</html>
