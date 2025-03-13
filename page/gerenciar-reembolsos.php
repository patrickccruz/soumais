<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header("Location: autenticacao.php");
    exit;
}

$is_page = true;
require_once '../includes/upload_functions.php';

// Conexão com o banco de dados
require_once '../includes/db.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Buscar dados do usuário logado
$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];

// Processar ações de aprovação/rejeição/crítica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reembolso_id = $_POST['reembolso_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    
    if ($reembolso_id && $action) {
        $status = '';
        switch($action) {
            case 'aprovar':
                $status = 'aprovado';
                break;
            case 'rejeitar':
                $status = 'reprovado';
                break;
            case 'criticar':
                $status = 'criticado';
                break;
        }

        // Atualizar status do reembolso
        $stmt = $conn->prepare("UPDATE reembolsos SET status = ?, comentario_admin = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $comentario, $reembolso_id);
        
        if ($stmt->execute()) {
            // Buscar informações do reembolso e do usuário
            $info_query = $conn->prepare("SELECT r.*, u.name as user_name FROM reembolsos r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
            $info_query->bind_param("i", $reembolso_id);
            $info_query->execute();
            $reembolso = $info_query->get_result()->fetch_assoc();

            if ($reembolso) {
                // Criar notificação para o usuário
                $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)");
                
                switch($status) {
                    case 'aprovado':
                        $tipo = 'aprovacao';
                        $titulo = 'Reembolso Aprovado';
                        $mensagem = "Seu reembolso no valor de R$ " . number_format($reembolso['valor'], 2, ',', '.') . " foi aprovado!";
                        break;
                    case 'reprovado':
                        $tipo = 'rejeicao';
                        $titulo = 'Reembolso Reprovado';
                        $mensagem = "Seu reembolso no valor de R$ " . number_format($reembolso['valor'], 2, ',', '.') . " foi reprovado. Motivo: $comentario";
                        break;
                    case 'criticado':
                        $tipo = 'sistema';
                        $titulo = 'Reembolso Necessita Ajustes';
                        $mensagem = "Seu reembolso no valor de R$ " . number_format($reembolso['valor'], 2, ',', '.') . " precisa de ajustes: $comentario";
                        break;
                }
                
                $link = "meus-reembolsos.php";
                $notif_stmt->bind_param("issss", $reembolso['user_id'], $tipo, $titulo, $mensagem, $link);
                $notif_stmt->execute();
            }

            $_SESSION['success'] = "Reembolso " . ucfirst($status) . " com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao processar reembolso: " . $stmt->error;
        }
        
        header("Location: gerenciar-reembolsos.php");
        exit;
    }
}

// Buscar reembolsos pendentes
$sql = "SELECT r.*, u.name as user_name, u.email as user_email 
        FROM reembolsos r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.status = 'pendente'
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);
$reembolsos = $result->fetch_all(MYSQLI_ASSOC);

