<?php
session_start();
$is_page = true;
include_once '../includes/header.php';
?>

<?php include_once '../includes/sidebar.php'; ?>

<main id="main" class="main">
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="terms-content">
                    <div class="terms-header">
                        <h1><i class="bi bi-file-text me-2"></i>Termos de Postagem</h1>
                        <p>Diretrizes e regras para publicação no blog Sou + Digital</p>
                    </div>

                    <div class="terms-section">
                        <h2><i class="bi bi-pencil-square"></i>1. Diretrizes de Conteúdo</h2>
                        <p>Ao criar um post no blog Sou + Digital, você concorda em seguir estas diretrizes:</p>
                        <ul>
                            <li>O conteúdo deve ser original ou devidamente creditado</li>
                            <li>Não é permitido conteúdo ofensivo, discriminatório ou ilegal</li>
                            <li>As imagens utilizadas devem respeitar direitos autorais</li>
                            <li>Informações técnicas devem ser verificadas e precisas</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h2><i class="bi bi-check-circle"></i>2. Processo de Aprovação</h2>
                        <p>Todos os posts passam por um processo de revisão, exceto para administradores:</p>
                        <ul>
                            <li>Posts são revisados pela equipe de administração</li>
                            <li>O tempo de aprovação pode variar</li>
                            <li>Feedback será fornecido em caso de rejeição</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h2><i class="bi bi-shield-check"></i>3. Responsabilidades</h2>
                        <p>Ao publicar no blog, você assume responsabilidade por:</p>
                        <ul>
                            <li>Precisão das informações compartilhadas</li>
                            <li>Respeito aos direitos autorais</li>
                            <li>Qualidade e relevância do conteúdo</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h2><i class="bi bi-file-earmark-text"></i>4. Direitos de Uso</h2>
                        <p>Ao publicar no blog Sou + Digital:</p>
                        <ul>
                            <li>Você mantém os direitos autorais do seu conteúdo</li>
                            <li>Concede à plataforma o direito de exibir e compartilhar o conteúdo</li>
                            <li>Permite que outros usuários compartilhem o conteúdo com atribuição</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h2><i class="bi bi-gear"></i>5. Moderação</h2>
                        <p>A equipe de administração reserva-se o direito de:</p>
                        <ul>
                            <li>Editar ou remover conteúdo que viole os termos</li>
                            <li>Suspender contas que violem repetidamente as diretrizes</li>
                            <li>Atualizar estes termos conforme necessário</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
</a>

<?php include_once __DIR__ . '/../includes/footer.php'; ?> 