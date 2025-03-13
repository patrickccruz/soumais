<?php
// Buffer de saída para evitar "headers already sent"
ob_start();

// Verificar se a sessão já foi iniciada para evitar warnings
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

// Verificar definição da variável user
if (!isset($_SESSION['user'])) {
    die("Sessão de usuário não encontrada.");
}

// Incluir funções de upload
require_once '../includes/upload_functions.php';
$user = $_SESSION['user'];

// Conectar ao banco de dados (usando o arquivo centralizado)
require_once '../includes/db.php';

// Verificar ID do reembolso
$reembolso_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reembolso_id <= 0) {
    die("ID de reembolso inválido.");
}

// Verificar se o reembolso existe e pertence ao usuário
try {
    $stmt = $conn->prepare("SELECT * FROM reembolsos WHERE id = ? AND user_id = ? AND status = 'criticado'");
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    $stmt->bind_param("ii", $reembolso_id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Reembolso não encontrado ou não pode ser editado.";
        header("Location: meus-reembolsos.php");
        exit;
    }

    $reembolso = $result->fetch_assoc();
    $stmt->close();
    
} catch (Exception $e) {
    die("Erro ao consultar reembolso: " . $e->getMessage());
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $dataChamado = $_POST['dataChamado'];
        $numeroChamado = !empty($_POST['numeroChamado']) ? intval($_POST['numeroChamado']) : null;
        $informacoesAdicionais = $_POST['informacoesAdicionais'];
        $valor = str_replace(',', '.', $_POST['valor']);
        
        // Informações de diagnóstico detalhadas
        $valor_original = isset($_POST['tipo_reembolso']) ? $_POST['tipo_reembolso'] : 'não definido';
        error_log("Valor original tipo_reembolso: '" . $valor_original . "'");
        
        // Verificar se existe o campo tipo_reembolso_fixed que usamos como backup
        if (isset($_POST['tipo_reembolso_fixed']) && !empty($_POST['tipo_reembolso_fixed'])) {
            $tipo_reembolso = $_POST['tipo_reembolso_fixed'];
            error_log("Usando valor de backup: '" . $tipo_reembolso . "'");
        } else {
            // Validar o tipo de reembolso
            $tipos_permitidos = ['estacionamento', 'pedagio', 'alimentacao', 'transporte', 'hospedagem', 'outros'];
            
            // Manter o valor original, não aplicar strtolower
            $tipo_reembolso = isset($_POST['tipo_reembolso']) ? trim($_POST['tipo_reembolso']) : '';
            error_log("Valor após trim: '" . $tipo_reembolso . "'");
            
            // Debug: verificar se o valor está na lista de permitidos
            if (!in_array($tipo_reembolso, $tipos_permitidos)) {
                error_log("Valor não encontrado na lista de permitidos");
                error_log("Tipos permitidos: " . implode(', ', $tipos_permitidos));
                
                // Tentar detectar qual é o mais próximo
                foreach ($tipos_permitidos as $tipo) {
                    if (strtolower($tipo_reembolso) == strtolower($tipo)) {
                        error_log("Encontrado match case-insensitive: $tipo");
                        // Usar o valor correto da lista
                        $tipo_reembolso = $tipo;
                        break;
                    }
                }
            }
            
            error_log("Valor final usado: '" . $tipo_reembolso . "'");
        }
        
        $tipos_permitidos = ['estacionamento', 'pedagio', 'alimentacao', 'transporte', 'hospedagem', 'outros'];
        if (!in_array($tipo_reembolso, $tipos_permitidos)) {
            throw new Exception("Tipo de reembolso inválido. Por favor, selecione uma opção válida da lista. Valor recebido: '" . htmlspecialchars($tipo_reembolso) . "'");
        }
        
        $status = 'pendente'; // Volta para pendente após edição

        // Atualizar sem o campo problemático primeiro
        $stmt = $conn->prepare("UPDATE reembolsos SET data_chamado = ?, numero_chamado = ?, informacoes_adicionais = ?, valor = ?, status = ?, comentario_admin = NULL WHERE id = ?");
        $stmt->bind_param("sssisi", $dataChamado, $numeroChamado, $informacoesAdicionais, $valor, $status, $reembolso_id);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar reembolso: " . $stmt->error);
        }
        
        // Atualizar apenas o campo problemático em uma consulta separada e com valor de enum válido
        $sql = "UPDATE reembolsos SET tipo_reembolso = '" . $conn->real_escape_string($tipo_reembolso) . "' WHERE id = " . intval($reembolso_id);
        error_log("SQL Final: " . $sql);
        
        if (!$conn->query($sql)) {
            throw new Exception("Erro ao atualizar tipo_reembolso: " . $conn->error . " (SQL: " . $sql . ")");
        }

        // Processar novos arquivos se enviados
        if (isset($_FILES['arquivos']) && $_FILES['arquivos']['error'][0] != UPLOAD_ERR_NO_FILE) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            $upload_info = get_upload_path('reimbursement', ['reimbursement_id' => $reembolso_id]);
            $upload_path = $upload_info['absolute_path'];
            $relative_base_path = $upload_info['relative_path'];
            
            error_log("Upload path: " . $upload_path);
            error_log("Relative base path: " . $relative_base_path);
            
            // Limpar arquivos antigos
            if ($reembolso['arquivo_path']) {
                $old_files = explode(',', $reembolso['arquivo_path']);
                foreach ($old_files as $old_file) {
                    // Caminho completo do arquivo, sem precisar adicionar '../'
                    $file_path = dirname(__DIR__) . '/' . $old_file;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
            
            $arquivoPaths = array();
            $fileCount = count($_FILES['arquivos']['name']);
            
            for($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['arquivos']['error'][$i] == UPLOAD_ERR_OK) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $finfo->file($_FILES['arquivos']['tmp_name'][$i]);
                    
                    if (!is_allowed_file_type($mime_type, $allowed_types)) {
                        continue;
                    }

                    $new_filename = generate_unique_filename($_FILES['arquivos']['name'][$i], 'reembolso_');
                    $full_path = $upload_path . '/' . $new_filename;
                    
                    if (move_uploaded_file_safe($_FILES['arquivos']['tmp_name'][$i], $full_path)) {
                        // Armazenar o caminho relativo para o banco de dados
                        $relative_path = $relative_base_path . '/' . $new_filename;
                        $arquivoPaths[] = $relative_path;
                        
                        // Adicionar log para debug
                        error_log("Arquivo processado: original=$full_path, relativo=$relative_path");
                    }
                }
            }

            if (!empty($arquivoPaths)) {
                $arquivo_path = implode(',', $arquivoPaths);
                $stmt = $conn->prepare("UPDATE reembolsos SET arquivo_path = ? WHERE id = ?");
                $stmt->bind_param("si", $arquivo_path, $reembolso_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar caminhos dos arquivos: " . $stmt->error);
                }
            }
        }

        // Notificar administradores sobre a atualização
        $admin_query = $conn->prepare("SELECT id FROM users WHERE is_admin = 1");
        $admin_query->execute();
        $admin_result = $admin_query->get_result();
        
        while ($admin = $admin_result->fetch_assoc()) {
            $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, 'sistema', ?, ?, ?)");
            $notif_titulo = "Reembolso Atualizado";
            $mensagem = "O usuário " . $user['name'] . " atualizou o reembolso #" . $reembolso_id;
            $link = "gerenciar-reembolsos.php";
            $notif_stmt->bind_param("isss", $admin['id'], $notif_titulo, $mensagem, $link);
            $notif_stmt->execute();
        }

        $_SESSION['success'] = "Reembolso atualizado com sucesso!";
        // Limpar qualquer saída antes de redirecionar
        ob_end_clean();
        header("Location: meus-reembolsos.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        // Limpar qualquer saída antes de redirecionar
        ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $reembolso_id);
        exit;
    }
}

