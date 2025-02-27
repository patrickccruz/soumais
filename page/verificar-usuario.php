<?php
// Este arquivo é apenas um redirecionador para o processador de login real

// Redireciona todos os dados do formulário para o processador real
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header("Location: processar-login.php?" . http_build_query($_POST));
    exit;
} else {
    // Se alguém tentar acessar este arquivo diretamente, redirecione para a página de login
    header("Location: autenticacao.php");
    exit;
}
?>
