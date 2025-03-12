<?php
session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes

require_once 'includes/upload_functions.php';

// Verificar e criar diretórios necessários
$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/blog',
    __DIR__ . '/uploads/blog/editor',
    __DIR__ . '/uploads/profiles',
    __DIR__ . '/uploads/reimbursements',
    __DIR__ . '/uploads/reports'
];

$logs = [];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            $logs[] = "Diretório criado: $dir";
        } else {
            $logs[] = "ERRO ao criar diretório: $dir";
        }
    } else {
        $logs[] = "Diretório já existe: $dir";
    }
}

// Teste de escrita em cada diretório
foreach ($dirs as $dir) {
    $testfile = $dir . '/teste.txt';
    if (file_put_contents($testfile, "Teste de escrita em " . date('Y-m-d H:i:s'))) {
        $logs[] = "Arquivo de teste criado com sucesso: $testfile";
    } else {
        $logs[] = "ERRO ao criar arquivo de teste: $testfile";
    }
}

// Relatório de permissões
foreach ($dirs as $dir) {
    $perms = substr(sprintf('%o', fileperms($dir)), -4);
    $logs[] = "Permissões do diretório $dir: $perms";
}

// Verificar se o PHP pode mover arquivos
$tempfile = tempnam(sys_get_temp_dir(), 'upload_test');
file_put_contents($tempfile, 'Conteúdo de teste');
$destfile = __DIR__ . '/uploads/teste_move.txt';

if (move_uploaded_file_safe($tempfile, 'uploads/teste_move.txt')) {
    $logs[] = "Arquivo movido com sucesso para: $destfile";
} else {
    $logs[] = "ERRO ao mover arquivo para: $destfile";
}

unlink($tempfile);

// Exibir resultados
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .log { margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-left: 4px solid #ccc; }
        .success { border-left-color: green; }
        .error { border-left-color: red; background: #ffe6e6; }
        .upload-form { margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Teste de Configuração de Upload</h1>
    
    <h2>Logs de Verificação</h2>
    <?php foreach ($logs as $log): ?>
        <?php 
            $class = "log";
            if (strpos($log, "ERRO") !== false) {
                $class .= " error";
            } elseif (strpos($log, "sucesso") !== false) {
                $class .= " success";
            }
        ?>
        <div class="<?php echo $class; ?>"><?php echo htmlspecialchars($log); ?></div>
    <?php endforeach; ?>
    
    <h2>Teste de Upload Manual</h2>
    <div class="upload-form">
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div>
                <label for="file">Selecione uma imagem:</label>
                <input type="file" name="file" id="file" accept="image/*">
            </div>
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html> 