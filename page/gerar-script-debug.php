<?php
// Arquivo de diagnóstico para gerar-script.php
// Iniciar buffer de saída
ob_start();

// Ativar exibição de erros para mostrar problemas
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para registrar e exibir mensagens de diagnóstico
function debug_log($mensagem, $tipo = 'info') {
    $estilo = 'color: blue;';
    if ($tipo == 'erro') $estilo = 'color: red; font-weight: bold;';
    if ($tipo == 'sucesso') $estilo = 'color: green;';
    
    echo "<div style='margin: 5px 0; padding: 5px; border: 1px solid #ccc; $estilo'>";
    echo $mensagem;
    echo "</div>";
    
    // Também registrar no log do PHP
    error_log("[gerar-script-debug] $mensagem");
}

// Verificar caminho de includes
$includes_dir = dirname(__DIR__) . '/includes';
debug_log("Verificando diretório de includes: $includes_dir");
if (!is_dir($includes_dir)) {
    debug_log("❌ Diretório de includes não encontrado!", 'erro');
} else {
    debug_log("✅ Diretório de includes encontrado", 'sucesso');
    
    // Verificar arquivos específicos
    $db_file = $includes_dir . '/db.php';
    $header_file = $includes_dir . '/header.php';
    $footer_file = $includes_dir . '/footer.php';
    $upload_functions_file = $includes_dir . '/upload_functions.php';
    
    debug_log("Verificando arquivos necessários:");
    foreach ([$db_file, $header_file, $footer_file, $upload_functions_file] as $arquivo) {
        if (file_exists($arquivo)) {
            debug_log("✅ Arquivo encontrado: " . basename($arquivo), 'sucesso');
        } else {
            debug_log("❌ Arquivo NÃO encontrado: " . basename($arquivo), 'erro');
        }
    }
}

echo "<h1>Diagnóstico da Página gerar-script.php</h1>";
echo "<p>Esta página verifica cada etapa do processamento para identificar o problema.</p>";

// Tentar iniciar sessão
echo "<h2>1. Verificando Sessão</h2>";
try {
    session_start();
    debug_log("✅ Sessão iniciada com sucesso", 'sucesso');
    debug_log("ID da Sessão: " . session_id());
    
    // Verificar se usuário está logado
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
        debug_log("❌ Usuário não está logado na sessão", 'erro');
    } else {
        debug_log("✅ Usuário está logado na sessão", 'sucesso');
        if (isset($_SESSION['user'])) {
            debug_log("✅ Dados do usuário na sessão: " . print_r($_SESSION['user'], true), 'sucesso');
        }
    }
} catch (Exception $e) {
    debug_log("❌ Erro ao iniciar sessão: " . $e->getMessage(), 'erro');
}

// Tentar incluir funções de upload
echo "<h2>2. Verificando Inclusão de upload_functions.php</h2>";
try {
    if (file_exists($upload_functions_file)) {
        debug_log("Tentando incluir upload_functions.php");
        include_once $upload_functions_file;
        debug_log("✅ Arquivo upload_functions.php incluído com sucesso", 'sucesso');
    } else {
        debug_log("❌ Arquivo upload_functions.php não encontrado", 'erro');
    }
} catch (Exception $e) {
    debug_log("❌ Erro ao incluir upload_functions.php: " . $e->getMessage(), 'erro');
}

