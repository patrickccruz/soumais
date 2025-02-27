<?php
// Caminho para o repositório Git
$repo_dir = '/var/www/html/soudigital';

// Chave secreta (você deve definir uma chave única)
$secret = "19042110";

// Cabeçalho com a assinatura do GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

// Obtenha o conteúdo do payload
$payload = file_get_contents('php://input');

// Log para debug
file_put_contents('/var/log/webhook.log', date('Y-m-d H:i:s') . " - Webhook recebido\n", FILE_APPEND);

// Verificar assinatura (segurança)
if ($signature) {
    $hash = 'sha1=' . hash_hmac('sha1', $payload, $secret);
    if (hash_equals($hash, $signature)) {
        // Execute o pull
        $output = [];
        $return_var = 0;
        
        // Registre os comandos sendo executados
        file_put_contents('/var/log/webhook.log', date('Y-m-d H:i:s') . " - Executando git pull\n", FILE_APPEND);
        
        // Mude para o diretório do repositório e faça pull
        exec('cd ' . $repo_dir . ' && git pull 2>&1', $output, $return_var);
        
        // Registre a saída do comando
        file_put_contents('/var/log/webhook.log', date('Y-m-d H:i:s') . " - Resultado:\n" . implode("\n", $output) . "\n", FILE_APPEND);
        
        // Atualize as permissões
        exec('chown -R www-data:www-data ' . $repo_dir, $output, $return_var);
        
        echo "Pull executado com sucesso";
    } else {
        file_put_contents('/var/log/webhook.log', date('Y-m-d H:i:s') . " - Erro de autenticação\n", FILE_APPEND);
        echo "Erro de autenticação";
    }
} else {
    file_put_contents('/var/log/webhook.log', date('Y-m-d H:i:s') . " - Requisição sem assinatura\n", FILE_APPEND);
    echo "Requisição sem assinatura";
}
?>
