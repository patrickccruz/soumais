<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

// Define que está em uma página
$is_page = true;

// Incluir dados do usuário
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    $user = ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
}

// Conexão com o banco de dados
require_once '../db.php';

// Buscar notificações do usuário
$sql = "SELECT * FROM notificacoes WHERE user_id = ? ORDER BY data_criacao DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$notificacoes = $stmt->get_result();

// Incluir cabeçalho e sidebar
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Notificações</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                <li class="breadcrumb-item active">Notificações</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Todas as notificações</h5>
                        
                        <?php if ($notificacoes && $notificacoes->num_rows > 0): ?>
                            <div class="list-group notification-list">
                                <?php while($notif = $notificacoes->fetch_assoc()): ?>
                                    <div class="list-group-item notification-item <?php echo $notif['lida'] ? 'read' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>">
                                        <div class="d-flex">
                                            <div class="notification-icon <?php echo $notif['tipo']; ?> me-3">
                                                <?php
                                                switch($notif['tipo']) {
                                                    case 'aprovacao':
                                                        echo '<i class="bi bi-check-circle"></i>';
                                                        break;
                                                    case 'rejeicao':
                                                        echo '<i class="bi bi-x-circle"></i>';
                                                        break;
                                                    case 'comentario':
                                                        echo '<i class="bi bi-chat-dots"></i>';
                                                        break;
                                                    case 'sistema':
                                                        echo '<i class="bi bi-gear"></i>';
                                                        break;
                                                }
                                                ?>
                                            </div>
                                            <div class="notification-content flex-grow-1">
                                                <h5><?php echo htmlspecialchars($notif['titulo']); ?></h5>
                                                <p><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['data_criacao'])); ?></small>
                                            </div>
                                            <div class="notification-actions">
                                                <?php if (!$notif['lida']): ?>
                                                    <button class="btn btn-sm btn-outline-primary mark-as-read" data-id="<?php echo $notif['id']; ?>">
                                                        Marcar como lida
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($notif['link']): ?>
                                                    <?php
                                                    $link = $notif['link'];
                                                    if (isset($is_page)) {
                                                        $link = preg_replace('/^page\//', '', $link);
                                                    } else {
                                                        if (!str_starts_with($link, 'page/')) {
                                                            $link = 'page/' . $link;
                                                        }
                                                    }
                                                    ?>
                                                    <a href="<?php echo htmlspecialchars($link); ?>" class="btn btn-sm btn-primary ms-2">
                                                        Ver detalhes
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> Você não possui notificações.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    // Script para marcar notificações como lidas
    document.addEventListener('DOMContentLoaded', function() {
        const markReadButtons = document.querySelectorAll('.mark-as-read');
        
        markReadButtons.forEach(button => {
            button.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                const notificationItem = this.closest('.notification-item');
                
                // Fazer requisição AJAX para marcar como lida
                fetch('../ajax/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar UI
                        notificationItem.classList.remove('unread');
                        notificationItem.classList.add('read');
                        button.remove();
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
            });
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>
