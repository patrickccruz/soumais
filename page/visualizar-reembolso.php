<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

// Verificar se um ID de reembolso foi fornecido
$reembolso_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reembolso_id) {
    header("Location: todos-reembolsos.php");
    exit;
}

// Definição para indicar que estamos em uma página dentro da pasta 'page'
$is_page = true;

// Incluir conexão com o banco de dados
require_once '../includes/db.php';
require_once '../includes/upload_functions.php';

// Buscar dados do reembolso
$stmt = $conn->prepare("SELECT r.*, u.name as user_name, u.profile_image 
                       FROM reembolsos r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.id = ?");
$stmt->bind_param("i", $reembolso_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: todos-reembolsos.php");
    exit;
}

$reembolso = $result->fetch_assoc();

// Verificar permissão (apenas admin ou o próprio usuário pode ver)
if (!isset($_SESSION['user']['is_admin']) && $reembolso['user_id'] != $_SESSION['user']['id']) {
    header("Location: ../index.php");
    exit;
}

// Processar aprovação ou rejeição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true) {
    $acao = $_POST['acao'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    
    if ($acao === 'aprovar' || $acao === 'rejeitar' || $acao === 'criticar') {
        $status = $acao === 'aprovar' ? 'aprovado' : ($acao === 'rejeitar' ? 'reprovado' : 'criticado');
        
        $stmt = $conn->prepare("UPDATE reembolsos SET status = ?, comentario_admin = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $comentario, $reembolso_id);
        
        if ($stmt->execute()) {
            // Criar notificação para o usuário
            $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)");
            
            if ($acao === 'aprovar') {
                $tipo = 'aprovacao';
                $titulo = 'Reembolso Aprovado';
                $mensagem = "Seu reembolso #" . $reembolso_id . " foi aprovado!";
            } elseif ($acao === 'rejeitar') {
                $tipo = 'rejeicao';
                $titulo = 'Reembolso Reprovado';
                $mensagem = "Seu reembolso #" . $reembolso_id . " foi reprovado. Motivo: " . $comentario;
            } else {
                $tipo = 'comentario';
                $titulo = 'Feedback no Reembolso';
                $mensagem = "Seu reembolso #" . $reembolso_id . " recebeu um feedback: " . $comentario;
            }
            
            $link = "meus-reembolsos.php";
            $notif_stmt->bind_param("issss", $reembolso['user_id'], $tipo, $titulo, $mensagem, $link);
            $notif_stmt->execute();
            
            $_SESSION['success'] = "Reembolso " . ($acao === 'aprovar' ? 'aprovado' : ($acao === 'rejeitar' ? 'reprovado' : 'atualizado')) . " com sucesso!";
            header("Location: visualizar-reembolso.php?id=" . $reembolso_id);
            exit;
        } else {
            $_SESSION['error'] = "Erro ao processar o reembolso: " . $stmt->error;
        }
    }
}

// Função para obter a classe de cor baseada no status
function getStatusClass($status) {
    switch($status) {
        case 'aprovado':
            return 'success';
        case 'pendente':
            return 'warning';
        case 'criticado':
            return 'info';
        case 'reprovado':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Função para obter o ícone baseado no status
function getStatusIcon($status) {
    switch($status) {
        case 'aprovado':
            return 'bi-check-circle';
        case 'pendente':
            return 'bi-clock';
        case 'criticado':
            return 'bi-exclamation-circle';
        case 'reprovado':
            return 'bi-x-circle';
        default:
            return 'bi-question-circle';
    }
}

include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Detalhes do Reembolso</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true): ?>
                    <li class="breadcrumb-item"><a href="todos-reembolsos.php">Todos os Reembolsos</a></li>
                <?php else: ?>
                    <li class="breadcrumb-item"><a href="meus-reembolsos.php">Meus Reembolsos</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active">Reembolso #<?php echo $reembolso_id; ?></li>
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

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                            <h5 class="card-title">
                                Reembolso #<?php echo $reembolso_id; ?>
                                <span class="badge bg-<?php echo getStatusClass($reembolso['status']); ?> ms-2">
                                    <i class="bi <?php echo getStatusIcon($reembolso['status']); ?>"></i>
                                    <?php echo ucfirst($reembolso['status']); ?>
                                </span>
                            </h5>
                            
                            <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true && $reembolso['status'] === 'pendente'): ?>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                        <i class="bi bi-check-circle"></i> Aprovar
                                    </button>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                        <i class="bi bi-x-circle"></i> Rejeitar
                                    </button>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                        <i class="bi bi-chat-text"></i> Enviar Crítica
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Informações do Reembolso</h5>
                                        <div class="row mb-3">
                                            <div class="col-md-4 fw-bold">Solicitante:</div>
                                            <div class="col-md-8"><?php echo htmlspecialchars($reembolso['user_name']); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4 fw-bold">Tipo:</div>
                                            <div class="col-md-8"><?php echo ucfirst($reembolso['tipo_reembolso']); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4 fw-bold">Data do Gasto:</div>
                                            <div class="col-md-8"><?php echo date('d/m/Y', strtotime($reembolso['data_chamado'])); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4 fw-bold">Valor:</div>
                                            <div class="col-md-8">R$ <?php echo number_format($reembolso['valor'], 2, ',', '.'); ?></div>
                                        </div>
                                        <?php if ($reembolso['numero_chamado']): ?>
                                            <div class="row mb-3">
                                                <div class="col-md-4 fw-bold">Chamado:</div>
                                                <div class="col-md-8"><?php echo htmlspecialchars($reembolso['numero_chamado']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="row mb-3">
                                            <div class="col-md-4 fw-bold">Data Solicitação:</div>
                                            <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($reembolso['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Descrição</h5>
                                        <p><?php echo nl2br(htmlspecialchars($reembolso['informacoes_adicionais'])); ?></p>
                                        
                                        <?php if ($reembolso['comentario_admin']): ?>
                                            <div class="alert alert-<?php echo $reembolso['status'] === 'criticado' ? 'info' : ($reembolso['status'] === 'reprovado' ? 'danger' : 'success'); ?> mt-3">
                                                <h6 class="alert-heading"><i class="bi bi-chat-quote"></i> Feedback do Administrador:</h6>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($reembolso['comentario_admin'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Comprovantes -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Comprovantes</h5>
                                <?php if ($reembolso['arquivo_path']): ?>
                                    <div class="row">
                                        <?php 
                                        $arquivos = explode(',', $reembolso['arquivo_path']);
                                        foreach($arquivos as $arquivo):
                                            $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
                                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                        ?>
                                            <div class="col-md-3 mb-3">
                                                <a href="../<?php echo $arquivo; ?>" target="_blank" class="d-block">
                                                    <img src="../<?php echo $arquivo; ?>" class="img-fluid rounded" alt="Comprovante">
                                                    <div class="text-center mt-2">
                                                        <small class="text-muted">Clique para ampliar</small>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body text-center">
                                                        <i class="bi bi-file-earmark-text display-4"></i>
                                                        <p class="mt-2"><?php echo strtoupper($ext); ?></p>
                                                        <a href="../<?php echo $arquivo; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="bi bi-download"></i> Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> Nenhum comprovante anexado.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="text-center mt-4 mb-3">
                            <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true): ?>
                                <a href="todos-reembolsos.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Voltar para Lista
                                </a>
                            <?php else: ?>
                                <a href="meus-reembolsos.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Voltar para Meus Reembolsos
                                </a>
                                
                                <?php if ($reembolso['status'] === 'criticado'): ?>
                                    <a href="editar-reembolso.php?id=<?php echo $reembolso['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-pencil"></i> Editar Reembolso
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal de Aprovação -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Aprovar Reembolso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a aprovar este reembolso. Deseja adicionar algum comentário?</p>
                    <div class="mb-3">
                        <label for="aprovacao-comentario" class="form-label">Comentário (opcional)</label>
                        <textarea class="form-control" id="aprovacao-comentario" name="comentario" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="acao" value="aprovar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprovar Reembolso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Rejeição -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle"></i> Rejeitar Reembolso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a rejeitar este reembolso. Por favor, forneça o motivo:</p>
                    <div class="mb-3">
                        <label for="rejeicao-comentario" class="form-label">Motivo da Rejeição</label>
                        <textarea class="form-control" id="rejeicao-comentario" name="comentario" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="acao" value="rejeitar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rejeitar Reembolso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-chat-text"></i> Enviar Crítica/Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Envie um feedback ao usuário solicitando ajustes ou mais informações:</p>
                    <div class="mb-3">
                        <label for="feedback-comentario" class="form-label">Feedback</label>
                        <textarea class="form-control" id="feedback-comentario" name="comentario" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="acao" value="criticar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Enviar Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
