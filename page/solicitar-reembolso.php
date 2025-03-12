<?php
// Configuração de depuração para capturar erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Criar diretório de logs se não existir
$log_dir = dirname(__DIR__) . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}

// Arquivo de log personalizado
$error_log_file = $log_dir . '/reembolso_debug.log';
ini_set('error_log', $error_log_file);

// Registrar início da execução
$timestamp = date('Y-m-d H:i:s');
error_log("[$timestamp] Iniciando execução da página solicitar-reembolso.php");

// Verificar se as dependências necessárias estão disponíveis
if (!function_exists('process_file_upload')) {
    error_log("[$timestamp] ERRO: Função process_file_upload não encontrada");
}

// Verificar diretórios de upload
$upload_path = dirname(__DIR__) . '/uploads/reimbursements';
if (!is_dir($upload_path)) {
    error_log("[$timestamp] AVISO: Diretório de upload não existe: $upload_path");
    @mkdir($upload_path, 0777, true);
    error_log("[$timestamp] Tentativa de criar diretório: " . (is_dir($upload_path) ? "Sucesso" : "Falha"));
}

// Verificar permissões de diretório
if (is_dir($upload_path) && !is_writable($upload_path)) {
    error_log("[$timestamp] ERRO: Diretório de upload sem permissão de escrita: $upload_path");
}

