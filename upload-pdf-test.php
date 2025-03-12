<?php
session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes

require_once 'includes/upload_functions.php';

// Configurar log
$log_file = __DIR__ . '/upload_debug.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($log_file, "\n[$timestamp] ==== TESTE DE UPLOAD DE PDF - PROCESSAMENTO ====\n", FILE_APPEND);

$response = [
    'success' => false,
    'message' => '',
    'details' => []
];

// Verificar se um arquivo foi enviado
if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] != UPLOAD_ERR_OK) {
    $error = 'Nenhum arquivo enviado ou erro no upload: ';
    if (isset($_FILES['pdfFile'])) {
        $error .= $_FILES['pdfFile']['error'];
        file_put_contents($log_file, "[$timestamp] Erro no upload: " . $_FILES['pdfFile']['error'] . "\n", FILE_APPEND);
    } else {
        $error .= 'FILES não definido';
        file_put_contents($log_file, "[$timestamp] FILES não definido\n", FILE_APPEND);
    }
    $response['message'] = $error;
} else {
    $file = $_FILES['pdfFile'];
    
    // Log dos dados do arquivo
    file_put_contents($log_file, "[$timestamp] Arquivo recebido: " . $file['name'] . "\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tamanho: " . $file['size'] . " bytes\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Tipo declarado: " . $file['type'] . "\n", FILE_APPEND);
    file_put_contents($log_file, "[$timestamp] Arquivo temporário: " . $file['tmp_name'] . "\n", FILE_APPEND);
    
    // Verificar tipo de arquivo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    file_put_contents($log_file, "[$timestamp] MIME detectado: " . $mime_type . "\n", FILE_APPEND);
    
    $allowed_types = ['application/pdf'];
    
    if (!is_allowed_file_type($mime_type, $allowed_types)) {
        $response['message'] = "Tipo de arquivo não permitido: " . $mime_type;
        file_put_contents($log_file, "[$timestamp] Tipo de arquivo não permitido\n", FILE_APPEND);
    } else {
        // Preparar para o upload
        try {
            // Gerar nome único para o arquivo
            $new_filename = generate_unique_filename($file['name'], 'test_');
            file_put_contents($log_file, "[$timestamp] Nome gerado: " . $new_filename . "\n", FILE_APPEND);
            
            // Usar o diretório de teste
            $test_id = time(); // ID baseado no timestamp
            $upload_path = get_upload_path('reports', ['report_id' => $test_id]);
            file_put_contents($log_file, "[$timestamp] Caminho gerado: " . $upload_path . "\n", FILE_APPEND);
            
            $full_path = $upload_path . '/' . $new_filename;
            file_put_contents($log_file, "[$timestamp] Caminho completo: " . $full_path . "\n", FILE_APPEND);
            
            // Verificar permissões
            $upload_dir = dirname(__DIR__ . '/' . $full_path);
            $perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
            $is_writable = is_writable($upload_dir) ? "Sim" : "Não";
            file_put_contents($log_file, "[$timestamp] Permissões do diretório: $perms, Gravável: $is_writable\n", FILE_APPEND);
            
            // Tentar mover o arquivo
            if (move_uploaded_file_safe($file['tmp_name'], $full_path)) {
                $response['success'] = true;
                $response['message'] = "Arquivo enviado com sucesso!";
                $response['details']['path'] = $full_path;
                
                // Verificar se o arquivo foi realmente criado
                if (file_exists(__DIR__ . '/' . $full_path)) {
                    $response['details']['file_exists'] = true;
                    $response['details']['file_size'] = filesize(__DIR__ . '/' . $full_path);
                } else {
                    $response['details']['file_exists'] = false;
                }
                
                file_put_contents($log_file, "[$timestamp] Upload bem-sucedido para: " . $full_path . "\n", FILE_APPEND);
            } else {
                $response['message'] = "Falha ao mover o arquivo";
                file_put_contents($log_file, "[$timestamp] Falha ao mover o arquivo\n", FILE_APPEND);
                
                // Tentar uma cópia simples para diagnóstico
                $diagnose_path = __DIR__ . '/uploads/diagnosis_' . $new_filename;
                if (copy($file['tmp_name'], $diagnose_path)) {
                    $response['details']['diagnostic'] = "Cópia de diagnóstico funcionou: " . $diagnose_path;
                    file_put_contents($log_file, "[$timestamp] Cópia de diagnóstico funcionou: " . $diagnose_path . "\n", FILE_APPEND);
                } else {
                    $response['details']['diagnostic'] = "Cópia de diagnóstico falhou";
                    file_put_contents($log_file, "[$timestamp] Cópia de diagnóstico falhou\n", FILE_APPEND);
                }
            }
        } catch (Exception $e) {
            $response['message'] = "Erro: " . $e->getMessage();
            file_put_contents($log_file, "[$timestamp] Exceção: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

// Retornar resultado para o usuário
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do Upload de PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f8f8f8; padding: 10px; overflow: auto; }
        .btn { display: inline-block; padding: 8px 16px; background: #4CAF50; color: white; 
               text-decoration: none; border-radius: 4px; margin-top: 15px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Resultado do Teste de Upload</h1>
    
    <div class="card">
        <h2 class="<?= $response['success'] ? 'success' : 'error' ?>">
            <?= $response['success'] ? 'Sucesso!' : 'Falha!' ?>
        </h2>
        <p><?= htmlspecialchars($response['message']) ?></p>
        
        <?php if (!empty($response['details'])): ?>
            <h3>Detalhes:</h3>
            <pre><?= htmlspecialchars(print_r($response['details'], true)) ?></pre>
        <?php endif; ?>
        
        <a href="teste-upload-pdf.php" class="btn">Voltar ao Teste</a>
    </div>
    
    <div class="card">
        <h2>Log de Depuração</h2>
        <pre>
<?php 
    if (file_exists($log_file)) {
        // Mostrar apenas as últimas 50 linhas do log
        $log_content = file($log_file);
        $lines_to_show = array_slice($log_content, -50);
        echo htmlspecialchars(implode('', $lines_to_show));
    } else {
        echo "Arquivo de log não encontrado.";
    }
?>
        </pre>
    </div>
</body>
</html> 