<?php
// Arquivo de configuração para conexão com banco de dados
// Última atualização: 2025-03-13

// Configuração de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credenciais do banco de dados
$servername = "localhost";
$username = "sou_digital";  // Usuário específico para a aplicação
$password = "SuaSenhaSegura123!";  // Senha do usuário sou_digital
$dbname = "sou_digital";  // Nome do banco de dados

// Tentar conexão com as credenciais principais
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Verificar conexão
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão primária: " . $conn->connect_error);
    }
    
    // Configurar charset para UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro de conexão: " . $e->getMessage());
    
    // Se falhar, tentar conexão alternativa (somente para ambientes de desenvolvimento)
    try {
        // Tentar com usuário root (apenas para desenvolvimento)
        $conn = new mysqli($servername, "root", "", $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão secundária: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        error_log("Usando conexão alternativa com root (não recomendado para produção)");
        
    } catch (Exception $e2) {
        die("Erro crítico de conexão com banco de dados: " . $e2->getMessage());
    }
}
?>