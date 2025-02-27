<?php
// Este script verifica todos os arquivos PHP em busca de conexões diretas ao banco de dados
// que não utilizam o arquivo db.php centralizado

echo "Verificando conexões diretas ao banco de dados...\n";

// Função para verificar um diretório recursivamente
function checkDirectory($dir) {
    $results = array();
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Recursivamente verificar subdiretórios
            $results = array_merge($results, checkDirectory($path));
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            // Verificar apenas arquivos PHP
            $content = file_get_contents($path);
            
            // Procurar por padrões de conexão direta ao banco de dados
            if (preg_match('/new\s+mysqli\s*\(/i', $content) && 
                !preg_match('/\/\/.*new\s+mysqli/i', $content)) { // Excluir linhas comentadas
                
                // Verificar se o arquivo já inclui db.php
                $includes_db = preg_match('/require(?:_once)?\s+.*db\.php/i', $content);
                
                if (!$includes_db) {
                    $results[] = array(
                        'file' => $path,
                        'issue' => 'Conexão direta ao banco de dados sem incluir db.php'
                    );
                } else {
                    $results[] = array(
                        'file' => $path,
                        'issue' => 'Possível uso redundante de mysqli (já inclui db.php)'
                    );
                }
            }
        }
    }
    
    return $results;
}

// Verificar o diretório raiz e todos os subdiretórios
$issues = checkDirectory(__DIR__);

if (empty($issues)) {
    echo "Nenhuma conexão direta ao banco de dados encontrada!\n";
} else {
    echo "Encontradas " . count($issues) . " possíveis conexões diretas:\n";
    
    foreach ($issues as $issue) {
        echo "- " . str_replace(__DIR__ . '/', '', $issue['file']) . ": " . $issue['issue'] . "\n";
    }
    
    echo "\nRecomendação: Substitua conexões diretas por 'require_once __DIR__ . '/../db.php';'\n";
}

echo "\nVerificação concluída!\n";
?> 