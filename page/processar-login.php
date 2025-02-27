<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log para debug
error_log("[processar-login.php] Iniciando processamento de login");
error_log("[processar-login.php] POST: " . print_r($_POST, true));

// Incluir conexão com o banco
require_once __DIR__ . '/../db.php';

error_log("[processar-login.php] DB incluído, verificando status da conexão");
if ($conn->connect_error) {
    error_log("[processar-login.php] ERRO CRÍTICO: Falha na conexão com o banco de dados: " . $conn->connect_error);
}

session_start();
error_log("[processar-login.php] Sessão iniciada, ID: " . session_id());

// Limpar sessão anterior se existir
if (isset($_SESSION['loggedin'])) {
    error_log("[processar-login.php] Limpando sessão anterior");
    session_unset();
    // Não use session_destroy() aqui, pois você ainda precisa da sessão
}

// Proteção contra força bruta
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Verificar tentativas de login
if ($_SESSION['login_attempts'] >= 5) {
    $time_passed = time() - $_SESSION['last_attempt'];
    if ($time_passed < 300) { // 5 minutos de bloqueio
        die("Muitas tentativas de login. Tente novamente em " . (300 - $time_passed) . " segundos.");
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

// Verificar se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validação básica
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new Exception("Todos os campos são obrigatórios");
        }

        error_log("[processar-login.php] Validação de campos ok. Username: " . $username);

        // Verificar conexão com o banco
        if ($conn->connect_error) {
            throw new Exception("Erro de conexão com o banco de dados");
        }

        // Descobrir qual tabela usar
        $users_table = null;
        $user_found = false;
        $user = null;

        // Primeiro, verificar se a tabela 'users' existe
        $table_check = $conn->query("SHOW TABLES LIKE 'users'");
        if ($table_check->num_rows > 0) {
            error_log("[processar-login.php] Tabela 'users' encontrada. Verificando usuário.");
            $users_table = 'users';
            
            // Preparar a consulta SQL para users
            $sql = "SELECT * FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                error_log("[processar-login.php] Erro ao preparar consulta na tabela users: " . $conn->error);
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $user_found = true;
                    error_log("[processar-login.php] Usuário encontrado na tabela users: " . print_r($user, true));
                } else {
                    error_log("[processar-login.php] Usuário não encontrado na tabela users");
                }
                $stmt->close();
            }
        } else {
            error_log("[processar-login.php] Tabela 'users' não encontrada");
        }

        // Se usuário não encontrado em 'users', tentar em 'usuarios'
        if (!$user_found) {
            $table_check = $conn->query("SHOW TABLES LIKE 'usuarios'");
            if ($table_check->num_rows > 0) {
                error_log("[processar-login.php] Tabela 'usuarios' encontrada. Verificando usuário.");
                $users_table = 'usuarios';
                
                // Preparar a consulta SQL para usuarios
                $sql = "SELECT * FROM usuarios WHERE username = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    error_log("[processar-login.php] Erro ao preparar consulta na tabela usuarios: " . $conn->error);
                } else {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        $user_found = true;
                        error_log("[processar-login.php] Usuário encontrado na tabela usuarios: " . print_r($user, true));
                    } else {
                        error_log("[processar-login.php] Usuário não encontrado na tabela usuarios");
                    }
                    $stmt->close();
                }
            } else {
                error_log("[processar-login.php] Tabela 'usuarios' não encontrada");
                throw new Exception("Nenhuma tabela de usuários encontrada no banco de dados");
            }
        }

        // Se usuário foi encontrado em alguma das tabelas
        if ($user_found && $user) {
            // Verificar a senha
            if (password_verify($password, $user['password'])) {
                // Login bem sucedido
                error_log("[processar-login.php] Senha verificada com sucesso para o usuário: " . $username);
                
                $_SESSION['loggedin'] = true;
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'is_admin' => isset($user['is_admin']) ? (bool)$user['is_admin'] : false
                ];
                
                // Adicionar role se existir
                if (isset($user['role'])) {
                    $_SESSION['user']['role'] = $user['role'];
                }
                
                // Salvar a tabela usada na sessão para futuras operações
                $_SESSION['users_table'] = $users_table;
                
                // Log para depuração
                error_log("[processar-login.php] Login bem-sucedido para: {$username}. Dados da sessão: " . print_r($_SESSION, true));
                
                header("Location: ../index.php");
                exit;
            } else {
                error_log("[processar-login.php] Senha incorreta para usuário: {$username}");
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
                $_SESSION['login_error'] = "Senha incorreta";
                header("Location: autenticacao.php");
                exit;
            }
        } else {
            error_log("[processar-login.php] Usuário não encontrado: {$username} em nenhuma tabela");
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $_SESSION['login_error'] = "Usuário não encontrado";
            header("Location: autenticacao.php");
            exit;
        }
    } catch (Exception $e) {
        error_log("[processar-login.php] Erro no login: " . $e->getMessage());
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt'] = time();
        $_SESSION['login_error'] = $e->getMessage();
        header("Location: autenticacao.php");
        exit;
    }
} else {
    error_log("[processar-login.php] Acesso direto a processar-login.php sem POST");
    header("Location: autenticacao.php");
    exit;
}
?>