try {
    session_start();
    ob_start(); // Inicia o buffer de saída
    require_once '../includes/upload_functions.php';

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
        header("Location: autenticacao.php");
        exit;
    }

    // Se for uma requisição AJAX/POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            error_log("[$timestamp] Tentando conectar ao banco de dados");
            
            $conn = new mysqli('localhost', 'root', '', 'sou_digital');
            if ($conn->connect_error) {
                error_log("[$timestamp] ERRO de conexão ao banco: " . $conn->connect_error);
                throw new Exception("Falha na conexão ao banco de dados: " . $conn->connect_error);
            }
            
            error_log("[$timestamp] Conexão ao banco de dados estabelecida com sucesso");
            
            $user = $_SESSION['user'];
            $dataChamado = $_POST['dataChamado'];
            $numeroChamado = !empty($_POST['numeroChamado']) ? intval($_POST['numeroChamado']) : null;
            $informacoesAdicionais = $_POST['informacoesAdicionais'];
            $valor = str_replace(',', '.', $_POST['valor']);
            $tipo_reembolso = $_POST['tipo_reembolso'];
            $status = 'pendente';
            
            error_log("[$timestamp] Dados recebidos: Data=$dataChamado, Número=$numeroChamado, Tipo=$tipo_reembolso, Valor=$valor");
            
            // Validações básicas
            if (empty($dataChamado) || empty($informacoesAdicionais) || empty($valor) || empty($tipo_reembolso)) {
                error_log("[$timestamp] ERRO: Campos obrigatórios não preenchidos");
                throw new Exception("Por favor, preencha todos os campos obrigatórios");
            }

            // Inserir reembolso
            $stmt = $conn->prepare("INSERT INTO reembolsos (user_id, data_chamado, numero_chamado, informacoes_adicionais, valor, tipo_reembolso, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                error_log("[$timestamp] ERRO ao preparar consulta de inserção: " . $conn->error);
                throw new Exception("Erro ao preparar consulta de reembolso");
            }
            
            $stmt->bind_param("isissss", $user['id'], $dataChamado, $numeroChamado, $informacoesAdicionais, $valor, $tipo_reembolso, $status);

            if (!$stmt->execute()) {
                error_log("[$timestamp] ERRO ao executar inserção do reembolso: " . $stmt->error);
                throw new Exception("Erro ao salvar reembolso: " . $stmt->error);
            }

            error_log("[$timestamp] Reembolso inserido com sucesso, ID: " . $conn->insert_id);
            $reembolso_id = $conn->insert_id;
            $arquivoPaths = array();

            // Fazer upload dos arquivos
            $has_files = false;
            $files_paths = [];
            
            if (isset($_FILES['arquivos']) && count($_FILES['arquivos']['name']) > 0) {
                error_log("[$timestamp] Processando " . count($_FILES['arquivos']['name']) . " arquivos");
                
                // Tipos de arquivos permitidos
                $allowed_types = [
                    'application/pdf', 
                    'application/x-pdf',
                    'image/jpeg', 
                    'image/png',
                    'image/jpg',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ];
                
                // Tamanho máximo (10MB)
                $max_size = 10 * 1024 * 1024;
                
                // Criar diretório para este reembolso
                $upload_dir = dirname(__DIR__) . '/uploads/reimbursements/' . $reembolso_id;
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        error_log("[$timestamp] ERRO: Falha ao criar diretório para reembolso: $upload_dir");
                        throw new Exception("Não foi possível criar o diretório para armazenar os arquivos");
                    }
                    error_log("[$timestamp] Diretório criado com sucesso: $upload_dir");
                }
                
                $log_file = dirname(__DIR__) . '/logs/reembolso_upload.log';
                
                error_log("[$timestamp] === PROCESSANDO ARQUIVOS REEMBOLSO ID: $reembolso_id ===");
                
                for ($i = 0; $i < count($_FILES['arquivos']['name']); $i++) {
                    // Verificar se há arquivo selecionado
                    if (empty($_FILES['arquivos']['name'][$i])) {
                        continue;
                    }
                    
                    error_log("[$timestamp] Processando arquivo " . ($i+1) . ": " . $_FILES['arquivos']['name'][$i]);
                    
                    // Verificar erro de upload
                    if ($_FILES['arquivos']['error'][$i] !== UPLOAD_ERR_OK) {
                        $error_code = $_FILES['arquivos']['error'][$i];
                        $error_message = "Erro no upload do arquivo " . ($i+1) . " (código: $error_code)";
                        error_log("[$timestamp] $error_message");
                        continue;
                    }
                    
                    // Criar estrutura para usar com process_file_upload
                    $file = [
                        'name' => $_FILES['arquivos']['name'][$i],
                        'type' => $_FILES['arquivos']['type'][$i],
                        'tmp_name' => $_FILES['arquivos']['tmp_name'][$i],
                        'error' => $_FILES['arquivos']['error'][$i],
                        'size' => $_FILES['arquivos']['size'][$i]
                    ];
                    
                    $destination = $upload_dir . '/reembolso_' . uniqid();
                    
                    error_log("[$timestamp] Destino do arquivo: $destination");
                    
                    // Verificar se a função existe
                    if (!function_exists('process_file_upload')) {
                        error_log("[$timestamp] ERRO FATAL: Função process_file_upload não está disponível");
                        throw new Exception("Sistema de upload não está configurado corretamente");
                    }
                    
                    // Processar upload
                    try {
                        $result = process_file_upload(
                            $file,
                            $destination,
                            $allowed_types,
                            $max_size,
                            'reembolso_',
                            $log_file
                        );
                        
                        if ($result['success']) {
                            $has_files = true;
                            $files_paths[] = $result['path'];
                            
                            // Salvar referência do arquivo no banco
                            $stmt = $conn->prepare("INSERT INTO reembolso_arquivos (reembolso_id, arquivo_path) VALUES (?, ?)");
                            $stmt->bind_param("is", $reembolso_id, $result['path']);
                            if (!$stmt->execute()) {
                                error_log("[$timestamp] ERRO ao inserir registro de arquivo no banco: " . $stmt->error);
                            }
                            
                            error_log("[$timestamp] Arquivo processado com sucesso: " . $result['path']);
                        } else {
                            error_log("[$timestamp] ERRO ao processar arquivo " . ($i+1) . ": " . $result['message']);
                            // Continuamos o processo mesmo com erro em um arquivo
                        }
                    } catch (Exception $e) {
                        error_log("[$timestamp] EXCEÇÃO no processamento do arquivo: " . $e->getMessage());
                    }
                }
                
                error_log("[$timestamp] === FIM DO PROCESSAMENTO DE ARQUIVOS REEMBOLSO ===");
            } else {
                error_log("[$timestamp] Nenhum arquivo enviado com o reembolso");
            }

            // Notificar administradores
            $admin_query = $conn->prepare("SELECT id FROM users WHERE is_admin = 1");
            if (!$admin_query) {
                error_log("[$timestamp] ERRO ao preparar consulta de administradores: " . $conn->error);
                throw new Exception("Erro ao buscar administradores");
            }
            
            if (!$admin_query->execute()) {
                error_log("[$timestamp] ERRO ao executar consulta de administradores: " . $admin_query->error);
                // Não interrompe o processo, apenas loga o erro
            }
            
            $admin_result = $admin_query->get_result();
            
            while ($admin = $admin_result->fetch_assoc()) {
                $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, 'sistema', ?, ?, ?)");
                if (!$notif_stmt) {
                    error_log("[$timestamp] ERRO ao preparar inserção de notificação: " . $conn->error);
                    continue;
                }
                
                $notif_titulo = "Nova Solicitação de Reembolso";
                $mensagem = "O usuário " . $user['name'] . " solicitou um reembolso no valor de R$ " . number_format($valor, 2, ',', '.');
                $link = "gerenciar-reembolsos.php";
                
                $notif_stmt->bind_param("isss", $admin['id'], $notif_titulo, $mensagem, $link);
                if (!$notif_stmt->execute()) {
                    error_log("[$timestamp] ERRO ao inserir notificação para admin ID " . $admin['id'] . ": " . $notif_stmt->error);
                    // Continua mesmo se falhar
                }
            }

            error_log("[$timestamp] Processo de reembolso completado com sucesso");
            $response['success'] = true;
            $response['message'] = "Reembolso solicitado com sucesso!";
            
        } catch (Exception $e) {
            error_log("[$timestamp] EXCEÇÃO no processamento do reembolso: " . $e->getMessage());
            error_log("[$timestamp] Trace: " . $e->getTraceAsString());
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        } finally {
            // Fechar conexões e limpar recursos
            if (isset($stmt)) $stmt->close();
            if (isset($admin_query)) $admin_query->close();
            if (isset($notif_stmt)) $notif_stmt->close();
            if (isset($conn)) $conn->close();
            error_log("[$timestamp] Conexões e recursos limpos");
        }

        echo json_encode($response);
        exit;
    }

    // Se não for POST/AJAX, continua com a renderização normal da página
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
    $is_page = true;
    include_once '../includes/header.php';
    include_once '../includes/sidebar.php';
