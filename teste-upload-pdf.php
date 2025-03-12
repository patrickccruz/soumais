<?php
session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes

require_once 'includes/upload_functions.php';

// Criar um PDF simples para teste
$pdf_content = <<<EOD
%PDF-1.4
1 0 obj
<</Type /Catalog /Pages 2 0 R>>
endobj
2 0 obj
<</Type /Pages /Kids [3 0 R] /Count 1>>
endobj
3 0 obj
<</Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 500 800] /Contents 6 0 R>>
endobj
4 0 obj
<</Font <</F1 5 0 R>>>>
endobj
5 0 obj
<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>
endobj
6 0 obj
<</Length 44>>
stream
BT /F1 24 Tf 175 720 Td (PDF Teste Sou Digital!) Tj ET
endstream
endobj
xref
0 7
0000000000 65535 f
0000000009 00000 n
0000000056 00000 n
0000000111 00000 n
0000000212 00000 n
0000000250 00000 n
0000000317 00000 n
trailer
<</Size 7 /Root 1 0 R>>
startxref
406
%%EOF
EOD;

$test_pdf_path = __DIR__ . '/uploads/teste.pdf';
file_put_contents($test_pdf_path, $pdf_content);

// Resultados do teste
$results = [];
$log_file = __DIR__ . '/upload_debug.log';
file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] ==== INICIANDO TESTE DE UPLOAD PDF ====\n", FILE_APPEND);

// 1. Testar a criação de diretórios
$test_dir = __DIR__ . '/uploads/test_reports/123';
if (!is_dir($test_dir)) {
    if (mkdir($test_dir, 0777, true)) {
        $results[] = ["Criação de diretório de teste", "SUCESSO", "Diretório $test_dir criado com sucesso"];
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Diretório de teste criado: $test_dir\n", FILE_APPEND);
    } else {
        $results[] = ["Criação de diretório de teste", "FALHA", "Não foi possível criar $test_dir"];
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERRO ao criar diretório de teste: $test_dir\n", FILE_APPEND);
    }
} else {
    $results[] = ["Verificação de diretório de teste", "SUCESSO", "Diretório $test_dir já existe"];
}

// 2. Testar a função get_upload_path
try {
    $upload_path = get_upload_path('reports', ['report_id' => 123]);
    $results[] = ["Função get_upload_path", "SUCESSO", "Caminho gerado: $upload_path"];
} catch (Exception $e) {
    $results[] = ["Função get_upload_path", "FALHA", "Erro: " . $e->getMessage()];
}

// 3. Testar permissões do diretório uploads
$perm_check = [];
$dirs_to_check = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/reports',
    $test_dir
];

foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $is_writable = is_writable($dir) ? "Sim" : "Não";
        $owner = posix_getpwuid(fileowner($dir))['name'] ?? 'Desconhecido';
        $group = posix_getgrgid(filegroup($dir))['name'] ?? 'Desconhecido';
        
        $perm_check[] = [
            "Diretório" => $dir,
            "Permissões" => $perms,
            "Gravável" => $is_writable,
            "Proprietário" => $owner,
            "Grupo" => $group
        ];
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Permissões do diretório $dir: $perms, Gravável: $is_writable, Proprietário: $owner, Grupo: $group\n", FILE_APPEND);
    }
}

// 4. Testar movimento de arquivo diretamente
$test_dest = $test_dir . '/test_direct_copy.pdf';
if (copy($test_pdf_path, $test_dest)) {
    $results[] = ["Cópia direta de arquivo", "SUCESSO", "Arquivo copiado para $test_dest"];
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Arquivo copiado diretamente para: $test_dest\n", FILE_APPEND);
} else {
    $results[] = ["Cópia direta de arquivo", "FALHA", "Não foi possível copiar para $test_dest"];
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERRO na cópia direta para: $test_dest\n", FILE_APPEND);
}

// 5. Testar a função move_uploaded_file_safe (simulando)
$test_dest = $test_dir . '/test_upload_func.pdf';
$result = move_uploaded_file_safe($test_pdf_path, str_replace(__DIR__ . '/', '', $test_dest));
if ($result) {
    $results[] = ["Função move_uploaded_file_safe", "SUCESSO", "Arquivo movido para $test_dest"];
} else {
    $results[] = ["Função move_uploaded_file_safe", "FALHA", "Não foi possível mover para $test_dest"];
}

// 6. Configurações do PHP relevantes para upload
$php_configs = [
    "upload_max_filesize" => ini_get('upload_max_filesize'),
    "post_max_size" => ini_get('post_max_size'),
    "max_file_uploads" => ini_get('max_file_uploads'),
    "memory_limit" => ini_get('memory_limit'),
    "file_uploads" => ini_get('file_uploads') ? "Ativado" : "Desativado",
    "upload_tmp_dir" => ini_get('upload_tmp_dir') ?: "Padrão do sistema"
];

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Configurações do PHP: " . json_encode($php_configs) . "\n", FILE_APPEND);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload de PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .result-item { margin-bottom: 10px; padding: 10px; border-left: 4px solid #ccc; }
        .success { border-left-color: green; background: #f0fff0; }
        .failure { border-left-color: red; background: #fff0f0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .upload-form { background: #f5f5f5; padding: 20px; border-radius: 5px; }
        .btn { padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Teste de Upload de PDF</h1>
    
    <div class="card">
        <h2>1. Resultados dos Testes Automáticos</h2>
        <?php foreach ($results as $result): ?>
            <div class="result-item <?= $result[1] == 'SUCESSO' ? 'success' : 'failure' ?>">
                <strong><?= htmlspecialchars($result[0]) ?>:</strong> 
                <?= htmlspecialchars($result[1]) ?> - 
                <?= htmlspecialchars($result[2]) ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="card">
        <h2>2. Permissões de Diretórios</h2>
        <table>
            <tr>
                <th>Diretório</th>
                <th>Permissões</th>
                <th>Gravável</th>
                <th>Proprietário</th>
                <th>Grupo</th>
            </tr>
            <?php foreach ($perm_check as $perm): ?>
                <tr>
                    <td><?= htmlspecialchars($perm['Diretório']) ?></td>
                    <td><?= htmlspecialchars($perm['Permissões']) ?></td>
                    <td><?= htmlspecialchars($perm['Gravável']) ?></td>
                    <td><?= htmlspecialchars($perm['Proprietário']) ?></td>
                    <td><?= htmlspecialchars($perm['Grupo']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="card">
        <h2>3. Configurações do PHP</h2>
        <table>
            <tr>
                <th>Configuração</th>
                <th>Valor</th>
            </tr>
            <?php foreach ($php_configs as $key => $value): ?>
                <tr>
                    <td><?= htmlspecialchars($key) ?></td>
                    <td><?= htmlspecialchars($value) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="card">
        <h2>4. Teste de Upload Manual</h2>
        <div class="upload-form">
            <form action="upload-pdf-test.php" method="post" enctype="multipart/form-data">
                <p>
                    <label for="pdfFile">Selecione um arquivo PDF:</label>
                    <input type="file" name="pdfFile" id="pdfFile" accept=".pdf">
                </p>
                <button type="submit" class="btn">Enviar</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <h2>5. Log de Depuração</h2>
        <pre style="background:#f8f8f8; padding:10px; overflow:auto; max-height:300px;">
<?php 
    if (file_exists($log_file)) {
        echo htmlspecialchars(file_get_contents($log_file));
    } else {
        echo "Arquivo de log não encontrado.";
    }
?>
        </pre>
    </div>
</body>
</html> 