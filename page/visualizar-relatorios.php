<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../page/autenticacao.php");
    exit;
}

// Verificação de permissão de administrador
if (!isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

$is_page = true;

// Conexão com o banco de dados
require_once '../includes/db.php';

// Buscar dados do usuário logado
$user = [];
if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT name, username, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result_user = $stmt->get_result();
    $user = $result_user->fetch_assoc();
    $stmt->close();
}
?>

<!-- Estilos específicos da página -->
<style>
    .script-card {
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .script-card:hover {
        transform: translateY(-5px);
    }
    .filter-section {
        background: #f6f9ff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .status-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        align-items: flex-end;
    }
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    .search-box i {
        position: absolute;
        left: 15px;
        top: 12px;
        color: #666;
    }
    .search-box input {
        padding-left: 40px;
    }
    .card-footer {
        background: transparent;
        border-top: 1px solid rgba(0,0,0,.125);
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-state i {
        font-size: 48px;
        color: #ccc;
        margin-bottom: 15px;
    }
</style>

<?php include_once '../includes/header.php'; ?>
<?php include_once '../includes/sidebar.php'; ?>

<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="pagetitle">
                            <h1>Relatórios de Atendimento</h1>
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                                    <li class="breadcrumb-item active">Relatórios</li>
                                </ol>
                            </nav>
                        </div>

                        <!-- Filtros e Pesquisa -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="search-box">
                                        <i class="bi bi-search"></i>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar relatórios...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" name="data_chamado" id="filterDate" class="form-control" placeholder="Data do Chamado">
                                </div>
                                <div class="col-md-3">
                                    <select name="user_id" id="filterUser" class="form-control">
                                        <option value="">Todos os Usuários</option>
                                        <?php 
                                            $users = $conn->query("SELECT id, name FROM users");
                                            while ($user = $users->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="cliente" id="filterCliente" class="form-control" placeholder="Cliente">
                                </div>
                            </div>
                        </div>

                        <!-- Cards dos Relatórios -->
                        <div class="row" id="relatoriosContainer">
                            <?php
                                $where = [];
                                if (!empty($_GET['data_chamado'])) {
                                    $where[] = "reports.data_chamado = '{$_GET['data_chamado']}'";
                                }
                                if (!empty($_GET['cliente'])) {  
                                    $where[] = "reports.cliente LIKE '%{$_GET['cliente']}%'";
                                }
                                if (!empty($_GET['user_id'])) {
                                    $where[] = "reports.user_id = {$_GET['user_id']}";
                                }
                                $whereSQL = implode(' AND ', $where);
                                $result = $conn->query("SELECT reports.*, users.name as user_name, users.profile_image FROM reports JOIN users ON reports.user_id = users.id ".($whereSQL ? "WHERE $whereSQL" : "")." ORDER BY reports.data_chamado DESC");

                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                        // Cálculo de KM percorridos
                                        $km_percorrido = $row['km_final'] - $row['km_inicial'];
                                        
                                        // Cálculo do tempo de atendimento
                                        $hora_chegada = strtotime($row['hora_chegada']);
                                        $hora_saida = strtotime($row['hora_saida']);
                                        $diferenca = $hora_saida - $hora_chegada;
                                        $horas = floor($diferenca / 3600);
                                        $minutos = floor(($diferenca % 3600) / 60);
                            ?>
                                <div class="col-md-6 col-lg-4 mb-4 script-card-container">
                                    <div class="card script-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                Chamado #<?php echo htmlspecialchars($row['numero_chamado']); ?>
                                            </h5>
                                            <div class="status-badge">
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($row['user_name']); ?>
                                                </span>
                                                <?php if ($row['arquivo_path']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-file-earmark-check"></i> RAT Anexado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-file-earmark-x"></i> Sem RAT
                                                    </span>
                                                <?php endif; ?>

                                                <?php
                                                    $statusClass = '';
                                                    $statusIcon = '';
                                                    switch($row['status_chamado']) {
                                                        case 'resolvido':
                                                            $statusClass = 'bg-success';
                                                            $statusIcon = 'bi-check-circle';
                                                            break;
                                                        case 'pendente':
                                                            $statusClass = 'bg-warning';
                                                            $statusIcon = 'bi-clock';
                                                            break;
                                                        case 'improdutivo':
                                                            $statusClass = 'bg-danger';
                                                            $statusIcon = 'bi-x-circle';
                                                            break;
                                                    }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <i class="bi <?php echo $statusIcon; ?>"></i> 
                                                    <?php echo ucfirst($row['status_chamado']); ?>
                                                </span>
                                            </div>
                                            <p class="card-text">
                                                <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($row['data_chamado'])); ?><br>
                                                <strong>Cliente:</strong> <?php echo htmlspecialchars($row['cliente']); ?><br>
                                                <strong>Tipo:</strong> <?php echo ucfirst($row['tipo_chamado']); ?><br>
                                                <strong>Patrimônios:</strong><br>
                                                <?php 
                                                    $patrimonios = json_decode($row['tipo_patrimonio'], true);
                                                    if ($patrimonios) {
                                                        foreach ($patrimonios as $p) {
                                                            echo "&nbsp;&nbsp;&nbsp;• " . htmlspecialchars($p['quantidade']) . " " . htmlspecialchars($p['tipo']) . "<br>";
                                                        }
                                                    } else {
                                                        echo "&nbsp;&nbsp;&nbsp;• " . htmlspecialchars($row['quantidade_patrimonios']) . " " . htmlspecialchars($row['tipo_patrimonio']) . "<br>";
                                                    }
                                                ?>
                                                <strong>Informante:</strong> <?php echo htmlspecialchars($row['nome_informante']); ?><br>
                                                <div class="mt-2 pt-2 border-top">
                                                    <div class="d-flex justify-content-between text-muted">
                                                        <small>
                                                            <i class="bi bi-speedometer2"></i> 
                                                            <?php echo $km_percorrido; ?> km percorridos
                                                        </small>
                                                        <small>
                                                            <i class="bi bi-clock"></i>
                                                            <?php 
                                                                if ($horas > 0) {
                                                                    echo $horas . 'h ';
                                                                }
                                                                echo $minutos . 'min';
                                                            ?> de atendimento
                                                        </small>
                                                    </div>
                                                </div>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#detalhesModal<?php echo $row['id']; ?>">
                                                    <i class="bi bi-eye"></i> Ver relatório
                                                </button>
                                                <?php if ($row['arquivo_path']): ?>
                                                    <a href="view-pdf.php?file=<?php echo urlencode($row['arquivo_path']); ?>" 
                                                       class="btn btn-info btn-sm" 
                                                       target="_blank">
                                                        <i class="bi bi-file-pdf"></i> Ver RAT
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de Detalhes -->
                                <div class="modal fade" id="detalhesModal<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-file-text me-2"></i>
                                                    Relatório #<?php echo htmlspecialchars($row['numero_chamado']); ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="list-group list-group-flush">
                                                    <!-- Informações do Chamado -->
                                                    <div class="list-group-item">
                                                        <h6 class="mb-3 text-primary">
                                                            <i class="bi bi-info-circle me-2"></i>Informações do Chamado
                                                        </h6>
                                                        <div class="ms-4">
                                                            <div class="row mb-2">
                                                                <div class="col-md-4">
                                                                    <p class="mb-1"><i class="bi bi-calendar me-2"></i><strong>Data:</strong></p>
                                                                    <p class="text-muted ms-4"><?php echo date('d/m/Y', strtotime($row['data_chamado'])); ?></p>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <p class="mb-1"><i class="bi bi-hash me-2"></i><strong>Número:</strong></p>
                                                                    <p class="text-muted ms-4"><?php echo htmlspecialchars($row['numero_chamado']); ?></p>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <p class="mb-1"><i class="bi bi-tag me-2"></i><strong>Tipo:</strong></p>
                                                                    <p class="text-muted ms-4"><?php echo ucfirst($row['tipo_chamado']); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-building me-2"></i><strong>Cliente:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['cliente']); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1">
                                                                        <i class="bi bi-check-circle me-2"></i><strong>Status:</strong>
                                                                    </p>
                                                                        <p class="text-muted ms-4"><?php echo ucfirst($row['status_chamado']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Informações do Cliente -->
                                                    <div class="list-group-item">
                                                        <h6 class="mb-3 text-primary">
                                                            <i class="bi bi-person me-2"></i>Informações do Cliente
                                                        </h6>
                                                        <div class="ms-4">
                                                            <div class="row mb-2">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-building me-2"></i><strong>Cliente atendido:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['cliente']); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-person-badge me-2"></i><strong>Quem atribuiu o chamado:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['nome_informante']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Informações de Deslocamento -->
                                                    <div class="list-group-item">
                                                        <h6 class="mb-3 text-primary">
                                                            <i class="bi bi-geo-alt me-2"></i>Informações de Deslocamento
                                                        </h6>
                                                        <div class="ms-4">
                                                            <div class="row mb-2">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-speedometer2 me-2"></i><strong>KM Inicial:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['km_inicial']); ?> km</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-speedometer me-2"></i><strong>KM Final:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['km_final']); ?> km</p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-clock me-2"></i><strong>Horário de chegada:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['hora_chegada']); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-clock-history me-2"></i><strong>Horário de saída:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['hora_saida']); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-arrow-right-circle me-2"></i><strong>Total percorrido:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo $km_percorrido; ?> km</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1"><i class="bi bi-stopwatch me-2"></i><strong>Tempo de atendimento:</strong></p>
                                                                        <p class="text-muted ms-4">
                                                                            <?php 
                                                                                if ($horas > 0) {
                                                                                    echo $horas . ' hora' . ($horas > 1 ? 's' : '') . ' e ';
                                                                                }
                                                                                echo $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
                                                                            ?>
                                                                        </p>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <p class="mb-1"><i class="bi bi-geo me-2"></i><strong>Endereço de Partida:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['endereco_partida']); ?></p>
                                                                </div>
                                                                <div class="col-6">
                                                                    <p class="mb-1"><i class="bi bi-geo-fill me-2"></i><strong>Endereço de Chegada:</strong></p>
                                                                        <p class="text-muted ms-4"><?php echo htmlspecialchars($row['endereco_chegada']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Informações do Serviço -->
                                                    <div class="list-group-item">
                                                        <h6 class="mb-3 text-primary">
                                                            <i class="bi bi-tools me-2"></i>Informações do Atendimento Realizado
                                                        </h6>
                                                        <div class="ms-4">
                                                            <div class="row mb-2">
                                                                <div class="col-md-6">
                                                                    <p class="mb-1">
                                                                        <i class="bi bi-boxes me-2"></i>
                                                                        <strong>Patrimônios Tratados:</strong>
                                                                    </p>
                                                                    <div class="ms-4">
                                                                        <?php 
                                                                            $patrimonios = json_decode($row['tipo_patrimonio'], true);
                                                                            if ($patrimonios) {
                                                                                foreach ($patrimonios as $p) {
                                                                                    echo "<p class='text-muted mb-1'>• " . htmlspecialchars($p['quantidade']) . " " . htmlspecialchars($p['tipo']) . "</p>";
                                                                                }
                                                                                echo "<p class='text-muted mt-2'><strong>Total:</strong> " . htmlspecialchars($row['quantidade_patrimonios']) . " patrimônios</p>";
                                                                            } else {
                                                                                echo "<p class='text-muted'>• " . htmlspecialchars($row['quantidade_patrimonios']) . " " . htmlspecialchars($row['tipo_patrimonio']) . "</p>";
                                                                            }
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p class="mb-1">
                                                                        <i class="bi bi-person-badge me-2"></i>
                                                                        <strong>Informante:</strong>
                                                                    </p>
                                                                    <p class="text-muted ms-4"><?php echo htmlspecialchars($row['nome_informante']); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <p class="mb-1">
                                                                        <i class="bi bi-card-text me-2"></i>
                                                                        <strong>Informações Adicionais:</strong>
                                                                    </p>
                                                                    <div class="p-3 bg-light rounded ms-4">
                                                                        <?php echo nl2br(htmlspecialchars($row['informacoes_adicionais'])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <?php if ($row['arquivo_path']): ?>
                                                    <a href="view-pdf.php?file=<?php echo urlencode($row['arquivo_path']); ?>" 
                                                       class="btn btn-info" 
                                                       target="_blank">
                                                        <i class="bi bi-file-pdf"></i> Abrir RAT
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="bi bi-x-circle me-1"></i>Fechar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="bi bi-journal-x"></i>
                                        <h4>Nenhum relatório encontrado</h4>
                                        <p>Não há relatórios que correspondam aos critérios de busca.</p>
                                    </div>
                                </div>
                            <?php
                                endif;
                                $result->free();
                                $conn->close();
                            ?>
                        </div>
                        <a href="exportar-relatorios.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="bi bi-file-excel"></i> Exportar para Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
</a>

<?php include_once '../includes/footer.php'; ?>

<!-- Scripts específicos da página -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const filterDate = document.getElementById('filterDate');
        const filterUser = document.getElementById('filterUser');
        const filterCliente = document.getElementById('filterCliente');
        const cards = document.querySelectorAll('.script-card-container');

        function getDateFromText(text) {
            const match = text.match(/(\d{2})\/(\d{2})\/(\d{4})/);
            if (match) {
                return {
                    day: match[1],
                    month: match[2],
                    year: match[3]
                };
            }
            return null;
        }

        function filterCards() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedDate = filterDate.value;
            const selectedUser = filterUser.value;
            const selectedCliente = filterCliente.value.toLowerCase();

            cards.forEach(card => {
                // Busca o texto do cartão para pesquisa geral
                const cardText = card.textContent.toLowerCase();
                
                // Extrai a data do cartão
                const dateElement = Array.from(card.querySelectorAll('.card-text strong')).find(el => el.textContent.includes('Data:'));
                let formattedCardDate = '';
                if (dateElement) {
                    const dateText = dateElement.nextSibling.textContent.trim();
                    const dateInfo = getDateFromText(dateText);
                    if (dateInfo) {
                        formattedCardDate = `${dateInfo.year}-${dateInfo.month}-${dateInfo.day}`;
                    }
                }
                
                // Extrai o nome do usuário
                const userBadge = card.querySelector('.badge.bg-primary');
                const userName = userBadge ? userBadge.textContent.trim() : '';
                
                // Extrai o nome do cliente
                const clienteElement = Array.from(card.querySelectorAll('.card-text strong')).find(el => el.textContent.includes('Cliente:'));
                const clienteName = clienteElement ? clienteElement.nextSibling.textContent.trim().toLowerCase() : '';

                // Aplica os filtros
                const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
                const matchesDate = !selectedDate || formattedCardDate === selectedDate;
                const matchesUser = !selectedUser || (userName.toLowerCase().includes(filterUser.options[filterUser.selectedIndex].text.toLowerCase()));
                const matchesCliente = !selectedCliente || clienteName.includes(selectedCliente);

                // Mostra ou esconde o cartão baseado nos filtros
                card.style.display = (matchesSearch && matchesDate && matchesUser && matchesCliente) ? '' : 'none';
            });

            updateEmptyState();
        }

        function updateEmptyState() {
            const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
            const container = document.getElementById('relatoriosContainer');
            const existingEmptyState = container.querySelector('.empty-state');

            if (visibleCards.length === 0) {
                if (!existingEmptyState) {
                    const emptyStateDiv = document.createElement('div');
                    emptyStateDiv.className = 'col-12 empty-state';
                    emptyStateDiv.innerHTML = `
                        <i class="bi bi-search"></i>
                        <h4>Nenhum resultado encontrado</h4>
                        <p>Tente ajustar seus filtros de pesquisa.</p>
                    `;
                    container.appendChild(emptyStateDiv);
                }
            } else if (existingEmptyState) {
                existingEmptyState.remove();
            }
        }

        // Adiciona os event listeners
        searchInput.addEventListener('input', filterCards);
        filterDate.addEventListener('change', filterCards);
        filterUser.addEventListener('change', filterCards);
        filterCliente.addEventListener('input', filterCards);

        // Aplica os filtros inicialmente
        filterCards();
    });
</script>
</body>
</html>