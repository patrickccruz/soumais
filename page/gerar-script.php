<?php
// Tentar aumentar os limites de upload em tempo de execução
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

$is_page = true;

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    $user = ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
}

require_once '../includes/upload_functions.php';

// Conexão com o banco de dados
require_once '../includes/db.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Criar diretório de log se não existir
        $log_dir = dirname(__DIR__) . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/gerar_script_upload.log';
        $timestamp = date('Y-m-d H:i:s');
        
        file_put_contents($log_file, "\n[$timestamp] ==== PROCESSANDO FORM GERAR-SCRIPT ====\n", FILE_APPEND);
        file_put_contents($log_file, "[$timestamp] Configurações PHP: upload_max_filesize=" . ini_get('upload_max_filesize') . 
                                     ", post_max_size=" . ini_get('post_max_size') . "\n", FILE_APPEND);
        
        $dataChamado = $_POST['dataChamado'];
        $numeroChamado = $_POST['numeroChamado'];
        $tipoChamado = $_POST['tipoChamado'];
        $cliente = $_POST['cliente'];
        $nomeInformante = $_POST['nomeInformante'];
        
        // Processar arrays de patrimônios
        $quantidadesPatrimonios = $_POST['quantidadePatrimonios'];
        $tiposPatrimonio = $_POST['tipoPatrimonio'];
        
        // Combinar as quantidades e tipos em uma string JSON
        $patrimonios = array();
        for ($i = 0; $i < count($quantidadesPatrimonios); $i++) {
            $patrimonios[] = array(
                'quantidade' => $quantidadesPatrimonios[$i],
                'tipo' => $tiposPatrimonio[$i]
            );
        }
        
        // Calcular total de patrimônios
        $quantidadeTotal = array_sum($quantidadesPatrimonios);
        $tiposPatrimonioStr = json_encode($patrimonios);
        
        $kmInicial = $_POST['kmInicial'];
        $kmFinal = $_POST['kmFinal'];
        $horaChegada = $_POST['horaChegada'];
        $horaSaida = $_POST['horaSaida'];
        $enderecoPartida = $_POST['enderecoPartida'];
        $enderecoChegada = $_POST['enderecoChegada'];
        $informacoesAdicionais = $_POST['informacoesAdicionais'];
        $statusChamado = $_POST['statusChamado'];
        $arquivoPath = '';

        // Primeiro inserir o registro para obter o ID
        $query = "INSERT INTO reports (user_id, data_chamado, numero_chamado, tipo_chamado, cliente, nome_informante, quantidade_patrimonios, tipo_patrimonio, km_inicial, km_final, hora_chegada, hora_saida, endereco_partida, endereco_chegada, informacoes_adicionais, status_chamado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }
        
        $stmt->bind_param("isssssisisssssss", $user['id'], $dataChamado, $numeroChamado, $tipoChamado, $cliente, $nomeInformante, $quantidadeTotal, $tiposPatrimonioStr, $kmInicial, $kmFinal, $horaChegada, $horaSaida, $enderecoPartida, $enderecoChegada, $informacoesAdicionais, $statusChamado);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao salvar os dados: " . $stmt->error);
        }

        $report_id = $conn->insert_id;
        file_put_contents($log_file, "[$timestamp] Report ID criado: $report_id\n", FILE_APPEND);

        // Processar upload do arquivo
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == UPLOAD_ERR_OK) {
            file_put_contents($log_file, "[$timestamp] Arquivo recebido: " . $_FILES['arquivo']['name'] . "\n", FILE_APPEND);
            file_put_contents($log_file, "[$timestamp] Tamanho: " . $_FILES['arquivo']['size'] . " bytes\n", FILE_APPEND);
            file_put_contents($log_file, "[$timestamp] Tipo: " . $_FILES['arquivo']['type'] . "\n", FILE_APPEND);
            
            // Validar tipo de arquivo
            $allowed_types = ['application/pdf', 'application/x-pdf', 'application/acrobat', 'application/vnd.pdf', 'text/pdf', 'text/x-pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($_FILES['arquivo']['tmp_name']);
            
            file_put_contents($log_file, "[$timestamp] MIME detectado: " . $mime_type . "\n", FILE_APPEND);
            
            if (!in_array($mime_type, $allowed_types)) {
                $msg = "Tipo de arquivo não permitido. Use apenas PDF.";
                file_put_contents($log_file, "[$timestamp] ERRO: $msg\n", FILE_APPEND);
                throw new Exception($msg);
            }

            // Verificar tamanho do arquivo (definir limite manual de 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB em bytes
            if ($_FILES['arquivo']['size'] > $max_size) {
                $msg = "O arquivo é muito grande (" . round($_FILES['arquivo']['size'] / (1024 * 1024), 2) . "MB). O tamanho máximo é 10MB.";
                file_put_contents($log_file, "[$timestamp] ERRO: $msg\n", FILE_APPEND);
                throw new Exception($msg);
            }

            // Criar diretório de uploads se não existir
            $base_upload_dir = dirname(__DIR__) . '/uploads/reports';
            if (!is_dir($base_upload_dir)) {
                mkdir($base_upload_dir, 0777, true);
                file_put_contents($log_file, "[$timestamp] Diretório base de uploads criado: $base_upload_dir\n", FILE_APPEND);
            }
            
            // Gerar nome único e mover arquivo
            $new_filename = 'rat_' . $report_id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $_FILES['arquivo']['name']);
            file_put_contents($log_file, "[$timestamp] Nome de arquivo gerado: " . $new_filename . "\n", FILE_APPEND);
            
            try {
                // Criar diretório específico para este relatório
                $report_dir = $base_upload_dir . '/' . $report_id;
                if (!is_dir($report_dir)) {
                    if (!mkdir($report_dir, 0777, true)) {
                        throw new Exception("Não foi possível criar o diretório do relatório");
                    }
                    file_put_contents($log_file, "[$timestamp] Diretório do relatório criado: $report_dir\n", FILE_APPEND);
                }
                
                $destination = $report_dir . '/' . $new_filename;
                file_put_contents($log_file, "[$timestamp] Destino final: $destination\n", FILE_APPEND);
                
                // Método melhorado para movimentação do arquivo
                if (!file_exists($_FILES['arquivo']['tmp_name'])) {
                    file_put_contents($log_file, "[$timestamp] ERRO: Arquivo temporário não existe\n", FILE_APPEND);
                    throw new Exception("Arquivo temporário não existe");
                }
                
                if (!is_readable($_FILES['arquivo']['tmp_name'])) {
                    file_put_contents($log_file, "[$timestamp] ERRO: Arquivo temporário não pode ser lido\n", FILE_APPEND);
                    throw new Exception("Arquivo temporário não pode ser lido");
                }
                
                // Tentar mover o arquivo com múltiplas abordagens
                $upload_success = false;
                
                // Primeira tentativa: move_uploaded_file (método recomendado e mais seguro)
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destination)) {
                    $upload_success = true;
                    file_put_contents($log_file, "[$timestamp] Arquivo movido com sucesso (move_uploaded_file)\n", FILE_APPEND);
                } else {
                    file_put_contents($log_file, "[$timestamp] Falha ao mover o arquivo com move_uploaded_file\n", FILE_APPEND);
                    
                    // Segunda tentativa: copiar o arquivo
                    if (copy($_FILES['arquivo']['tmp_name'], $destination)) {
                        $upload_success = true;
                        file_put_contents($log_file, "[$timestamp] Arquivo copiado com sucesso (copy)\n", FILE_APPEND);
                    } else {
                        file_put_contents($log_file, "[$timestamp] Falha ao copiar o arquivo\n", FILE_APPEND);
                        
                        // Terceira tentativa: usar file_put_contents
                        $file_content = file_get_contents($_FILES['arquivo']['tmp_name']);
                        if ($file_content !== false && file_put_contents($destination, $file_content)) {
                            $upload_success = true;
                            file_put_contents($log_file, "[$timestamp] Arquivo salvo com sucesso (file_put_contents)\n", FILE_APPEND);
                        } else {
                            file_put_contents($log_file, "[$timestamp] Falha ao salvar o arquivo com file_put_contents\n", FILE_APPEND);
                        }
                    }
                }
                
                if ($upload_success) {
                    // Definir permissões adequadas
                    chmod($destination, 0644);
                    
                    // Caminho relativo para o banco de dados
                    $arquivoPath = 'uploads/reports/' . $report_id . '/' . $new_filename;
                    file_put_contents($log_file, "[$timestamp] Caminho para banco de dados: $arquivoPath\n", FILE_APPEND);
                    
                    // Atualizar o registro com o caminho do arquivo
                    $stmt = $conn->prepare("UPDATE reports SET arquivo_path = ? WHERE id = ?");
                    $stmt->bind_param("si", $arquivoPath, $report_id);
                    
                    if (!$stmt->execute()) {
                        $db_error = "Erro ao atualizar caminho do arquivo: " . $stmt->error;
                        file_put_contents($log_file, "[$timestamp] ERRO BD: $db_error\n", FILE_APPEND);
                        throw new Exception($db_error);
                    }
                    
                    file_put_contents($log_file, "[$timestamp] Banco de dados atualizado com sucesso\n", FILE_APPEND);
                } else {
                    throw new Exception("Não foi possível salvar o arquivo após várias tentativas");
                }
            } catch (Exception $e) {
                file_put_contents($log_file, "[$timestamp] EXCEÇÃO: " . $e->getMessage() . "\n", FILE_APPEND);
                throw $e;
            }
        } else if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] != UPLOAD_ERR_NO_FILE) {
            // Registrar erro específico se houver um problema com o upload
            $upload_error = $_FILES['arquivo']['error'];
            $error_desc = "Erro no upload do arquivo: ";
            
            switch ($upload_error) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_desc .= "O arquivo excede o tamanho máximo permitido pelo PHP (" . ini_get('upload_max_filesize') . ")";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_desc .= "O arquivo excede o tamanho máximo especificado no formulário";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_desc .= "O arquivo foi parcialmente enviado";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_desc .= "Diretório temporário não encontrado";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_desc .= "Falha ao gravar arquivo no disco";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_desc .= "Upload interrompido por uma extensão PHP";
                    break;
                default:
                    $error_desc .= "Código de erro: " . $upload_error;
            }
            
            file_put_contents($log_file, "[$timestamp] $error_desc\n", FILE_APPEND);
            throw new Exception($error_desc);
        }

        $success_message = "Dados salvos com sucesso!";
        header("Location: meus-scripts.php");
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