include_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Gerenciar Reembolsos - Sou + Digital</title>
    <style>
        .reembolso-card {
            transition: transform 0.2s;
        }
        .reembolso-card:hover {
            transform: translateY(-5px);
        }
        .arquivo-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            margin: 5px;
            border-radius: 5px;
        }
        .arquivos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include_once '../includes/sidebar.php'; ?>

    <main id="main" class="main">
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="pagetitle">
                                <h1>Gerenciar Reembolsos</h1>
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                                        <li class="breadcrumb-item active">Gerenciar Reembolsos</li>
                                    </ol>
                                </nav>
                            </div>

                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php 
                                    echo $_SESSION['success'];
                                    unset($_SESSION['success']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php 
                                    echo $_SESSION['error'];
                                    unset($_SESSION['error']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($reembolsos)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Não há reembolsos pendentes para análise.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($reembolsos as $reembolso): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card reembolso-card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title d-flex justify-content-between align-items-center">
                                                        <span>
                                                            Reembolso #<?php echo $reembolso['id']; ?>
                                                            <small class="text-muted">
                                                                (<?php echo ucfirst($reembolso['tipo_reembolso']); ?>)
                                                            </small>
                                                        </span>
                                                        <span class="badge bg-warning">Pendente</span>
                                                    </h5>

                                                    <div class="user-info mb-3">
                                                        <strong><i class="bi bi-person"></i> Solicitante:</strong>
                                                        <?php echo htmlspecialchars($reembolso['user_name']); ?>
                                                        <br>
                                                        <strong><i class="bi bi-envelope"></i> Email:</strong>
                                                        <?php echo htmlspecialchars($reembolso['user_email']); ?>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <strong><i class="bi bi-calendar"></i> Data do Gasto:</strong>
                                                            <br>
                                                            <?php echo date('d/m/Y', strtotime($reembolso['data_chamado'])); ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong><i class="bi bi-currency-dollar"></i> Valor:</strong>
                                                            <br>
                                                            R$ <?php echo number_format($reembolso['valor'], 2, ',', '.'); ?>
                                                        </div>
                                                    </div>

                                                    <?php if ($reembolso['numero_chamado']): ?>
                                                        <p>
                                                            <strong><i class="bi bi-hash"></i> Chamado:</strong>
                                                            <?php echo htmlspecialchars($reembolso['numero_chamado']); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <p>
                                                        <strong><i class="bi bi-text-left"></i> Descrição:</strong>
                                                        <br>
                                                        <?php echo htmlspecialchars($reembolso['informacoes_adicionais']); ?>
                                                    </p>

                                                    <?php if ($reembolso['arquivo_path']): ?>
                                                        <div class="arquivos-container">
                                                            <?php 
                                                            $arquivos = explode(',', $reembolso['arquivo_path']);
                                                            foreach($arquivos as $arquivo):
                                                                $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
                                                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                                            ?>
                                                                <a href="../<?php echo $arquivo; ?>" target="_blank">
                                                                    <img src="../<?php echo $arquivo; ?>" class="arquivo-preview" alt="Comprovante">
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="../<?php echo $arquivo; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                                    <i class="bi bi-file-earmark-text"></i>
                                                                    Ver Arquivo
                                                                </a>
                                                            <?php 
                                                                endif;
                                                            endforeach; 
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <hr>

                                                    <!-- Formulário de Ação -->
                                                    <form method="POST" class="mt-3">
                                                        <input type="hidden" name="reembolso_id" value="<?php echo $reembolso['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="comentario_<?php echo $reembolso['id']; ?>" class="form-label">
                                                                <i class="bi bi-chat-dots"></i> Comentário/Feedback
                                                            </label>
                                                            <textarea class="form-control" 
                                                                    id="comentario_<?php echo $reembolso['id']; ?>" 
                                                                    name="comentario" 
                                                                    rows="3" 
                                                                    placeholder="Digite seu feedback aqui..."></textarea>
                                                        </div>

                                                        <div class="d-flex gap-2 justify-content-center">
                                                            <button type="submit" name="action" value="aprovar" class="btn btn-success">
                                                                <i class="bi bi-check-circle"></i> Aprovar
                                                            </button>
                                                            <button type="submit" name="action" value="criticar" class="btn btn-info">
                                                                <i class="bi bi-pencil"></i> Solicitar Ajustes
                                                            </button>
                                                            <button type="submit" name="action" value="rejeitar" class="btn btn-danger">
                                                                <i class="bi bi-x-circle"></i> Rejeitar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; Copyright <strong><span>Sou + Digital</span></strong>. Todos os direitos reservados
        </div>
        <div class="credits">
            Desenvolvido por <a href="https://www.linkedin.com/in/patrick-da-costa-cruz-08493212a/" target="_blank">Patrick C Cruz</a>
        </div>
    </footer>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script>
        // Confirmar ações importantes
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = e.submitter.value;
                const comentario = this.querySelector('textarea').value;
                
                if (action === 'rejeitar' || action === 'criticar') {
                    if (!comentario.trim()) {
                        e.preventDefault();
                        alert('Por favor, forneça um feedback para ' + action + ' o reembolso.');
                        return;
                    }
                }
                
                if (action === 'rejeitar') {
                    if (!confirm('Tem certeza que deseja rejeitar este reembolso?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html> 