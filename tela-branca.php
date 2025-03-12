<?php
// Ativar buffer de saída para verificar se algo está sendo enviado
ob_start();

// Arquivo para diagnosticar páginas em branco
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para verificar se existe output buffer
function verificar_output() {
    $output = ob_get_contents();
    if (empty($output)) {
        echo "<p style='color:red'>Nenhuma saída detectada antes deste ponto!</p>";
    } else {
        echo "<p style='color:green'>Há " . strlen($output) . " bytes de saída antes deste ponto.</p>";
    }
}

// Início do HTML
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico de Tela Branca</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Tela Branca</h1>";

echo "<div class='test-section'>
    <h2>Teste 1: Exibição de Erros</h2>";
    verificar_output();
echo "</div>";

// Teste de inclusão de arquivos
echo "<div class='test-section'>
    <h2>Teste 2: Inclusão de Arquivos</h2>";
    
    $arquivos_para_testar = ['db.php', 'db2.php', 'header.php'];
    foreach ($arquivos_para_testar as $arquivo) {
        echo "<h3>Testando inclusão de: $arquivo</h3>";
        try {
            echo "Tentando incluir $arquivo...<br>";
            $caminho = __DIR__ . '/' . $arquivo;
            if (file_exists($caminho)) {
                echo "Arquivo existe no caminho: $caminho<br>";
                
                // Capturar qualquer saída ou erro durante a inclusão
                ob_start();
                $resultado = @include_once($arquivo);
                $output = ob_get_clean();
                
                if ($resultado === false) {
                    echo "<span class='error'>Falha ao incluir o arquivo.</span><br>";
                } else {
                    echo "<span class='success'>Arquivo incluído com sucesso.</span><br>";
                }
                
                if (!empty($output)) {
                    echo "Saída durante inclusão:<br><pre>" . htmlspecialchars($output) . "</pre>";
                } else {
                    echo "Nenhuma saída durante a inclusão.<br>";
                }
                
            } else {
                echo "<span class='error'>Arquivo não encontrado: $caminho</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>Erro ao incluir arquivo: " . $e->getMessage() . "</span><br>";
        }
    }
echo "</div>";

// Teste de conexão com o banco de dados
echo "<div class='test-section'>
    <h2>Teste 3: Conexão com o Banco de Dados</h2>";
    
    try {
        $conn = new mysqli("localhost", "sou_digital", "SuaSenhaSegura123!", "sou_digital");
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        echo "<p class='success'>Conexão com banco de dados bem-sucedida!</p>";
        
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<p>Tabelas no banco de dados:</p><ul>";
            while ($row = $result->fetch_row()) {
                echo "<li>" . htmlspecialchars($row[0]) . "</li>";
            }
            echo "</ul>";
        }
        
        $conn->close();
    } catch (Exception $e) {
        echo "<p class='error'>Erro: " . $e->getMessage() . "</p>";
    }
echo "</div>";

// Verificação de memória e recursos
echo "<div class='test-section'>
    <h2>Teste 4: Memória e Recursos</h2>";
    echo "<p>Limite de memória: " . ini_get('memory_limit') . "</p>";
    echo "<p>Tempo máximo de execução: " . ini_get('max_execution_time') . " segundos</p>";
    echo "<p>Uso atual de memória: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";
    echo "<p>Pico de uso de memória: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB</p>";
echo "</div>";

// Verificação de extensões do PHP
echo "<div class='test-section'>
    <h2>Teste 5: Extensões do PHP</h2>";
    $extensoes_necessarias = ['mysqli', 'mbstring', 'json', 'session'];
    echo "<ul>";
    foreach ($extensoes_necessarias as $ext) {
        if (extension_loaded($ext)) {
            echo "<li class='success'>$ext está carregada</li>";
        } else {
            echo "<li class='error'>$ext NÃO está carregada</li>";
        }
    }
    echo "</ul>";
echo "</div>";

// Verificação de headers HTTP
echo "<div class='test-section'>
    <h2>Teste 6: Headers HTTP</h2>";
    echo "<p>Headers que seriam enviados:</p><pre>";
    $lista_headers = headers_list();
    if (empty($lista_headers)) {
        echo "Nenhum header definido ainda.";
    } else {
        foreach ($lista_headers as $header) {
            echo htmlspecialchars($header) . "\n";
        }
    }
    echo "</pre>";
echo "</div>";

// Fim do HTML
echo "</body></html>";

// Liberar o buffer
ob_end_flush();
?> 