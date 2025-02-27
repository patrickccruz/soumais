<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir conexão com o banco
require_once __DIR__ . '/../db.php';

session_start();

// Limpar sessão anterior se existir
if (isset($_SESSION['loggedin'])) {
    session_destroy();
    session_start();
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
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            throw new Exception("Todos os campos são obrigatórios");
        }

        // Verificar conexão com o banco
        if ($conn->connect_error) {
            throw new Exception("Erro de conexão com o banco de dados");
        }

        // Preparar a consulta SQL
        $sql = "SELECT * FROM usuarios WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verificar a senha
            if (password_verify($password, $user['password'])) {
                // Login bem sucedido
                $_SESSION['loggedin'] = true;
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
                header("Location: ../index.php");
                exit;
            } else {
                $_SESSION['error'] = "Senha incorreta";
                header("Location: ../login.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Usuário não encontrado";
            header("Location: ../login.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = $e->getMessage();
        error_log("Tentativa de login falhou: {$username} - " . $e->getMessage());
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
