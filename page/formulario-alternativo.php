<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

$is_page = true;

// Verificar se o usuário está logado
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    $user = ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
}

// Importar funções e conexão com o banco
require_once '../includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar se há arquivo enviado
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == UPLOAD_ERR_OK) {
        // Criar diretório para o log
        $log_dir = dirname(__DIR__) . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        $log_file = $log_dir . '/form_alternativo.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "\n[$timestamp] ==== UPLOAD ALTERNATIVO ====\n", FILE_APPEND);
        
        // Registrar informações do arquivo
        file_put_contents($log_file, "[$timestamp] Arquivo: " . $_FILES['arquivo']['name'] . "\n", FILE_APPEND);
        file_put_contents($log_file, "[$timestamp] Tamanho: " . $_FILES['arquivo']['size'] . " bytes\n", FILE_APPEND);
        
        // Verificar o tipo do arquivo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($_FILES['arquivo']['tmp_name']);
        file_put_contents($log_file, "[$timestamp] MIME: " . $mime_type . "\n", FILE_APPEND);
        
        // Verificar se é um PDF válido
        $allowed_types = ['application/pdf', 'application/x-pdf', 'application/acrobat', 'application/vnd.pdf', 'text/pdf', 'text/x-pdf'];
        if (!in_array($mime_type, $allowed_types)) {
            $error = "Tipo de arquivo não permitido. Use apenas PDF.";
            file_put_contents($log_file, "[$timestamp] ERRO: $error\n", FILE_APPEND);
        } else {
            try {
                // Criar diretório para armazenar o arquivo
                $upload_dir = dirname(__DIR__) . '/uploads/page_uploads';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                    file_put_contents($log_file, "[$timestamp] Diretório criado: $upload_dir\n", FILE_APPEND);
                }
                
                // Gerar nome único para o arquivo
                $new_filename = 'pdf_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $_FILES['arquivo']['name']);
                $destination = $upload_dir . '/' . $new_filename;
                
                // Salvar o arquivo
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destination)) {
                    chmod($destination, 0644);
                    file_put_contents($log_file, "[$timestamp] Arquivo salvo com sucesso: $destination\n", FILE_APPEND);
                    
                    // Inserir registro no banco de dados
                    if (isset($_POST['descricao']) && !empty($_POST['descricao'])) {
                        $descricao = $_POST['descricao'];
                        $filepath = 'uploads/page_uploads/' . $new_filename;
                        
                        // Inserir na tabela de documentos
                        $stmt = $conn->prepare("INSERT INTO documentos (user_id, descricao, arquivo_path, data_upload) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("iss", $user['id'], $descricao, $filepath);
                        
                        if ($stmt->execute()) {
                            file_put_contents($log_file, "[$timestamp] Registro salvo no banco de dados\n", FILE_APPEND);
                            $message = "Arquivo enviado com sucesso!";
                        } else {
                            file_put_contents($log_file, "[$timestamp] ERRO ao salvar no banco: " . $stmt->error . "\n", FILE_APPEND);
                            $error = "Erro ao salvar informações no banco de dados: " . $stmt->error;
                        }
                    } else {
                        $message = "Arquivo enviado com sucesso! (sem descrição)";
                    }
                } else {
                    file_put_contents($log_file, "[$timestamp] ERRO ao mover o arquivo\n", FILE_APPEND);
                    $error = "Erro ao salvar o arquivo. Por favor, tente novamente.";
                }
            } catch (Exception $e) {
                file_put_contents($log_file, "[$timestamp] EXCEÇÃO: " . $e->getMessage() . "\n", FILE_APPEND);
                $error = "Erro: " . $e->getMessage();
            }
        }
    } else {
        // Verificar código de erro específico
        if (isset($_FILES['arquivo'])) {
            $upload_error = $_FILES['arquivo']['error'];
            switch ($upload_error) {
                case UPLOAD_ERR_INI_SIZE:
                    $error = "O arquivo excede o tamanho máximo permitido pelo PHP.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "O arquivo excede o tamanho máximo especificado no formulário.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "O arquivo foi parcialmente enviado.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "Nenhum arquivo foi selecionado.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = "Diretório temporário não encontrado.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = "Falha ao gravar arquivo no disco.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = "Upload interrompido por uma extensão PHP.";
                    break;
                default:
                    $error = "Erro desconhecido no upload.";
            }
        } else {
            $error = "Nenhum arquivo foi enviado.";
        }
    }
}

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body pt-3">
                        <div class="pagetitle">
                            <h1>Formulário Alternativo de Upload</h1>
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                                    <li class="breadcrumb-item active">Upload Alternativo</li>
                                </ol>
                            </nav>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info" role="alert">
                            <h4 class="alert-heading">Informações sobre o Upload</h4>
                            <p>Este formulário alternativo foi criado para permitir o upload de arquivos PDF maiores. Funciona de forma semelhante ao formulário principal, mas com algumas otimizações para arquivos grandes.</p>
                            <hr>
                            <p class="mb-0">Tamanho máximo permitido: <strong>10MB</strong>. Se seu arquivo for maior, considere compactá-lo ou dividi-lo em partes menores.</p>
                        </div>
                        
                        <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                            <div class="form-group mb-3">
                                <label for="descricao" class="form-label">Descrição do Documento</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descreva o conteúdo do documento"></textarea>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="arquivo" class="form-label">Selecione o Arquivo PDF</label>
                                <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                                <input class="form-control" type="file" id="arquivo" name="arquivo" accept=".pdf">
                                <div class="form-text">Arquivos PDF de até 10MB são aceitos.</div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i> Enviar Arquivo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>

<script>
    // Validar tamanho do arquivo antes do envio
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('arquivo');
        const maxSize = 10 * 1024 * 1024; // 10MB em bytes
        
        if (fileInput.files.length > 0) {
            const fileSize = fileInput.files[0].size;
            
            if (fileSize > maxSize) {
                e.preventDefault();
                alert('O arquivo selecionado é muito grande. O tamanho máximo permitido é 10MB.');
            }
        }
    });
</script> 