?>

<!-- CSS Específico da Página -->
<style>
  :focus {
    border-color: #1bd81b !important;
    box-shadow: 0 0 5px rgb(7, 228, 25) !important;
    outline: none !important;
  }
  .preview-image {
    max-width: 200px;
    max-height: 200px;
    margin: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  }
  #preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
  }
  .file-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 10px;
    max-width: 200px;
  }
  .file-preview .file-icon {
    font-size: 2.5rem;
    margin-bottom: 8px;
  }
  .file-preview .file-name {
    font-size: 0.8rem;
    text-align: center;
    word-break: break-word;
  }
  .file-size {
    font-size: 0.7rem;
    color: #6c757d;
  }
</style>

<main id="main" class="main">
  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="pagetitle">
              <h1>Solicitar Reembolso</h1>
              <nav>
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                  <li class="breadcrumb-item active">Solicitar Reembolso</li>
                </ol>
              </nav>
            </div>

            <!-- Novo alerta com observações importantes -->
            <div class="alert alert-info alert-dismissible fade show" role="alert">
              <h4 class="alert-heading"><i class="bi bi-info-circle"></i> Observações Importantes:</h4>
              <ul>
                <li>Anexe todos os comprovantes fiscais (notas fiscais, recibos, cupons) legíveis</li>
                <li>O reembolso será analisado em até 5 dias úteis</li>
                <li>Valores acima de R$ 500,00 precisam de aprovação adicional</li>
                <li>Mantenha os comprovantes originais por 6 meses</li>
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form id="reembolsoForm" method="POST" enctype="multipart/form-data">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="dataChamado" class="form-label">Data do Gasto</label>
                  <input type="date" class="form-control" id="dataChamado" name="dataChamado" required>
                </div>
                <div class="col-md-6">
                  <label for="numeroChamado" class="form-label">Número do Chamado (opcional)</label>
                  <input type="number" 
                         class="form-control" 
                         id="numeroChamado" 
                         name="numeroChamado" 
                         min="1"
                         oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="form-text">Digite apenas números. Deixe em branco se não houver chamado associado.</div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="valor" class="form-label">Valor do Reembolso (R$)</label>
                  <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                </div>
                <div class="col-md-6">
                  <label for="tipo_reembolso" class="form-label">Tipo de Reembolso</label>
                  <select class="form-select" id="tipo_reembolso" name="tipo_reembolso" required>
                    <option value="">Selecione o tipo</option>
                    <option value="estacionamento">Estacionamento</option>
                    <option value="pedagio">Pedágio</option>
                    <option value="alimentacao">Alimentação</option>
                    <option value="transporte">Transporte</option>
                    <option value="hospedagem">Hospedagem</option>
                    <option value="outros">Outros</option>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label for="informacoesAdicionais" class="form-label">Descrição Detalhada</label>
                <textarea class="form-control" id="informacoesAdicionais" name="informacoesAdicionais" rows="4" required></textarea>
              </div>

              <div class="mb-3">
                <label for="arquivos" class="form-label">Comprovantes (Notas fiscais, recibos, etc.)</label>
                <input type="file" class="form-control" id="arquivos" name="arquivos[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" multiple required>
                <div class="form-text">
                  <strong>Formatos aceitos:</strong> PDF, JPG, PNG, DOC, DOCX, XLS, XLSX<br>
                  <strong>Tamanho máximo:</strong> 10MB por arquivo<br>
                  <strong>Dica:</strong> Certifique-se que todos os documentos estejam legíveis
                </div>
                <div id="preview-container"></div>
              </div>

              <!-- Nova caixa de confirmação dos termos -->
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="concordoTermos" required>
                <label class="form-check-label" for="concordoTermos">
                  Declaro que li e concordo com os termos para solicitação de reembolso. Confirmo que todos os dados e documentos enviados são verdadeiros.
                </label>
              </div>

              <div class="row mt-4">
                <div class="col-12">
                  <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                  <button type="reset" class="btn btn-secondary">Limpar Formulário</button>
                </div>
              </div>
            </form>

            <!-- Modal de Sucesso -->
            <div class="modal fade" id="sucessoModal" tabindex="-1" aria-labelledby="sucessoModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="sucessoModalLabel">Sucesso!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <p id="mensagemSucesso"></p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Fechar</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal de Erro -->
            <div class="modal fade" id="erroModal" tabindex="-1" aria-labelledby="erroModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="erroModalLabel">Erro!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <p id="mensagemErro"></p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include_once '../includes/footer.php'; ?>

