<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

$is_page = true;
require_once '../includes/upload_functions.php';
require_once '../includes/db.php';

// Buscar dados do usuário logado
$user = isset($_SESSION['user']) ? $_SESSION['user'] : ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];

if (isset($user['id'])) {
    $userId = $user['id'];
    
    // Buscar reembolsos do usuário
    $stmt = $conn->prepare("SELECT r.*, u.name as user_name, u.profile_image 
                           FROM reembolsos r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.user_id = ? 
                           ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $reembolsos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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

<!-- CSS Específico da Página -->
<main id="main" class="main">
  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="pagetitle">
              <h1>Meus Reembolsos</h1>
              <nav>
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                  <li class="breadcrumb-item active">Meus Reembolsos</li>
                </ol>
              </nav>
            </div>

            <!-- Seção de Filtros -->
            <div class="filter-section mb-4">
              <div class="row">
                <div class="col-md-4">
                  <input type="text" class="form-control" id="searchInput" placeholder="Pesquisar reembolsos...">
                </div>
                <div class="col-md-3">
                  <select class="form-select" id="statusFilter">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes</option>
                    <option value="aprovado">Aprovados</option>
                    <option value="criticado">Criticados</option>
                    <option value="reprovado">Reprovados</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <select class="form-select" id="tipoFilter">
                    <option value="">Todos os Tipos</option>
                    <option value="estacionamento">Estacionamento</option>
                    <option value="pedagio">Pedágio</option>
                    <option value="alimentacao">Alimentação</option>
                    <option value="transporte">Transporte</option>
                    <option value="hospedagem">Hospedagem</option>
                    <option value="outros">Outros</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <a href="solicitar-reembolso.php" class="btn btn-primary w-100">
                    <i class="bi bi-plus-circle"></i> Novo
                  </a>
                </div>
              </div>
            </div>

            <?php if (empty($reembolsos)): ?>
              <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                Você ainda não possui solicitações de reembolso.
                <br>
                <a href="solicitar-reembolso.php" class="btn btn-primary mt-3">
                  Criar Nova Solicitação
                </a>
              </div>
            <?php else: ?>
              <div class="row" id="reembolsosContainer">
                <?php foreach ($reembolsos as $reembolso): ?>
                  <div class="col-md-6 mb-4 reembolso-item" 
                       data-status="<?php echo $reembolso['status']; ?>"
                       data-tipo="<?php echo $reembolso['tipo_reembolso']; ?>">
                    <div class="card reembolso-card h-100">
                      <div class="card-body position-relative">
                        <!-- Badge de Status -->
                        <span class="badge bg-<?php echo getStatusClass($reembolso['status']); ?> status-badge">
                          <i class="bi <?php echo getStatusIcon($reembolso['status']); ?>"></i>
                          <?php echo ucfirst($reembolso['status']); ?>
                        </span>

                        <h5 class="card-title">
                          Reembolso #<?php echo $reembolso['id']; ?>
                          <small class="text-muted">
                            (<?php echo ucfirst($reembolso['tipo_reembolso']); ?>)
                          </small>
                        </h5>

                        <div class="row mb-3">
                          <div class="col-md-6">
                            <strong>Data do Gasto:</strong>
                            <br>
                            <?php echo date('d/m/Y', strtotime($reembolso['data_chamado'])); ?>
                          </div>
                          <div class="col-md-6">
                            <strong>Valor:</strong>
                            <br>
                            R$ <?php echo number_format($reembolso['valor'], 2, ',', '.'); ?>
                          </div>
                        </div>

                        <?php if ($reembolso['numero_chamado']): ?>
                          <p><strong>Chamado:</strong> <?php echo htmlspecialchars($reembolso['numero_chamado']); ?></p>
                        <?php endif; ?>

                        <p><strong>Descrição:</strong> <?php echo htmlspecialchars($reembolso['informacoes_adicionais']); ?></p>

                        <?php if ($reembolso['comentario_admin']): ?>
                          <div class="alert alert-<?php echo $reembolso['status'] === 'criticado' ? 'info' : 'danger'; ?> mt-3">
                            <strong>Feedback do Administrador:</strong>
                            <br>
                            <?php echo htmlspecialchars($reembolso['comentario_admin']); ?>
                          </div>
                        <?php endif; ?>

                        <!-- Arquivos/Comprovantes -->
                        <?php if ($reembolso['arquivo_path']): ?>
                          <div class="arquivos-container">
                            <?php 
                            $arquivos = explode(',', $reembolso['arquivo_path']);
                            foreach($arquivos as $arquivo):
                              // Corrigir o caminho para funcionar com Ngrok
                              $arquivo_url = str_replace('soudigital/', '', "/{$arquivo}");
                              $nome = basename($arquivo);
                              $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
                              
                              if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                            ?>
                              <a href="<?php echo $arquivo_url; ?>" target="_blank" class="m-2">
                                <img src="<?php echo $arquivo_url; ?>" class="arquivo-preview" alt="Comprovante">
                              </a>
                            <?php else: ?>
                              <a href="<?php echo $arquivo_url; ?>" target="_blank" class="btn btn-outline-primary m-2" 
                                 style="min-width: 120px; min-height: 100px; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                                 <?php if ($ext === 'pdf'): ?>data-type="application/pdf" rel="noopener noreferrer"<?php endif; ?>>
                                <i class="bi bi-file-earmark-<?php echo $ext === 'pdf' ? 'pdf-fill text-danger' : 'text-fill text-primary'; ?>" style="font-size: 2.5em;"></i>
                                <span class="mt-2"><?php echo $ext === 'pdf' ? 'Ver PDF' : 'Ver Arquivo'; ?></span>
                              </a>
                            <?php 
                              endif;
                            endforeach;
                            ?>
                          </div>
                        <?php endif; ?>

                        <!-- Botões de Ação -->
                        <div class="mt-3">
                          <?php if ($reembolso['status'] === 'criticado'): ?>
                            <a href="editar-reembolso.php?id=<?php echo $reembolso['id']; ?>" class="btn btn-primary btn-sm">
                              <i class="bi bi-pencil"></i> Editar Solicitação
                            </a>
                          <?php endif; ?>
                        </div>
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

<?php include_once '../includes/footer.php'; ?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tipoFilter = document.getElementById('tipoFilter');
    const reembolsosContainer = document.getElementById('reembolsosContainer');

    // Melhorar a exibição de PDFs
    document.querySelectorAll('a[data-type="application/pdf"]').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');
        
        // Tentar abrir em uma nova janela com tamanho específico
        const pdfWindow = window.open(url, '_blank', 'width=800,height=600,toolbar=0,menubar=0,location=0');
        
        // Fallback se o pop-up for bloqueado
        if (!pdfWindow || pdfWindow.closed || typeof pdfWindow.closed === 'undefined') {
          // Usar o método normal de abrir em nova aba
          window.open(url, '_blank', 'noopener,noreferrer');
        }
      });
    });

    function filterReembolsos() {
      const searchTerm = searchInput.value.toLowerCase();
      const statusTerm = statusFilter.value.toLowerCase();
      const tipoTerm = tipoFilter.value.toLowerCase();
      const reembolsos = document.querySelectorAll('.reembolso-item');

      reembolsos.forEach(reembolso => {
        const text = reembolso.textContent.toLowerCase();
        const status = reembolso.dataset.status;
        const tipo = reembolso.dataset.tipo;

        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusTerm || status === statusTerm;
        const matchesTipo = !tipoTerm || tipo === tipoTerm;

        reembolso.style.display = (matchesSearch && matchesStatus && matchesTipo) ? '' : 'none';
      });

      // Mostrar mensagem quando não houver resultados
      const visibleReembolsos = document.querySelectorAll('.reembolso-item[style=""]').length;
      const noResultsMessage = document.querySelector('.no-results-message');
      
      if (visibleReembolsos === 0) {
        if (!noResultsMessage) {
          const message = document.createElement('div');
          message.className = 'alert alert-info text-center no-results-message';
          message.innerHTML = 'Nenhum reembolso encontrado com os filtros selecionados.';
          reembolsosContainer.appendChild(message);
        }
      } else if (noResultsMessage) {
        noResultsMessage.remove();
      }
    }

    searchInput.addEventListener('input', filterReembolsos);
    statusFilter.addEventListener('change', filterReembolsos);
    tipoFilter.addEventListener('change', filterReembolsos);
  });
</script> 