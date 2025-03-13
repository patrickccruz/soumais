<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

require_once '../includes/upload_functions.php';
$user = $_SESSION['user'];

$conn = new mysqli('localhost', 'root', '', 'sou_digital');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reembolso_id = $_GET['id'] ?? 0;

// Verificar se o reembolso existe e pertence ao usuário
$stmt = $conn->prepare("SELECT * FROM reembolsos WHERE id = ? AND user_id = ? AND status = 'criticado'");
$stmt->bind_param("ii", $reembolso_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Reembolso não encontrado ou não pode ser editado.";
    header("Location: meus-reembolsos.php");
    exit;
}

$reembolso = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $dataChamado = $_POST['dataChamado'];
        $numeroChamado = !empty($_POST['numeroChamado']) ? intval($_POST['numeroChamado']) : null;
        $informacoesAdicionais = $_POST['informacoesAdicionais'];
        $valor = str_replace(',', '.', $_POST['valor']);
        $tipo_reembolso = $_POST['tipo_reembolso'];
        $status = 'pendente'; // Volta para pendente após edição

        // Atualizar informações do reembolso
        $stmt = $conn->prepare("UPDATE reembolsos SET data_chamado = ?, numero_chamado = ?, informacoes_adicionais = ?, valor = ?, tipo_reembolso = ?, status = ?, comentario_admin = NULL WHERE id = ?");
        $stmt->bind_param("sssdisi", $dataChamado, $numeroChamado, $informacoesAdicionais, $valor, $tipo_reembolso, $status, $reembolso_id);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar reembolso: " . $stmt->error);
        }

        // Processar novos arquivos se enviados
        if (isset($_FILES['arquivos']) && $_FILES['arquivos']['error'][0] != UPLOAD_ERR_NO_FILE) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            $upload_path = get_upload_path('reimbursement', ['reimbursement_id' => $reembolso_id]);
            
            // Limpar arquivos antigos
            if ($reembolso['arquivo_path']) {
                $old_files = explode(',', $reembolso['arquivo_path']);
                foreach ($old_files as $old_file) {
                    if (file_exists('../' . $old_file)) {
                        unlink('../' . $old_file);
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
                        $arquivoPaths[] = str_replace('../', '', $full_path);
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
        header("Location: meus-reembolsos.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $reembolso_id);
        exit;
    }
}

$is_page = true;
include_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Editar Reembolso - Sou + Digital</title>
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
</head>

<body>
    <?php include_once '../includes/sidebar.php'; ?>

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
                                        <select class="form-select" id="tipo_reembolso" name="tipo_reembolso" required>
                                            <option value="">Selecione o tipo</option>
                                            <option value="estacionamento" <?php echo $reembolso['tipo_reembolso'] == 'estacionamento' ? 'selected' : ''; ?>>Estacionamento</option>
                                            <option value="pedagio" <?php echo $reembolso['tipo_reembolso'] == 'pedagio' ? 'selected' : ''; ?>>Pedágio</option>
                                            <option value="alimentacao" <?php echo $reembolso['tipo_reembolso'] == 'alimentacao' ? 'selected' : ''; ?>>Alimentação</option>
                                            <option value="transporte" <?php echo $reembolso['tipo_reembolso'] == 'transporte' ? 'selected' : ''; ?>>Transporte</option>
                                            <option value="hospedagem" <?php echo $reembolso['tipo_reembolso'] == 'hospedagem' ? 'selected' : ''; ?>>Hospedagem</option>
                                            <option value="outros" <?php echo $reembolso['tipo_reembolso'] == 'outros' ? 'selected' : ''; ?>>Outros</option>
                                        </select>
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
                                    <div class="current-files">
                                        <?php 
                                        if ($reembolso['arquivo_path']) {
                                            $arquivos = explode(',', $reembolso['arquivo_path']);
                                            foreach($arquivos as $arquivo) {
                                                $nome = basename($arquivo);
                                                echo "<div class='current-file'>";
                                                echo "<a href='$arquivo' target='_blank'>$nome</a>";
                                                echo "</div>";
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

    <script>
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
    </script>
</body>
</html> 