<script>
// Armazenar o nome e o ID do usuário logado na sessionStorage
sessionStorage.setItem('nomeUsuario', '<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>');
sessionStorage.setItem('idUsuario', '<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>');

// Função para mostrar modal de sucesso
function mostrarSucesso(mensagem) {
    document.getElementById('mensagemSucesso').textContent = mensagem;
    const modal = new bootstrap.Modal(document.getElementById('sucessoModal'));
    modal.show();
}

// Função para mostrar modal de erro
function mostrarErro(mensagem) {
    document.getElementById('mensagemErro').textContent = mensagem;
    const modal = new bootstrap.Modal(document.getElementById('erroModal'));
    modal.show();
}

// Função para limpar o formulário
function limparFormulario() {
    document.getElementById('reembolsoForm').reset();
    mostrarSucesso('Formulário limpo com sucesso!');
}

// Função para enviar dados para o Discord
async function enviarParaDiscord(dados) {
    const webhookUrl = 'https://discord.com/api/webhooks/1333406850187526184/vOEWFHFRY-I8Vs7A5M3CD71REU6fr60vChk_J7-C8-8eUM4DUnm2kMahjvLfajkpR3Xm';
    
    // Criar o FormData para enviar arquivos
    const formData = new FormData();
    
    // Adicionar a mensagem como um campo JSON
    const mensagem = {
        content: 'Nova solicitação de reembolso',
        embeds: [{
            title: `Reembolso - Chamado #${dados.numeroChamado}`,
            color: 0x00ff00,
            fields: [
                {
                    name: 'Data do Chamado',
                    value: dados.dataChamado,
                    inline: true
                },
                {
                    name: 'Número do Chamado',
                    value: dados.numeroChamado,
                    inline: true
                },
                {
                    name: 'Descrição',
                    value: dados.informacoesAdicionais || 'Sem descrição',
                    inline: false
                }
            ],
            timestamp: new Date().toISOString()
        }]
    };

    // Adicionar os arquivos primeiro
    const fileInput = document.getElementById('arquivos');
    const files = fileInput.files;
    
    // Se não houver arquivos, envia apenas a mensagem
    if (files.length === 0) {
        const response = await fetch(webhookUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(mensagem)
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erro ao enviar mensagem para o Discord: ${errorText}`);
        }
        return response;
    }

    // Se houver arquivos, envia com FormData
    formData.append('payload_json', JSON.stringify(mensagem));
    
    // Adiciona cada arquivo com o nome 'files[n]'
    for (let i = 0; i < files.length; i++) {
        formData.append(`files[${i}]`, files[i]);
    }

    try {
        const response = await fetch(webhookUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erro ao enviar mensagem para o Discord: ${errorText}`);
        }

        return response;
    } catch (error) {
        console.error('Erro ao enviar para o Discord:', error);
        throw error;
    }
}

