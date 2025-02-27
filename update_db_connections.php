<?php
$directory = __DIR__ . '/page';
$files = glob($directory . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Verifica se o arquivo contém uma conexão direta com o banco
    if (strpos($content, "new mysqli('localhost'") !== false || 
        strpos($content, 'new mysqli("localhost"') !== false) {
        
        // Remove a conexão antiga
        $content = preg_replace(
            "/(\\$conn = new mysqli\\('localhost', '.*?', '.*?', '.*?'\\);(\r\n|\n)?)/",
            "require_once __DIR__ . '/../db.php';\n",
            $content
        );
        
        // Remove verificações de conexão redundantes
        $content = preg_replace(
            "/(if \\(\\$conn->connect_error\\) \\{(\r\n|\n).*?\\}(\r\n|\n)?)/s",
            "",
            $content
        );
        
        file_put_contents($file, $content);
        echo "Arquivo atualizado: " . basename($file) . "\n";
    }
}

echo "Atualização concluída!\n";
?> 