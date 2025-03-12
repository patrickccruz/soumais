<?php
session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Simples de Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .info { background-color: #f8f9fa; padding: 10px; border-left: 4px solid #17a2b8; margin-bottom: 20px; }
        pre { background: #f8f8f8; padding: 10px; overflow: auto; max-height: 300px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .error { color: #dc3545; font-weight: bold; }
        .success { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Teste Simples de Upload de Arquivo</h1>
    
    <div class="card">
        <h2>Configurações do PHP</h2>
        <ul>
            <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
            <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
            <li>memory_limit: <?php echo ini_get('memory_limit'); ?></li>
            <li>max_file_uploads: <?php echo ini_get('max_file_uploads'); ?></li>
            <li>upload_tmp_dir: <?php echo ini_get('upload_tmp_dir') ?: 'Padrão do sistema'; ?></li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Formulário de Upload</h2>
        <div class="info">
            <p>Este formulário está configurado para testar o upload básico. Selecione um arquivo pequeno (menos de 1MB) para testar.</p>
        </div>
        
        <form action="processar-upload.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
            
            <div class="form-group">
                <label for="arquivo">Selecione um arquivo:</label>
                <input type="file" name="arquivo" id="arquivo">
            </div>
            
            <button type="submit">Enviar Arquivo</button>
        </form>
    </div>
    
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
    <div class="card">
        <h2 class="success">Upload Bem-sucedido!</h2>
        <p>O arquivo foi enviado com sucesso.</p>
        <?php if (isset($_GET['path'])): ?>
            <p>Caminho: <?php echo htmlspecialchars($_GET['path']); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
    <div class="card">
        <h2 class="error">Erro no Upload</h2>
        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>Dicas para Solução de Problemas</h2>
        <ul>
            <li>Certifique-se de que o arquivo não é muito grande (limite atual: <?php echo ini_get('upload_max_filesize'); ?>)</li>
            <li>Verifique se o diretório de uploads tem as permissões corretas (777 para teste)</li>
            <li>Certifique-se de que o formulário possui o atributo enctype="multipart/form-data"</li>
            <li>Verifique se o PHP tem permissões para escrever arquivos temporários</li>
        </ul>
    </div>
</body>
</html> 