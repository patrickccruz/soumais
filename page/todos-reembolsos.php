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

// Buscar todos os reembolsos (visão de administrador)
$sql = "SELECT r.*, u.name as user_name, u.email as user_email 
        FROM reembolsos r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);
$reembolsos = $result->fetch_all(MYSQLI_ASSOC);

include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<style>
  .reembolso-card {
      transition: transform 0.2s;
  }
  .reembolso-card:hover {
      transform: translateY(-5px);
  }
  .status-badge {
      position: absolute;
      top: 10px;
      right: 10px;
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
  .filter-section {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
  }
</style>

<main id="main" class="main">
    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <div class="pagetitle">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div>
                    <h1>Todos os Reembolsos</h1>
                    <nav>
                      <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                        <li class="breadcrumb-item">Administração</li>
                        <li class="breadcrumb-item active">Todos os Reembolsos</li>
                      </ol>
                    </nav>
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" id="refreshBtn">
                      <i class="bi bi-arrow-clockwise"></i> Atualizar
                    </button>
                    <div class="dropdown">
                      <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> Exportar
                      </button>
                      <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="exportExcel"><i class="bi bi-file-earmark-excel"></i> Excel</a></li>
                        <li><a class="dropdown-item" href="#" id="exportPDF"><i class="bi bi-file-earmark-pdf"></i> PDF</a></li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Seção de Filtros -->
              <div class="filter-section mb-4">
                <div class="row">
                  <div class="col-md-3">
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
                  <div class="col-md-3">
                    <select class="form-select" id="monthFilter">
                      <option value="">Todos os Meses</option>
                      <?php
                      $meses = [
                          1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
                          4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                          7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
                          10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                      ];
                      foreach ($meses as $num => $nome) {
                          echo "<option value='$num'>$nome</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
              </div>

              <?php if (empty($reembolsos)): ?>
                <div class="alert alert-info text-center">
                  <i class="bi bi-info-circle me-2"></i>
                  Não há reembolsos no sistema.
                </div>
              <?php else: ?>
                <div class="row" id="reembolsosContainer">
                  <?php foreach ($reembolsos as $reembolso): ?>
                    <div class="col-md-6 mb-4 reembolso-item" 
                         data-status="<?php echo $reembolso['status']; ?>"
                         data-tipo="<?php echo $reembolso['tipo_reembolso']; ?>"
                         data-month="<?php echo date('n', strtotime($reembolso['data_chamado'])); ?>">
                      <div class="card reembolso-card h-100">
                        <div class="card-body">
                          <h5 class="card-title d-flex justify-content-between align-items-center">
                            <span>
                              Reembolso #<?php echo $reembolso['id']; ?>
                              <small class="text-muted">
                                (<?php echo ucfirst($reembolso['tipo_reembolso']); ?>)
                              </small>
                            </span>
                            <span class="badge bg-<?php echo getStatusClass($reembolso['status']); ?>">
                              <i class="bi <?php echo getStatusIcon($reembolso['status']); ?>"></i>
                              <?php echo ucfirst($reembolso['status']); ?>
                            </span>
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

                          <?php if ($reembolso['comentario_admin']): ?>
                            <div class="alert alert-<?php echo $reembolso['status'] === 'criticado' ? 'info' : ($reembolso['status'] === 'reprovado' ? 'danger' : 'success'); ?> mt-3">
                              <strong><i class="bi bi-chat-dots"></i> Feedback do Administrador:</strong>
                              <br>
                              <?php echo htmlspecialchars($reembolso['comentario_admin']); ?>
                            </div>
                          <?php endif; ?>

                          <?php if ($reembolso['status'] === 'pendente'): ?>
                            <hr>
                            <div class="d-flex gap-2 justify-content-center">
                              <a href="gerenciar-reembolsos.php" class="btn btn-primary">
                                <i class="bi bi-gear"></i> Gerenciar
                              </a>
                            </div>
                          <?php endif; ?>
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

  <!-- Filtro e Pesquisa Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      const statusFilter = document.getElementById('statusFilter');
      const tipoFilter = document.getElementById('tipoFilter');
      const monthFilter = document.getElementById('monthFilter');
      const reembolsosContainer = document.getElementById('reembolsosContainer');
      const refreshBtn = document.getElementById('refreshBtn');

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

      // Função para atualizar a página
      refreshBtn.addEventListener('click', function() {
        location.reload();
      });

      function filterReembolsos() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusTerm = statusFilter.value.toLowerCase();
        const tipoTerm = tipoFilter.value.toLowerCase();
        const monthTerm = monthFilter.value;
        const reembolsos = document.querySelectorAll('.reembolso-item');

        reembolsos.forEach(reembolso => {
          const text = reembolso.textContent.toLowerCase();
          const status = reembolso.dataset.status;
          const tipo = reembolso.dataset.tipo;
          const month = reembolso.dataset.month;

          const matchesSearch = text.includes(searchTerm);
          const matchesStatus = !statusTerm || status === statusTerm;
          const matchesTipo = !tipoTerm || tipo === tipoTerm;
          const matchesMonth = !monthTerm || month === monthTerm;

          reembolso.style.display = (matchesSearch && matchesStatus && matchesTipo && matchesMonth) ? '' : 'none';
        });

        // Mostrar mensagem quando não houver resultados
        const visibleReembolsos = document.querySelectorAll('.reembolso-item[style=""]').length;
        const noResultsMessage = document.querySelector('.no-results-message');
        
        if (visibleReembolsos === 0) {
          if (!noResultsMessage) {
            const message = document.createElement('div');
            message.className = 'alert alert-info text-center no-results-message';
            message.innerHTML = `
              <i class="bi bi-info-circle me-2"></i>
              Nenhum reembolso encontrado com os filtros selecionados.
              <br>
              <button class="btn btn-primary mt-3" onclick="clearFilters()">
                <i class="bi bi-arrow-counterclockwise"></i> Limpar Filtros
              </button>
            `;
            reembolsosContainer.appendChild(message);
          }
        } else if (noResultsMessage) {
          noResultsMessage.remove();
        }
      }

      // Função para limpar filtros
      window.clearFilters = function() {
        searchInput.value = '';
        statusFilter.value = '';
        tipoFilter.value = '';
        monthFilter.value = '';
        filterReembolsos();
      }

      // Event listeners
      searchInput.addEventListener('input', filterReembolsos);
      statusFilter.addEventListener('change', filterReembolsos);
      tipoFilter.addEventListener('change', filterReembolsos);
      monthFilter.addEventListener('change', filterReembolsos);

      // Exportação
      document.getElementById('exportExcel').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Funcionalidade de exportação para Excel será implementada em breve!');
      });

      document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Funcionalidade de exportação para PDF será implementada em breve!');
      });
    });
  </script>
</body>
</html> 