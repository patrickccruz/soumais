<?php
// Modificar limites em tempo de execução
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '22M');
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

session_start();
$_SESSION['loggedin'] = true; // Forçar login para testes

// Verificar configurações aplicadas
$current_upload_max = ini_get('upload_max_filesize');
$current_post_max = ini_get('post_max_size');
$current_memory_limit = ini_get('memory_limit');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload de Arquivos Grandes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .alert-warning { background-color: #fff3cd; border-color: #ffeeba; color: #856404; }
        .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="file"] { padding: 6px; border: 1px solid #ccc; border-radius: 4px; width: 100%; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        pre { background: #f8f9fa; padding: 10px; overflow: auto; max-height: 300px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Teste de Upload de Arquivos Grandes (PDFs)</h1>
    
    <div class="card">
        <h2>Configurações Atuais do PHP</h2>
        <ul>
            <li><strong>upload_max_filesize:</strong> <?= $current_upload_max ?></li>
            <li><strong>post_max_size:</strong> <?= $current_post_max ?></li>
            <li><strong>memory_limit:</strong> <?= $current_memory_limit ?></li>
        </ul>
        
        <?php if ($current_upload_max != '20M'): ?>
            <div class="alert alert-warning">
                <strong>Atenção!</strong> As configurações não foram alteradas corretamente. 
                A alteração em tempo de execução de upload_max_filesize não funciona como esperado.
                O valor real será determinado pelo php.ini.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>Sucesso!</strong> As configurações foram alteradas corretamente.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Formulário para Testes</h2>
        <div class="alert alert-info">
            Este formulário está configurado para tentar fazer upload de arquivos maiores. 
            Selecione um arquivo PDF de até 20MB para testar.
        </div>
        
        <form action="processar-upload-grande.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="arquivo">Selecione um arquivo PDF (até 20MB):</label>
                <input type="file" name="arquivo" id="arquivo" accept=".pdf">
            </div>
            <button type="submit">Enviar Arquivo</button>
        </form>
    </div>
    
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert alert-success">
            <strong>Sucesso!</strong> Arquivo enviado com sucesso.
            <?php if (isset($_GET['path'])): ?>
                <p>Salvo em: <?= htmlspecialchars($_GET['path']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <strong>Erro!</strong> <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>Solução Alternativa para Envio de Arquivos Grandes</h2>
        <ol>
            <li>Reduza o tamanho do arquivo PDF (compressão) antes de enviar</li>
            <li>Divida arquivos grandes em partes menores</li>
            <li>Use um serviço de armazenamento externo (Google Drive, OneDrive) e compartilhe apenas o link</li>
        </ol>
        
        <div class="alert alert-info">
            <strong>Dica:</strong> Se você precisar enviar frequentemente arquivos grandes, 
            converse com o administrador do sistema para aumentar os limites no servidor.
        </div>
    </div>
</body>
</html> 