// Tentar conectar ao banco de dados
echo "<h2>3. Verificando Conexão com Banco de Dados</h2>";
try {
    // Primeiro, verificar se o arquivo db.php existe
    if (file_exists($db_file)) {
        debug_log("Tentando incluir db.php para conexão");
        include_once $db_file;
        
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            debug_log("✅ Conexão com banco de dados estabelecida via db.php", 'sucesso');
            
            // Testar uma consulta
            $test_query = "SHOW TABLES";
            $result = $conn->query($test_query);
            
            if ($result) {
                debug_log("✅ Consulta de teste executada com sucesso", 'sucesso');
                
                // Exibir tabelas encontradas
                echo "<p>Tabelas no banco de dados:</p><ul>";
                while ($row = $result->fetch_row()) {
                    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
                }
                echo "</ul>";
            } else {
                debug_log("❌ Falha ao executar consulta de teste: " . $conn->error, 'erro');
            }
        } else {
            debug_log("❌ Variável \$conn não está disponível ou tem erro após incluir db.php", 'erro');
            
            // Tentar conectar diretamente como fallback
            debug_log("Tentando conexão direta como fallback");
            $direct_conn = new mysqli("localhost", "sou_digital", "SuaSenhaSegura123!", "sou_digital");
            
            if ($direct_conn->connect_error) {
                debug_log("❌ Falha na conexão direta: " . $direct_conn->connect_error, 'erro');
            } else {
                debug_log("✅ Conexão direta bem-sucedida", 'sucesso');
                $conn = $direct_conn; // Disponibilizar conexão para o restante do script
            }
        }
    } else {
        debug_log("❌ Arquivo db.php não encontrado", 'erro');
        
        // Tentar conectar diretamente como fallback
        debug_log("Tentando conexão direta como fallback");
        $direct_conn = new mysqli("localhost", "sou_digital", "SuaSenhaSegura123!", "sou_digital");
        
        if ($direct_conn->connect_error) {
            debug_log("❌ Falha na conexão direta: " . $direct_conn->connect_error, 'erro');
        } else {
            debug_log("✅ Conexão direta bem-sucedida", 'sucesso');
            $conn = $direct_conn; // Disponibilizar conexão para o restante do script
        }
    }
} catch (Exception $e) {
    debug_log("❌ Erro ao conectar ao banco de dados: " . $e->getMessage(), 'erro');
}

// Tentar incluir header
echo "<h2>4. Verificando Inclusão do Header</h2>";
try {
    if (file_exists($header_file)) {
        debug_log("O arquivo header.php existe, mas não será incluído para evitar conflitos na página de diagnóstico");
    } else {
        debug_log("❌ Arquivo header.php não encontrado", 'erro');
    }
} catch (Exception $e) {
    debug_log("❌ Erro ao verificar header.php: " . $e->getMessage(), 'erro');
}

// Verificar permissões de diretórios relevantes
echo "<h2>5. Verificando Permissões de Diretórios</h2>";
try {
    $diretorios_para_verificar = [
        dirname(__DIR__) . '/uploads',
        dirname(__DIR__) . '/logs'
    ];
    
    foreach ($diretorios_para_verificar as $dir) {
        if (!is_dir($dir)) {
            debug_log("Diretório $dir não existe, tentando criar...");
            
            if (!@mkdir($dir, 0777, true)) {
                debug_log("❌ Falha ao criar diretório $dir", 'erro');
            } else {
                debug_log("✅ Diretório $dir criado com sucesso", 'sucesso');
            }
        } else {
            debug_log("✅ Diretório $dir existe", 'sucesso');
            
            if (is_writable($dir)) {
                debug_log("✅ Diretório $dir tem permissão de escrita", 'sucesso');
            } else {
                debug_log("❌ Diretório $dir NÃO tem permissão de escrita", 'erro');
                debug_log("Tentando definir permissões...");
                chmod($dir, 0777);
                
                if (is_writable($dir)) {
                    debug_log("✅ Permissões ajustadas com sucesso", 'sucesso');
                } else {
                    debug_log("❌ Falha ao ajustar permissões", 'erro');
                }
            }
        }
    }
} catch (Exception $e) {
    debug_log("❌ Erro ao verificar permissões de diretórios: " . $e->getMessage(), 'erro');
}

// Exibir instruções para corrigir o problema
echo "<h2>Como Corrigir a Página gerar-script.php</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd;'>";
echo "<p>Baseado nos resultados acima, siga estas instruções para corrigir a página:</p>";
echo "<ol>";
echo "<li>Verifique se os arquivos de includes estão nos locais corretos (especialmente db.php)</li>";
echo "<li>Confirme se a conexão com o banco de dados está funcionando</li>";
echo "<li>Certifique-se que os diretórios de uploads e logs existem e têm permissões adequadas</li>";
echo "<li>Se necessário, edite o arquivo original gerar-script.php para adicionar tratamento de erros</li>";
echo "</ol>";

echo "<p><strong>Links úteis:</strong></p>";
echo "<ul>";
echo "<li><a href='../page-debug.php'>Diagnóstico Geral do Site</a></li>";
echo "<li><a href='../tela-branca.php'>Diagnóstico de Tela Branca</a></li>";
echo "<li><a href='gerar-script.php'>Tentar Acessar gerar-script.php Original</a></li>";
echo "</ul>";
echo "</div>";

// Liberar buffer
ob_end_flush();
?> 