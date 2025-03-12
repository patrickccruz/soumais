<?php
// Iniciar buffer de saída
ob_start();

// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Registrar erro fatal personalizado
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<div style='color:red; border:2px solid red; padding:10px; margin:10px; background:#ffeeee;'>";
        echo "<h2>Erro Fatal Detectado</h2>";
        echo "<p><strong>Tipo:</strong> " . $error['type'] . "</p>";
        echo "<p><strong>Mensagem:</strong> " . $error['message'] . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $error['file'] . "</p>";
        echo "<p><strong>Linha:</strong> " . $error['line'] . "</p>";
        echo "</div>";
    }
});

// Função para testar inclusão segura
function incluir_seguro($arquivo) {
    echo "<div style='margin:10px 0; border:1px solid #ccc; padding:10px;'>";
    echo "<h3>Tentando incluir: $arquivo</h3>";
    
    if (!file_exists($arquivo)) {
        echo "<p style='color:red'>❌ Arquivo não encontrado: $arquivo</p>";
        return false;
    }
    
    echo "<p>✅ Arquivo encontrado, tentando incluir...</p>";
    
    // Capturar saída e erros
    ob_start();
    $resultado = @include($arquivo);
    $output = ob_get_clean();
    
    if ($resultado === false) {
        echo "<p style='color:red'>❌ Falha ao incluir o arquivo</p>";
    } else {
        echo "<p style='color:green'>✅ Arquivo incluído com sucesso</p>";
    }
    
    if (!empty($output)) {
        echo "<div style='background:#f5f5f5; padding:5px; margin-top:5px;'>";
        echo "<p><strong>Saída durante a inclusão:</strong></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    } else {
        echo "<p><em>Nenhuma saída durante a inclusão</em></p>";
    }
    
    echo "</div>";
    return $resultado;
}

// Função para verificar conexão BD
function testar_conexao_bd($usuario, $senha) {
    echo "<div style='margin:10px 0; border:1px solid #ccc; padding:10px;'>";
    echo "<h3>Testando conexão com usuário: $usuario</h3>";
    
    try {
        $conn = new mysqli("localhost", $usuario, $senha, "sou_digital");
        
        if ($conn->connect_error) {
            echo "<p style='color:red'>❌ Falha na conexão: " . $conn->connect_error . "</p>";
            return false;
        }
        
        echo "<p style='color:green'>✅ Conexão bem-sucedida!</p>";
        
        // Testar consulta
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<p><strong>Tabelas encontradas:</strong></p><ul>";
            while ($row = $result->fetch_row()) {
                echo "<li>" . htmlspecialchars($row[0]) . "</li>";
            }
            echo "</ul>";
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Erro: " . $e->getMessage() . "</p>";
        return false;
    }
    
    echo "</div>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Páginas em Branco</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.5; }
        .section { border: 1px solid #ddd; margin-bottom: 20px; padding: 15px; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #444; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .actions { margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Páginas em Branco - SouDigital</h1>
    
    <div class="section">
        <h2>1. Verificação de Ambiente PHP</h2>
        <p><strong>Versão do PHP:</strong> <?php echo phpversion(); ?></p>
        <p><strong>display_errors:</strong> <?php echo ini_get('display_errors') ? 'Ativado' : 'Desativado'; ?></p>
        <p><strong>error_reporting:</strong> <?php echo ini_get('error_reporting'); ?></p>
        <p><strong>Memória Limite:</strong> <?php echo ini_get('memory_limit'); ?></p>
        <p><strong>Tempo Máximo de Execução:</strong> <?php echo ini_get('max_execution_time'); ?> segundos</p>
        <p><strong>Extensões Críticas:</strong></p>
        <ul>
            <?php
            $extensoes = ['mysqli', 'json', 'session', 'mbstring'];
            foreach ($extensoes as $ext) {
                $status = extension_loaded($ext) ? "✅ Carregada" : "❌ NÃO carregada";
                $class = extension_loaded($ext) ? "success" : "error";
                echo "<li class='$class'>$ext: $status</li>";
            }
            ?>
        </ul>
    </div>
    
    <div class="section">
        <h2>2. Verificação de Arquivos Essenciais</h2>
        <?php
        $arquivos_essenciais = [
            __DIR__ . '/db.php',
            __DIR__ . '/index.php',
            __DIR__ . '/header.php',
            __DIR__ . '/includes/header.php',
            __DIR__ . '/includes/sidebar.php'
        ];
        
        foreach ($arquivos_essenciais as $arquivo) {
            $status = file_exists($arquivo) ? "✅ Encontrado" : "❌ NÃO encontrado";
            $class = file_exists($arquivo) ? "success" : "error";
            echo "<p class='$class'>$arquivo: $status</p>";
            
            if (file_exists($arquivo)) {
                echo "<p>Permissões: " . substr(sprintf('%o', fileperms($arquivo)), -4) . "</p>";
                echo "<p>Tamanho: " . filesize($arquivo) . " bytes</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Teste de Inclusão de Arquivos</h2>
        <?php
        incluir_seguro(__DIR__ . '/db.php');
        ?>
    </div>
    
    <div class="section">
        <h2>4. Teste de Conexão com Banco de Dados</h2>
        <?php
        testar_conexao_bd('sou_digital', 'SuaSenhaSegura123!');
        testar_conexao_bd('root', '');
        ?>
    </div>
    
    <div class="section">
        <h2>5. Verificação de Sessão</h2>
        <?php
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        echo "<p><strong>Status da Sessão:</strong> ";
        if (session_status() == PHP_SESSION_ACTIVE) {
            echo "<span class='success'>Ativa</span>";
        } else {
            echo "<span class='error'>Inativa</span>";
        }
        echo "</p>";
        
        echo "<p><strong>ID da Sessão:</strong> " . session_id() . "</p>";
        echo "<p><strong>Conteúdo da Sessão:</strong></p>";
        echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
        ?>
    </div>
    
    <div class="section">
        <h2>6. Últimos Erros do Log</h2>
        <pre><?php
        $log_path = '/var/log/apache2/soudigital_error.log';
        if (file_exists($log_path) && is_readable($log_path)) {
            $command = 'tail -n 20 ' . escapeshellarg($log_path);
            system($command);
        } else {
            echo "Não foi possível ler o arquivo de log.";
        }
        ?></pre>
    </div>
    
    <div class="actions">
        <h2>Ações Recomendadas</h2>
        <ol>
            <li>Verificar se o arquivo db.php está sendo corretamente incluído em todas as páginas</li>
            <li>Garantir que a variável $conn está disponível após a inclusão do db.php</li>
            <li>Verificar permissões de arquivos (devem ser legíveis pelo usuário www-data)</li>
            <li>Verificar se o banco de dados está acessível com as credenciais utilizadas</li>
            <li>Remover quaisquer caracteres de controle ou BOM (Byte Order Mark) dos arquivos PHP</li>
        </ol>
    </div>
    
    <p><a href="index.php">Voltar para a página principal</a> | <a href="teste-db2.php">Teste de conexão</a></p>
</body>
</html>
<?php
// Liberar buffer
ob_end_flush();
?> 