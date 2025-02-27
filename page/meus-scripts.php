<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

$is_page = true;
include_once '../includes/header.php';

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    $user = ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
}

// Conexão com o banco de dados
include_once '../includes/db.php';

// Buscar os scripts do usuário
$stmt = $conn->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY data_chamado DESC");
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param("i", $user['id']);
if (!$stmt->execute()) {
    die("Erro na execução da consulta: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!-- Estilos específicos da página -->
<style>
    .script-card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }
    
    .script-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    .script-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #4154f1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .script-card:hover::before {
        opacity: 1;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #012970;
        margin-bottom: 1.5rem;
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

    .badge {
        padding: 0.5em 0.8em;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .badge:hover {
        transform: scale(1.05);
    }

    .filter-section {
        background: linear-gradient(to right, #f6f9ff, #ffffff);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .search-box {
        position: relative;
        margin-bottom: 20px;
    }

    .search-box input {
        padding-left: 40px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .search-box input:focus {
        border-color: #4154f1;
        box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.25);
    }

    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .form-select:focus {
        border-color: #4154f1;
        box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.25);
    }

    .card-info {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
        color: #6c757d;
    }

    .card-info i {
        margin-right: 8px;
        font-size: 0.9rem;
    }

    .card-footer {
        background: transparent;
        border-top: 1px solid rgba(0,0,0,0.05);
        padding: 1rem;
    }

    .btn {
        border-radius: 6px;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn i {
        font-size: 0.9rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background: #f8f9fa;
        border-radius: 12px;
        margin: 2rem 0;
    }

    .empty-state i {
        font-size: 3rem;
        color: #4154f1;
        margin-bottom: 1rem;
    }

    .empty-state h4 {
        color: #012970;
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: #6c757d;
        margin-bottom: 1.5rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .script-card-container {
        animation: fadeIn 0.5s ease forwards;
    }

    /* Melhorias para o modal */
    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .modal-header {
        border-radius: 12px 12px 0 0;
        background: linear-gradient(135deg, #4154f1, #2536b8);
        padding: 1.5rem;
    }

    .modal-title {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .list-group-item {
        border: none;
        padding: 1.2rem;
        margin-bottom: 0.5rem;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .list-group-item h6 {
        color: #4154f1;
        font-weight: 600;
    }

    .form-control:disabled, .form-select:disabled {
        background-color: #f8f9fa;
        opacity: 1;
    }
</style>

<?php include_once '../includes/sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Meus Lançamentos</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                <li class="breadcrumb-item active">Meus Lançamentos</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Filtros e Pesquisa -->
                        <div class="filter-section">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-1"></i>
                                    <?php 
                                        echo $_SESSION['success_message'];
                                        unset($_SESSION['success_message']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-octagon me-1"></i>
                                    <?php 
                                        echo $_SESSION['error_message'];
                                        unset($_SESSION['error_message']);
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="search-box">
                                        <i class="bi bi-search"></i>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar scripts...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterMonth">
                                        <option value="">Todos os Meses</option>
                                        <option value="1">Janeiro</option>
                                        <option value="2">Fevereiro</option>
                                        <option value="3">Março</option>
                                        <option value="4">Abril</option>
                                        <option value="5">Maio</option>
                                        <option value="6">Junho</option>
                                        <option value="7">Julho</option>
                                        <option value="8">Agosto</option>
                                        <option value="9">Setembro</option>
                                        <option value="10">Outubro</option>
                                        <option value="11">Novembro</option>
                                        <option value="12">Dezembro</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filterYear">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
                                            echo "<option value='$year'>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Cards dos Scripts -->
                        <div class="row" id="scriptsContainer">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): 
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
                                                    Chamado # <?php echo htmlspecialchars($row['numero_chamado']); ?>
                                                </h5>
                                                <div class="status-badge">
                                                    <?php if ($row['arquivo_path']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-file-earmark-check"></i> RAT
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="bi bi-file-earmark-x"></i> RAT Pendente
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
                                                
                                                <div class="card-info">
                                                    <i class="bi bi-calendar"></i>
                                                    <?php echo date('d/m/Y', strtotime($row['data_chamado'])); ?>
                                                </div>
                                                
                                                <div class="card-info">
                                                    <i class="bi bi-building"></i>
                                                    <?php echo htmlspecialchars($row['cliente']); ?>
                                                </div>
                                                
                                                <div class="card-info">
                                                    <i class="bi bi-tag"></i>
                                                    <?php echo ucfirst($row['tipo_chamado']); ?>
                                                </div>

                                                <div class="card-info">
                                                    <i class="bi bi-geo"></i>
                                                    <?php 
                                                        $enderecoPartida = explode(',', $row['endereco_partida'])[0];
                                                        echo htmlspecialchars($enderecoPartida); 
                                                    ?>
                                                </div>

                                                <div class="card-info">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <?php 
                                                        $enderecoChegada = explode(',', $row['endereco_chegada'])[0];
                                                        echo htmlspecialchars($enderecoChegada); 
                                                    ?>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="d-flex justify-content-between align-items-center text-muted small">
                                                        <span>
                                                            <i class="bi bi-speedometer2"></i> 
                                                            <?php echo $km_percorrido; ?> km
                                                        </span>
                                                        <span>
                                                            <i class="bi bi-clock"></i>
                                                            <?php 
                                                                if ($horas > 0) echo $horas . 'h ';
                                                                echo $minutos . 'min';
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#detalhesModal<?php echo $row['id']; ?>">
                                                        <i class="bi bi-eye"></i> Detalhes
                                                    </button>
                                                    <?php if ($row['arquivo_path']): ?>
                                                        <a href="view-pdf.php?file=<?php echo urlencode($row['arquivo_path']); ?>" 
                                                           class="btn btn-info btn-sm" 
                                                           target="_blank">
                                                            <i class="bi bi-file-pdf"></i> RAT
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
                                                        Lançamento de script #<?php echo htmlspecialchars($row['numero_chamado']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form id="editForm<?php echo $row['id']; ?>" method="POST" action="atualizar-script.php" enctype="multipart/form-data">
                                                    <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3 d-flex justify-content-end">
                                                            <button type="button" class="btn btn-warning btn-sm me-2" id="btnEdit<?php echo $row['id']; ?>" onclick="toggleEdit(<?php echo $row['id']; ?>)">
                                                                <i class="bi bi-pencil"></i> Editar
                                                            </button>
                                                            <button type="submit" class="btn btn-success btn-sm d-none" id="btnSave<?php echo $row['id']; ?>">
                                                                <i class="bi bi-check-lg"></i> Salvar
                                                            </button>
                                                        </div>
                                                        <div class="list-group list-group-flush">
                                                            <!-- Informações do Chamado -->
                                                            <div class="list-group-item">
                                                                <h6 class="mb-3 text-primary">
                                                                    <i class="bi bi-info-circle me-2"></i>Informações do Chamado
                                                                </h6>
                                                                <div class="ms-4">
                                                                    <div class="row mb-2">
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Data do Chamado</label>
                                                                                <input type="date" class="form-control" name="data_chamado" value="<?php echo $row['data_chamado']; ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Número do Chamado</label>
                                                                                <input type="text" class="form-control" name="numero_chamado" value="<?php echo htmlspecialchars($row['numero_chamado']); ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Tipo do Chamado</label>
                                                                                <input type="text" class="form-control" value="<?php echo ucfirst($row['tipo_chamado']); ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mb-2">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Cliente</label>
                                                                                <input type="text" class="form-control" name="cliente" value="<?php echo htmlspecialchars($row['cliente']); ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Informante</label>
                                                                                <input type="text" class="form-control" name="nome_informante" value="<?php echo htmlspecialchars($row['nome_informante']); ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Patrimônios -->
                                                            <div class="list-group-item">
                                                                <h6 class="mb-3 text-primary">
                                                                    <i class="bi bi-box-seam me-2"></i>Patrimônios
                                                                </h6>
                                                                <div class="ms-4" id="patrimoniosContainer<?php echo $row['id']; ?>">
                                                                    <?php 
                                                                        $patrimonios = json_decode($row['tipo_patrimonio'], true);
                                                                        if ($patrimonios) {
                                                                            foreach ($patrimonios as $index => $patrimonio) {
                                                                                echo '<div class="row mb-2 patrimonio-row">';
                                                                                echo '<div class="col-md-5">';
                                                                                echo '<div class="form-group">';
                                                                                echo '<label class="form-label">Quantidade</label>';
                                                                                echo '<input type="number" class="form-control" name="quantidade_patrimonio[]" value="' . htmlspecialchars($patrimonio['quantidade']) . '" disabled>';
                                                                                echo '</div></div>';
                                                                                echo '<div class="col-md-5">';
                                                                                echo '<div class="form-group">';
                                                                                echo '<label class="form-label">Tipo</label>';
                                                                                echo '<input type="text" class="form-control" name="tipo_patrimonio[]" value="' . htmlspecialchars($patrimonio['tipo']) . '" disabled>';
                                                                                echo '</div></div>';
                                                                                echo '<div class="col-md-2 d-flex align-items-end">';
                                                                                if ($index === 0) {
                                                                                    echo '<button type="button" class="btn btn-success btn-sm add-patrimonio" style="display:none;" onclick="addPatrimonio(' . $row['id'] . ')">';
                                                                                    echo '<i class="bi bi-plus-lg"></i>';
                                                                                    echo '</button>';
                                                                                } else {
                                                                                    echo '<button type="button" class="btn btn-danger btn-sm remove-patrimonio" style="display:none;" onclick="removePatrimonio(this)">';
                                                                                    echo '<i class="bi bi-trash"></i>';
                                                                                    echo '</button>';
                                                                                }
                                                                                echo '</div></div>';
                                                                            }
                                                                        }
                                                                    ?>
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
                                                                            <div class="form-group">
                                                                                <label class="form-label">KM Inicial</label>
                                                                                <input type="number" class="form-control" value="<?php echo $row['km_inicial']; ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">KM Final</label>
                                                                                <input type="number" class="form-control" value="<?php echo $row['km_final']; ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mb-2">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Hora de Chegada</label>
                                                                                <input type="time" class="form-control" value="<?php echo $row['hora_chegada']; ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Hora de Saída</label>
                                                                                <input type="time" class="form-control" value="<?php echo $row['hora_saida']; ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mb-2">
                                                                        <div class="col-12">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Endereço de Partida</label>
                                                                                <input type="text" class="form-control" name="endereco_partida" value="<?php echo htmlspecialchars($row['endereco_partida']); ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mb-2">
                                                                        <div class="col-12">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Endereço de Chegada</label>
                                                                                <input type="text" class="form-control" name="endereco_chegada" value="<?php echo htmlspecialchars($row['endereco_chegada']); ?>" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Status e Informações Adicionais -->
                                                            <div class="list-group-item">
                                                                <h6 class="mb-3 text-primary">
                                                                    <i class="bi bi-clipboard-check me-2"></i>Status e Informações Adicionais
                                                                </h6>
                                                                <div class="ms-4">
                                                                    <div class="row mb-2">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Status do Chamado</label>
                                                                                <select class="form-select" name="status_chamado" disabled>
                                                                                    <option value="resolvido" <?php echo $row['status_chamado'] == 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                                                                                    <option value="pendente" <?php echo $row['status_chamado'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                                                                    <option value="improdutivo" <?php echo $row['status_chamado'] == 'improdutivo' ? 'selected' : ''; ?>>Improdutivo</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Informações Adicionais</label>
                                                                                <textarea class="form-control" name="informacoes_adicionais" rows="4" disabled><?php echo htmlspecialchars($row['informacoes_adicionais']); ?></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Upload de RAT -->
                                                            <div class="list-group-item">
                                                                <h6 class="mb-3 text-primary">
                                                                    <i class="bi bi-file-earmark-pdf me-2"></i>RAT (Relatório de Atendimento Técnico)
                                                                </h6>
                                                                <div class="ms-4">
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Anexar novo RAT (PDF)</label>
                                                                                <input type="file" class="form-control" name="arquivo" accept=".pdf" disabled>
                                                                            </div>
                                                                            <?php if ($row['arquivo_path']): ?>
                                                                                <div class="mt-2">
                                                                                    <p class="mb-1">RAT atual:</p>
                                                                                    <a href="view-pdf.php?file=<?php echo urlencode($row['arquivo_path']); ?>" 
                                                                                       class="btn btn-info btn-sm" 
                                                                                       target="_blank">
                                                                                        <i class="bi bi-file-pdf"></i> Visualizar RAT atual
                                                                                    </a>
                                                                                </div>
                                                                            <?php endif; ?>
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
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="bi bi-journal-x"></i>
                                        <h4>Nenhum script encontrado</h4>
                                        <p>Você ainda não criou nenhum script. Clique no botão abaixo para criar seu primeiro script.</p>
                                        <a href="gerar-script.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Criar Novo Script
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
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
        const filterMonth = document.getElementById('filterMonth');
        const filterYear = document.getElementById('filterYear');
        const cards = document.querySelectorAll('.script-card-container');

        function filterCards() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedMonth = filterMonth.value;
            const selectedYear = filterYear.value;

            cards.forEach(card => {
                const cardText = card.textContent.toLowerCase();
                const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
                const dateElement = card.querySelector('.card-info i.bi-calendar').parentElement;
                const dateText = dateElement.textContent.trim();
                const [day, month, year] = dateText.split('/');
                
                const matchesMonth = !selectedMonth || parseInt(month) === parseInt(selectedMonth);
                const matchesYear = !selectedYear || year === selectedYear;

                card.style.display = (matchesSearch && matchesMonth && matchesYear) ? '' : 'none';
            });

            updateEmptyState();
        }

        function updateEmptyState() {
            const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
            const container = document.getElementById('scriptsContainer');
            const existingEmptyState = container.querySelector('.empty-state');

            if (visibleCards.length === 0) {
                if (!existingEmptyState) {
                    const emptyStateDiv = document.createElement('div');
                    emptyStateDiv.className = 'col-12';
                    emptyStateDiv.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-search"></i>
                            <h4>Nenhum resultado encontrado</h4>
                            <p>Tente ajustar seus filtros de pesquisa.</p>
                        </div>
                    `;
                    container.appendChild(emptyStateDiv);
                }
            } else if (existingEmptyState) {
                existingEmptyState.remove();
            }
        }

        searchInput.addEventListener('input', filterCards);
        filterMonth.addEventListener('change', filterCards);
        filterYear.addEventListener('change', filterCards);
    });
</script>

<script>
    function toggleEdit(id) {
        const form = document.getElementById('editForm' + id);
        const btnEdit = document.getElementById('btnEdit' + id);
        const btnSave = document.getElementById('btnSave' + id);
        const inputs = form.querySelectorAll('input, select, textarea');
        const addPatrimonioBtn = form.querySelector('.add-patrimonio');
        const removePatrimonioBtns = form.querySelectorAll('.remove-patrimonio');
        
        if (btnEdit.innerHTML.includes('Editar')) {
            // Habilitar edição
            inputs.forEach(input => input.disabled = false);
            btnEdit.innerHTML = '<i class="bi bi-x"></i> Cancelar';
            btnEdit.classList.replace('btn-warning', 'btn-danger');
            btnSave.classList.remove('d-none');
            
            // Mostrar botões de adicionar/remover patrimônios
            if (addPatrimonioBtn) addPatrimonioBtn.style.display = 'block';
            removePatrimonioBtns.forEach(btn => btn.style.display = 'block');
        } else {
            // Desabilitar edição
            inputs.forEach(input => input.disabled = true);
            btnEdit.innerHTML = '<i class="bi bi-pencil"></i> Editar';
            btnEdit.classList.replace('btn-danger', 'btn-warning');
            btnSave.classList.add('d-none');
            
            // Esconder botões de adicionar/remover patrimônios
            if (addPatrimonioBtn) addPatrimonioBtn.style.display = 'none';
            removePatrimonioBtns.forEach(btn => btn.style.display = 'none');
        }
    }

    function addPatrimonio(reportId) {
        const container = document.getElementById('patrimoniosContainer' + reportId);
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 patrimonio-row';
        newRow.innerHTML = `
            <div class="col-md-5">
                <div class="form-group">
                    <label class="form-label">Quantidade</label>
                    <input type="number" class="form-control" name="quantidade_patrimonio[]" value="1">
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <input type="text" class="form-control" name="tipo_patrimonio[]" placeholder="Ex: Notebook, Desktop">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-patrimonio" onclick="removePatrimonio(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
    }

    function removePatrimonio(button) {
        const row = button.closest('.patrimonio-row');
        row.remove();
    }
</script>
</body>
</html> 