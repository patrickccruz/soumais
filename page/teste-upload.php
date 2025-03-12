<?php
// Configuração de depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sessão
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    echo "Você precisa estar logado para acessar esta página.";
    exit;
}

// Incluir funções necessárias
require_once '../includes/upload_functions.php';

// Verificar se o diretório de uploads existe
$uploads_dir = dirname(__DIR__) . '/uploads';
$test_dir = $uploads_dir . '/test';

// Status
$status = [];

// Verificar diretório de uploads
if (!is_dir($uploads_dir)) {
    $status['uploads_dir'] = [
        'status' => 'erro',
        'mensagem' => "Diretório de uploads não existe: $uploads_dir"
    ];
    try {
        mkdir($uploads_dir, 0777, true);
        $status['uploads_dir']['mensagem'] .= " (Tentativa de criar: " . (is_dir($uploads_dir) ? "Sucesso" : "Falha") . ")";
    } catch (Exception $e) {
        $status['uploads_dir']['mensagem'] .= " (Erro ao criar: " . $e->getMessage() . ")";
    }
} else {
    $status['uploads_dir'] = [
        'status' => is_writable($uploads_dir) ? 'ok' : 'erro',
        'mensagem' => is_writable($uploads_dir) 
            ? "Diretório de uploads existe e tem permissão de escrita" 
            : "Diretório de uploads existe mas NÃO tem permissão de escrita"
    ];
}

// Verificar diretório de teste
if (!is_dir($test_dir)) {
    $status['test_dir'] = [
        'status' => 'erro',
        'mensagem' => "Diretório de teste não existe: $test_dir"
    ];
    try {
        mkdir($test_dir, 0777, true);
        $status['test_dir']['mensagem'] .= " (Tentativa de criar: " . (is_dir($test_dir) ? "Sucesso" : "Falha") . ")";
    } catch (Exception $e) {
        $status['test_dir']['mensagem'] .= " (Erro ao criar: " . $e->getMessage() . ")";
    }
} else {
    $status['test_dir'] = [
        'status' => is_writable($test_dir) ? 'ok' : 'erro',
        'mensagem' => is_writable($test_dir) 
            ? "Diretório de teste existe e tem permissão de escrita" 
            : "Diretório de teste existe mas NÃO tem permissão de escrita"
    ];
}

// Verificar funções de upload
$status['functions'] = [
    'status' => function_exists('process_file_upload') ? 'ok' : 'erro',
    'mensagem' => function_exists('process_file_upload') 
        ? "Função process_file_upload está disponível" 
        : "Função process_file_upload NÃO está disponível"
];

// Verificar configurações do PHP
$status['php_config'] = [
    'status' => 'info',
    'mensagem' => "Upload max filesize: " . ini_get('upload_max_filesize') . 
                  ", Post max size: " . ini_get('post_max_size') . 
                  ", Memory limit: " . ini_get('memory_limit')
];

// Verificar permissões de arquivos temporários
$tmp_dir = sys_get_temp_dir();
$status['tmp_dir'] = [
    'status' => is_writable($tmp_dir) ? 'ok' : 'erro',
    'mensagem' => is_writable($tmp_dir) 
        ? "Diretório temporário ($tmp_dir) tem permissão de escrita" 
        : "Diretório temporário ($tmp_dir) NÃO tem permissão de escrita"
];

// Processar upload de teste se enviado
$test_upload_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
    // Tipos permitidos para teste
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    
    // Processar upload
    try {
        $result = process_file_upload(
            $_FILES['test_file'],
            $test_dir . '/test_upload',
            $allowed_types,
            5 * 1024 * 1024, // 5MB
            'test_'
        );
        
        $test_upload_result = $result;
    } catch (Exception $e) {
        $test_upload_result = [
            'success' => false,
            'message' => "Exceção: " . $e->getMessage()
        ];
    }
}

// Saída HTML
$title = "Diagnóstico de Upload";
$is_page = true;
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Diagnóstico de Upload</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                <li class="breadcrumb-item active">Diagnóstico de Upload</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Status do Sistema de Upload</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Componente</th>
                                        <th>Status</th>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($status as $key => $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($key); ?></td>
                                        <td>
                                            <?php if($item['status'] === 'ok'): ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php elseif($item['status'] === 'erro'): ?>
                                                <span class="badge bg-danger">ERRO</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">INFO</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['mensagem']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <h5 class="card-title mt-4">Teste de Upload</h5>
                        
                        <?php if($test_upload_result): ?>
                        <div class="alert <?php echo $test_upload_result['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <h5><?php echo $test_upload_result['success'] ? 'Upload bem-sucedido!' : 'Falha no upload!'; ?></h5>
                            <p><?php echo htmlspecialchars($test_upload_result['message']); ?></p>
                            <?php if($test_upload_result['success']): ?>
                            <p>Arquivo salvo em: <?php echo htmlspecialchars($test_upload_result['path']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data" class="mt-3">
                            <div class="mb-3">
                                <label for="test_file" class="form-label">Arquivo de Teste</label>
                                <input type="file" class="form-control" id="test_file" name="test_file" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="form-text">
                                    Selecione um arquivo (JPG, PNG ou PDF) para testar o sistema de upload.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Testar Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?> 