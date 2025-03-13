<?php
if (!isset($_SESSION)) {
    session_start();
}

// Incluir arquivo de funções
require_once __DIR__ . '/functions.php';

// Configurar fuso horário para Brasil
date_default_timezone_set('America/Sao_Paulo');

if (!isset($user)) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
    } else {
        $user = ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
    }
}

// Buscar notificações não lidas
require_once __DIR__ . '/../db.php';

// Configurar timezone do MySQL
$conn->query("SET time_zone = '-03:00'");

$sql = "SELECT * FROM notificacoes WHERE user_id = ? AND lida = FALSE ORDER BY data_criacao DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$notificacoes = $stmt->get_result();

// Contar total de notificações não lidas
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE user_id = ? AND lida = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$total_nao_lidas = $result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Sou + Digital</title>
    <!-- Favicons -->
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/img/Icon geral.png" rel="icon">
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/img/Icon geral.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/css/style.css" rel="stylesheet">
    <link href="<?php echo isset($is_page) ? '../' : ''; ?>assets/css/notifications.css" rel="stylesheet">
</head>
<body>
    <header id="header" class="header fixed-top d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?php echo isset($is_page) ? '../' : ''; ?>index.php" class="logo d-flex align-items-center">
                <img src="<?php echo isset($is_page) ? '../' : ''; ?>assets/img/Ico_geral.png" alt="Logo">
                <span class="d-none d-lg-block">Sou + Digital</span>
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link nav-icon position-relative" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($total_nao_lidas > 0): ?>
                            <span class="notification-badge"><?php echo $total_nao_lidas; ?></span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications-dropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <div>
                                Você tem <?php echo $total_nao_lidas; ?> notificação<?php echo $total_nao_lidas != 1 ? 'ões' : ''; ?> não lida<?php echo $total_nao_lidas != 1 ? 's' : ''; ?>
                            </div>
                            <?php if ($total_nao_lidas > 0): ?>
                                <a href="<?php echo isset($is_page) ? '' : 'page/'; ?>notificacoes.php" class="badge rounded-pill bg-primary p-2 ms-2">
                                    Ver todas
                                </a>
                            <?php endif; ?>
                        </li>

                        <div class="notifications-list">
                            <?php if ($notificacoes && $notificacoes->num_rows > 0): ?>
                                <?php while($notif = $notificacoes->fetch_assoc()): ?>
                                    <div class="notification-item" data-id="<?php echo $notif['id']; ?>">
                                        <div class="icon <?php echo $notif['tipo']; ?>">
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
                                        <div class="content">
                                            <h4><?php echo htmlspecialchars($notif['titulo']); ?></h4>
                                            <p><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                            <small data-time="<?php echo $notif['data_criacao']; ?>">
                                                <?php echo tempo_decorrido($notif['data_criacao']); ?>
                                            </small>
                                        </div>
                                        <div class="actions">
                                            <button class="mark-as-read" data-id="<?php echo $notif['id']; ?>">
                                                <i class="bi bi-check2"></i>
                                            </button>
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
                                                <a href="<?php echo htmlspecialchars($link); ?>" class="btn btn-link">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="notification-item">
                                    <div class="icon sistema">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                    <div class="content">
                                        <h4>Nenhuma notificação</h4>
                                        <p>Você não tem notificações não lidas</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <li class="notification-settings">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notification-sound-toggle">
                                <label class="form-check-label" for="notification-sound-toggle">Som de notificação</label>
                            </div>
                        </li>

                        <?php if ($total_nao_lidas > 0): ?>
                            <li class="dropdown-footer">
                                <a href="<?php echo isset($is_page) ? '' : 'page/'; ?>notificacoes.php">Ver todas as notificações</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <span class="d-none d-md-block dropdown-toggle ps-2">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6>Nome: <?php echo htmlspecialchars($user['name']); ?></h6>
                            <span>Usuário: <?php echo htmlspecialchars($user['username']); ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo isset($is_page) ? '' : 'page/'; ?>meu-perfil.php">
                                <i class="bi bi-person"></i>
                                <span>Meu Perfil</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?php echo isset($is_page) ? '' : 'page/'; ?>sair.php">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Deslogar</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Adicionar os scripts necessários -->
    <script src="<?php echo isset($is_page) ? '../' : ''; ?>assets/js/notifications.js"></script>
</body>
</html> 