include_once '../includes/header.php';
?>

<style>
    .form-floating > .form-control[type="file"] {
        height: calc(3.5rem + 2px);
        line-height: 1.25;
    }
    
    /* Estilos para melhorar a UX */
    .form-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
    }
    
    .form-section-title {
        color: #1d8031;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #1d8031;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .patrimonio-row {
        background: white;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 10px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .patrimonio-row:hover {
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    .add-patrimonio, .remove-patrimonio {
        width: 35px;
        height: 35px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(29, 128, 49, 0.25);
    }

    .required-field::after {
        content: "*";
        color: #dc3545;
        margin-left: 4px;
    }

    .form-floating > label {
        padding-left: 20px;
    }

    .form-control::placeholder {
        color: #6c757d;
        opacity: 0.5;
    }

    /* Animação para novos campos de patrimônio */
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .patrimonio-row.new-row {
        animation: slideDown 0.3s ease-out;
    }
    
    /* Estilo para o alerta de informações de upload */
    .upload-info {
        background-color: #e0f0e3;
        border-left: 5px solid #1d8031;
        padding: 0.5rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    /* Corrigir cores dos botões */
    .btn-primary {
        background-color: #1d8031 !important;
        border-color: #1d8031 !important;
    }

    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active {
        background-color: #0f2714 !important;
        border-color: #0f2714 !important;
    }
</style>

<?php include_once '../includes/sidebar.php'; ?>

<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="pagetitle">
                            <h1>Relatório de Atendimento</h1>
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                                    <li class="breadcrumb-item active">Gerar Script</li>
                                </ol>
                            </nav>
                        </div>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form id="scriptForm" enctype="multipart/form-data" method="POST">
                            <!-- Seção: Informações Básicas do Chamado -->
                            <div class="form-section">
                                <h5 class="form-section-title">
                                    <i class="bi bi-info-circle"></i>
                                    Informações Básicas do Chamado
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-floating mb-3">
                                            <input type="date" class="form-control" id="dataChamado" name="dataChamado" value="<?php echo date('Y-m-d'); ?>" required>
                                            <label for="dataChamado" class="required-field">Data do chamado</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating mb-3">
                                            <input type="number" class="form-control" id="numeroChamado" name="numeroChamado" required>
                                            <label for="numeroChamado" class="required-field">Número do chamado</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="tipoChamado" name="tipoChamado" required>
                                                <option value="">Selecione o tipo</option>
                                                <option value="implantacao">Implantação</option>
                                                <option value="sustentacao">Sustentação</option>
                                            </select>
                                            <label for="tipoChamado" class="required-field">Tipo de chamado</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="cliente" name="cliente" required>
                                            <label for="cliente" class="required-field">Cliente</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="nomeInformante" name="nomeInformante" required>
                                            <label for="nomeInformante" class="required-field">Nome de quem informou o chamado</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Patrimônios -->
                            <div class="form-section">
                                <h5 class="form-section-title">
                                    <i class="bi bi-box-seam"></i>
                                    Patrimônios Tratados
                                </h5>
                                <div id="patrimoniosContainer">
                                    <div class="patrimonio-row">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="form-floating">
                                                    <input type="number" class="form-control quantidade-patrimonio" name="quantidadePatrimonios[]" required min="1" value="1">
                                                    <label class="required-field">Quantidade</label>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control tipo-patrimonio" name="tipoPatrimonio[]" required placeholder="Ex: Notebook, Desktop, Monitor">
                                                    <label class="required-field">Tipo de patrimônio</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center justify-content-center">
                                                <button type="button" class="btn btn-success add-patrimonio" title="Adicionar mais patrimônios">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Informações de Deslocamento -->
                            <div class="form-section">
                                <h5 class="form-section-title">
                                    <i class="bi bi-geo-alt"></i>
                                    Informações de Deslocamento
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="number" class="form-control" id="kmInicial" name="kmInicial" required>
                                            <label for="kmInicial" class="required-field">KM inicial</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="number" class="form-control" id="kmFinal" name="kmFinal" required>
                                            <label for="kmFinal" class="required-field">KM final</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="time" class="form-control" id="horaChegada" name="horaChegada" required>
                                            <label for="horaChegada" class="required-field">Horário de chegada</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="time" class="form-control" id="horaSaida" name="horaSaida" required>
                                            <label for="horaSaida" class="required-field">Horário de saída</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="enderecoPartida" name="enderecoPartida" required>
                                            <label for="enderecoPartida" class="required-field">Endereço de partida</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="enderecoChegada" name="enderecoChegada" required>
                                            <label for="enderecoChegada" class="required-field">Endereço de chegada</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Status e Descrição -->
                            <div class="form-section">
                                <h5 class="form-section-title">
                                    <i class="bi bi-clipboard-check"></i>
                                    Status e Descrição
                                </h5>
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="statusChamado" name="statusChamado" required>
                                        <option value="">Selecione o status</option>
                                        <option value="resolvido">Resolvido</option>
                                        <option value="pendente">Pendente</option>
                                        <option value="improdutivo">Improdutivo</option>
                                    </select>
                                    <label for="statusChamado" class="required-field">Status do chamado</label>
                                </div>

                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="informacoesAdicionais" name="informacoesAdicionais" 
                                            style="height: 250px" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Descreva detalhadamente: 1) O que foi realizado no chamado; 2) Problemas encontrados; 3) Soluções aplicadas; 4) Observações importantes"
                                            required></textarea>
                                    <label for="informacoesAdicionais" class="required-field">Descrição detalhada do atendimento</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="arquivo" class="form-label">Anexar RAT (PDF)</label>
                                    <div class="upload-info">
                                        <strong>Dica:</strong> Você pode anexar arquivos PDF de até 10MB. Certifique-se que o arquivo esteja no formato PDF.
                                    </div>
                                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                                    <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".pdf">
                                    <small class="text-muted">Se o upload falhar, tente usar arquivos menores ou use a opção "Upload Alternativo" no menu lateral.</small>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-danger" onclick="limparFormulario()">
                                    <i class="bi bi-trash me-2"></i>Apagar Tudo
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Salvar e Enviar
                                </button>
                            </div>
                        </form>
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
    // Função para limpar o formulário
    function limparFormulario() {
        if (confirm('Tem certeza que deseja limpar todos os campos?')) {
            document.getElementById('scriptForm').reset();
            // Limpar campos de patrimônio extras
            const container = document.getElementById('patrimoniosContainer');
            const rows = container.getElementsByClassName('patrimonio-row');
            while (rows.length > 1) {
                container.removeChild(rows[rows.length - 1]);
            }
            mostrarSucesso('Formulário limpo com sucesso!');
        }
    }

    // Validações e cálculos automáticos
    document.addEventListener('DOMContentLoaded', function() {
        // Validar KM Final maior que Inicial
        document.getElementById('kmFinal').addEventListener('change', function() {
            const kmInicial = parseInt(document.getElementById('kmInicial').value);
            const kmFinal = parseInt(this.value);
            
            if (kmFinal <= kmInicial) {
                mostrarErro('O KM final deve ser maior que o KM inicial');
                this.value = '';
            }
        });

        // Validar Hora Saída maior que Chegada
        document.getElementById('horaSaida').addEventListener('change', function() {
            const horaChegada = document.getElementById('horaChegada').value;
            const horaSaida = this.value;
            
            if (horaSaida <= horaChegada) {
                mostrarErro('O horário de saída deve ser maior que o horário de chegada');
                this.value = '';
            }
        });

        // Validar tamanho do arquivo
        document.getElementById('arquivo').addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileSize = this.files[0].size;
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (fileSize > maxSize) {
                    mostrarErro('O arquivo selecionado excede o tamanho máximo de 10MB. Selecione um arquivo menor ou use a opção "Upload Alternativo" do menu.');
                    this.value = '';
                }
                
                // Verificar extensão
                const fileName = this.files[0].name;
                const fileExt = fileName.split('.').pop().toLowerCase();
                
                if (fileExt !== 'pdf') {
                    mostrarErro('Apenas arquivos PDF são permitidos.');
                    this.value = '';
                }
            }
        });

        // Adicionar novo campo de patrimônio
        document.getElementById('patrimoniosContainer').addEventListener('click', function(e) {
            if (e.target.classList.contains('add-patrimonio') || e.target.parentElement.classList.contains('add-patrimonio')) {
                const btn = e.target.closest('.add-patrimonio');
                const row = btn.closest('.patrimonio-row');
                const newRow = row.cloneNode(true);
                
                // Limpar valores dos inputs
                newRow.querySelectorAll('input').forEach(input => input.value = '');
                newRow.classList.add('new-row');
                
                // Trocar botão de adicionar por remover
                const btnCol = newRow.querySelector('.col-md-2');
                btnCol.innerHTML = `
                    <button type="button" class="btn btn-danger remove-patrimonio" title="Remover este patrimônio">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                
                // Inserir nova linha
                document.getElementById('patrimoniosContainer').appendChild(newRow);

                // Remover classe de animação após a transição
                setTimeout(() => {
                    newRow.classList.remove('new-row');
                }, 300);
            } else if (e.target.classList.contains('remove-patrimonio') || e.target.parentElement.classList.contains('remove-patrimonio')) {
                const btn = e.target.closest('.remove-patrimonio');
                const row = btn.closest('.patrimonio-row');
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                }, 300);
            }
        });
    });

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

    <?php if ($success_message): ?>
    // Mostrar alerta de sucesso
    setTimeout(function() {
        mostrarSucesso("<?php echo htmlspecialchars($success_message); ?>");
    }, 500);
    <?php endif; ?>

    <?php if ($error_message): ?>
    // Mostrar alerta de erro
    setTimeout(function() {
        mostrarErro("<?php echo htmlspecialchars($error_message); ?>");
    }, 500);
    <?php endif; ?>

    // Inicializa os tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
</body>
</html> 