// Evento do botão Enviar para Discord
document.getElementById('reembolsoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            mostrarSucesso(data.message);
            this.reset();
            setTimeout(() => {
                window.location.href = 'meus-reembolsos.php';
            }, 2000);
        } else {
            mostrarErro(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarErro("Erro ao salvar os dados. Por favor, tente novamente.");
    });
});

document.getElementById('arquivos').addEventListener('change', function(e) {
  const previewContainer = document.getElementById('preview-container');
  previewContainer.innerHTML = '';
  
  // Tipos de arquivos permitidos
  const allowedTypes = [
    'application/pdf', 
    'application/x-pdf',
    'image/jpeg', 
    'image/jpg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  ];
  
  // Tamanho máximo (10MB)
  const maxSize = 10 * 1024 * 1024;
  
  // Flag para verificar se todos os arquivos são válidos
  let allValid = true;
  
  // Função auxiliar para formatar o tamanho do arquivo
  function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    else return (bytes / 1048576).toFixed(2) + ' MB';
  }
  
  Array.from(e.target.files).forEach(file => {
    // Verificar tamanho
    if (file.size > maxSize) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'alert alert-danger';
      errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> <strong>ERRO:</strong> "${file.name}" (${formatFileSize(file.size)}) excede o tamanho máximo permitido de 10MB.`;
      previewContainer.appendChild(errorDiv);
      allValid = false;
      return;
    }
    
    // Verificar tipo
    if (!allowedTypes.includes(file.type)) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'alert alert-danger';
      errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> <strong>ERRO:</strong> "${file.name}" (${file.type}) não é um tipo de arquivo permitido.`;
      previewContainer.appendChild(errorDiv);
      allValid = false;
      return;
    }
    
    // Preview para imagens
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const fileContainer = document.createElement('div');
        fileContainer.className = 'file-preview';
        
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'preview-image';
        
        const nameElement = document.createElement('div');
        nameElement.className = 'file-name';
        nameElement.textContent = file.name;
        
        const sizeElement = document.createElement('div');
        sizeElement.className = 'file-size';
        sizeElement.textContent = formatFileSize(file.size);
        
        fileContainer.appendChild(img);
        fileContainer.appendChild(nameElement);
        fileContainer.appendChild(sizeElement);
        
        previewContainer.appendChild(fileContainer);
      }
      reader.readAsDataURL(file);
    } else {
      // Ícone para outros tipos de arquivo
      const fileContainer = document.createElement('div');
      fileContainer.className = 'file-preview';
      
      const iconContainer = document.createElement('div');
      iconContainer.className = 'file-icon';
      
      // Selecionar ícone adequado baseado no tipo de arquivo
      let iconClass = 'bi-file-earmark';
      if (file.type.includes('pdf')) iconClass = 'bi-file-earmark-pdf-fill text-danger';
      else if (file.type.includes('word')) iconClass = 'bi-file-earmark-word-fill text-primary';
      else if (file.type.includes('sheet') || file.type.includes('excel')) iconClass = 'bi-file-earmark-excel-fill text-success';
      
      iconContainer.innerHTML = `<i class="bi ${iconClass}"></i>`;
      
      const nameElement = document.createElement('div');
      nameElement.className = 'file-name';
      nameElement.textContent = file.name;
      
      const sizeElement = document.createElement('div');
      sizeElement.className = 'file-size';
      sizeElement.textContent = formatFileSize(file.size);
      
      fileContainer.appendChild(iconContainer);
      fileContainer.appendChild(nameElement);
      fileContainer.appendChild(sizeElement);
      
      previewContainer.appendChild(fileContainer);
    }
  });
  
  // Se houver arquivos inválidos, limpar o input
  if (!allValid) {
    this.value = '';
    const warningDiv = document.createElement('div');
    warningDiv.className = 'alert alert-warning mt-3';
    warningDiv.innerHTML = '<i class="bi bi-info-circle-fill"></i> <strong>Atenção:</strong> Os arquivos com erro foram removidos. Por favor, selecione arquivos válidos.';
    previewContainer.appendChild(warningDiv);
  }
});

// Formatar campo de valor para mostrar duas casas decimais
document.getElementById('valor').addEventListener('change', function(e) {
  if (e.target.value) {
    e.target.value = parseFloat(e.target.value).toFixed(2);
  }
});
</script>