// Configurar a página e incluir o template
$is_page = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="pagetitle">
                            <h1>Editar Reembolso #<?php echo $reembolso_id; ?></h1>
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                                    <li class="breadcrumb-item"><a href="meus-reembolsos.php">Meus Reembolsos</a></li>
                                    <li class="breadcrumb-item active">Editar Reembolso</li>
                                </ol>
                            </nav>
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

                        <!-- Feedback do Administrador -->
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">
                                <i class="bi bi-info-circle"></i> Feedback do Administrador
                            </h5>
                            <p><?php echo htmlspecialchars($reembolso['comentario_admin']); ?></p>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="dataChamado" class="form-label">Data do Gasto</label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="dataChamado" 
                                           name="dataChamado" 
                                           value="<?php echo $reembolso['data_chamado']; ?>" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="numeroChamado" class="form-label">Número do Chamado (opcional)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="numeroChamado" 
                                           name="numeroChamado"
                                           min="1"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                           value="<?php echo $reembolso['numero_chamado'] ? htmlspecialchars($reembolso['numero_chamado']) : ''; ?>">
                                    <div class="form-text">Digite apenas números. Deixe em branco se não houver chamado associado.</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="valor" class="form-label">Valor do Reembolso (R$)</label>
                                    <input type="number" 
                                           step="0.01" 
                                           class="form-control" 
                                           id="valor" 
                                           name="valor" 
                                           value="<?php echo $reembolso['valor']; ?>"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipo_reembolso" class="form-label">Tipo de Reembolso</label>
                                    <select class="form-select" id="tipo_reembolso" name="tipo_reembolso" required onchange="document.getElementById('tipo_reembolso_fixed').value = this.value;">
                                        <option value="">Selecione o tipo</option>
                                        <?php
                                        // Lista de tipos permitidos, exatamente como definidos no banco de dados
                                        $tipos = [
                                            'estacionamento' => 'Estacionamento',
                                            'pedagio' => 'Pedágio',
                                            'alimentacao' => 'Alimentação',
                                            'transporte' => 'Transporte',
                                            'hospedagem' => 'Hospedagem',
                                            'outros' => 'Outros'
                                        ];
                                        
                                        // Exibir o valor original do reembolso para diagnóstico
                                        error_log("Valor atual tipo_reembolso no banco: '" . $reembolso['tipo_reembolso'] . "'");
                                        
                                        foreach ($tipos as $valor => $texto) {
                                            $selected = $reembolso['tipo_reembolso'] == $valor ? 'selected' : '';
                                            echo "<option value=\"{$valor}\" {$selected}>{$texto}</option>";
                                        }
                                        ?>
                                    </select>
                                    <!-- Campo oculto com valor de backup -->
                                    <input type="hidden" id="tipo_reembolso_fixed" name="tipo_reembolso_fixed" value="<?php echo htmlspecialchars($reembolso['tipo_reembolso']); ?>">
                                    <div class="form-text">Selecione o tipo de reembolso que melhor se aplica a este gasto.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="informacoesAdicionais" class="form-label">Descrição Detalhada</label>
                                <textarea class="form-control" 
                                          id="informacoesAdicionais" 
                                          name="informacoesAdicionais" 
                                          rows="4" 
                                          required><?php echo htmlspecialchars($reembolso['informacoes_adicionais']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Comprovantes Atuais</label>
                                <div class="current-files d-flex flex-wrap gap-3">
                                    <?php 
                                    if ($reembolso['arquivo_path']) {
                                        $arquivos = explode(',', $reembolso['arquivo_path']);
                                        foreach($arquivos as $arquivo) {
                                            // Determinar o caminho de exibição correto
                                            $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
                                            $nome = basename($arquivo);
                                            $arquivo_url = "/{$arquivo}";
                                            
                                            // Se for uma imagem, mostrar uma miniatura
                                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                                                echo "<div class='current-file' style='width: 150px;'>";
                                                echo "<a href='{$arquivo_url}' target='_blank'>";
                                                echo "<img src='{$arquivo_url}' alt='{$nome}' style='max-width: 140px; max-height: 140px; object-fit: cover; border-radius: 8px;'><br>";
                                                echo "<small class='text-truncate d-block'>" . $nome . "</small>";
                                                echo "</a>";
                                                echo "</div>";
                                            } else {
                                                echo "<div class='current-file' style='width: 150px;'>";
                                                echo "<a href='{$arquivo_url}' target='_blank' class='d-flex flex-column align-items-center'>";
                                                echo "<i class='bi bi-file-earmark-" . ($ext == 'pdf' ? 'pdf-fill text-danger' : 'text-fill text-primary') . "' style='font-size: 4rem;'></i>";
                                                echo "<small class='text-truncate d-block text-center'>" . $nome . "</small>";
                                                echo "</a>";
                                                echo "</div>";
                                            }
                                        }
                                    } else {
                                        echo "<p>Nenhum arquivo anexado</p>";
                                    }
                                    ?>
                                </div>

                                <label for="arquivos" class="form-label">Novos Comprovantes (opcional)</label>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Se você enviar novos arquivos, os arquivos anteriores serão substituídos.
                                </div>
                                <input type="file" 
                                       class="form-control" 
                                       id="arquivos" 
                                       name="arquivos[]" 
                                       accept=".pdf,image/*" 
                                       multiple>
                                <div id="preview-container"></div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Atualizar Solicitação
                                    </button>
                                    <a href="meus-reembolsos.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                </div>
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

<?php 
// Incluir o rodapé
include_once '../includes/footer.php';
?>

<style>
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        margin: 10px;
    }
    #preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .current-files {
        margin-bottom: 20px;
    }
    .current-file {
        display: inline-block;
        margin: 5px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar visualização de arquivos
        document.getElementById('arquivos').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('preview-container');
            previewContainer.innerHTML = '';
            
            Array.from(e.target.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                } else {
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'alert alert-info';
                    fileInfo.textContent = `Arquivo: ${file.name}`;
                    previewContainer.appendChild(fileInfo);
                }
            });
        });

        // Formatar campo de valor para mostrar duas casas decimais
        document.getElementById('valor').addEventListener('change', function(e) {
            if (e.target.value) {
                e.target.value = parseFloat(e.target.value).toFixed(2);
            }
        });
    });
</script> 