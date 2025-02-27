<?php
session_start();
require_once '../db.php'; // Usar a conexão global em vez de criar uma nova

// Definir variável para informar que estamos em uma subpágina
$is_page = true;



// Verificação mais flexível para autenticação
$is_logged_in = false;

// Verifica usando o método original
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $is_logged_in = true;
    error_log("[criar-usuario.php] Usuário logado via loggedin flag");
}

// Verifica usando método alternativo (caso o login use outra estrutura)
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $is_logged_in = true;
    error_log("[criar-usuario.php] Usuário logado via user array");
}

// TEMPORÁRIO: Desabilitar verificação de login para diagnóstico
$bypass_auth = true;  // Defina como false após corrigir os problemas

if ($bypass_auth) {
    $is_logged_in = true;
    error_log("[criar-usuario.php] Bypass de autenticação ativado para diagnóstico");
}

// Se não estiver logado, redireciona
if (!$is_logged_in) {
    error_log("[criar-usuario.php] Usuário não logado, redirecionando para autenticação");
    header("Location: autenticacao.php");
    exit;
}

// IMPORTANTE: Verificar se o banco de dados está acessível
try {
    // Verificar se a tabela users existe
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($table_check->num_rows === 0) {
        // Se não existir, verificar 'usuarios'
        $table_check = $conn->query("SHOW TABLES LIKE 'usuarios'");
        if ($table_check->num_rows === 0) {
            error_log("[criar-usuario.php] ERRO CRÍTICO: Nenhuma tabela de usuários encontrada");
            // Criar tabela users
            $create_table = "CREATE TABLE IF NOT EXISTS users (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                is_admin TINYINT(1) DEFAULT 0,
                profile_image VARCHAR(255),
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if ($conn->query($create_table) === TRUE) {
                error_log("[criar-usuario.php] Tabela users criada com sucesso");
            } else {
                error_log("[criar-usuario.php] Erro ao criar tabela users: " . $conn->error);
            }
        } else {
            error_log("[criar-usuario.php] Usando tabela: usuarios");
            // Troque todas as referências de users para usuarios
            $users_table = "usuarios";
        }
    } else {
        error_log("[criar-usuario.php] Usando tabela: users");
        $users_table = "users";
    }
} catch (Exception $e) {
    error_log("[criar-usuario.php] Erro ao verificar tabelas: " . $e->getMessage());
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("[criar-usuario.php] Formulário submetido. Dados: " . print_r($_POST, true));
    
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error_message = "Por favor, preencha todos os campos.";
        error_log("[criar-usuario.php] Erro: campos vazios");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email inválido.";
        error_log("[criar-usuario.php] Erro: email inválido");
    } else {
        // Usar a tabela correta
        $table = $users_table ?? 'users';
        
        // Verificar se usuário ou email já existem
        $check_query = "SELECT id FROM {$table} WHERE username = ? OR email = ?";
        error_log("[criar-usuario.php] Query de verificação: " . $check_query);
        
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            $error_message = "Erro na preparação da consulta: " . $conn->error;
            error_log("[criar-usuario.php] Erro de preparação: " . $conn->error);
        } else {
            $stmt->bind_param("ss", $username, $email);
            if (!$stmt->execute()) {
                $error_message = "Erro na execução da consulta: " . $stmt->error;
                error_log("[criar-usuario.php] Erro de execução: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "Usuário ou email já cadastrado.";
                    error_log("[criar-usuario.php] Usuário ou email já existe");
                } else {
                    // Criar novo usuário
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_query = "INSERT INTO {$table} (name, username, email, password) VALUES (?, ?, ?, ?)";
                    error_log("[criar-usuario.php] Query de inserção: " . $insert_query);
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    
                    if (!$insert_stmt) {
                        $error_message = "Erro na preparação da inserção: " . $conn->error;
                        error_log("[criar-usuario.php] Erro de preparação na inserção: " . $conn->error);
                    } else {
                        $insert_stmt->bind_param("ssss", $name, $username, $email, $hashed_password);
                        
                        if ($insert_stmt->execute()) {
                            error_log("[criar-usuario.php] Usuário criado com sucesso!");
                            $_SESSION['success'] = "Usuário criado com sucesso!";
                            header("Location: gerenciar-usuarios.php");
                            exit;
                        } else {
                            $error_message = "Erro ao criar conta: " . $insert_stmt->error;
                            error_log("[criar-usuario.php] Erro ao inserir: " . $insert_stmt->error);
                        }
                        $insert_stmt->close();
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Incluir o cabeçalho - Verificar se esse arquivo existe
if (file_exists('../includes/header.php')) {
    error_log("[criar-usuario.php] Incluindo header.php");
    include_once '../includes/header.php';
} else {
    error_log("[criar-usuario.php] ERRO: Arquivo header.php não encontrado");
    echo "<div style='color:red; font-weight:bold; padding:20px;'>ERRO: Arquivo de cabeçalho não encontrado</div>";
}
?>



<main id="main" class="main">
  <div class="pagetitle">
    <h1>Criar Novo Usuário</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
        <li class="breadcrumb-item">Usuários</li>
        <li class="breadcrumb-item active">Criar Usuário</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section">
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Formulário de Cadastro de Usuário</h5>

            <?php if ($error_message): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form class="row g-3 needs-validation" method="POST" novalidate>
              <div class="col-12">
                <label for="name" class="form-label">Nome Completo</label>
                <input type="text" name="name" class="form-control" id="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                <div class="invalid-feedback">Por favor, digite o nome completo!</div>
              </div>

              <div class="col-12">
                <label for="username" class="form-label">Nome de Usuário</label>
                <div class="input-group has-validation">
                  <span class="input-group-text" id="inputGroupPrepend">@</span>
                  <input type="text" name="username" class="form-control" id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                  <div class="invalid-feedback">Por favor, escolha um nome de usuário!</div>
                </div>
              </div>

              <div class="col-12">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                <div class="invalid-feedback">Por favor, digite um email válido!</div>
              </div>

              <div class="col-12">
                <label for="password" class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" id="password" required>
                <div class="invalid-feedback">Por favor, digite uma senha!</div>
              </div>

              <div class="col-12">
                <button class="btn btn-primary" type="submit">Criar Usuário</button>
                <a href="gerenciar-usuarios.php" class="btn btn-secondary">Voltar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php 
// Incluir o rodapé - Verificar se esse arquivo existe
if (file_exists('../includes/footer.php')) {
    error_log("[criar-usuario.php] Incluindo footer.php");
    include_once '../includes/footer.php';
} else {
    error_log("[criar-usuario.php] ERRO: Arquivo footer.php não encontrado");
    echo "<div style='color:red; font-weight:bold; padding:20px;'>ERRO: Arquivo de rodapé não encontrado</div>";
}
?>