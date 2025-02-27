<?php
session_start();
$is_page = true;
include_once '../includes/header.php';
?>

<style>
    .terms-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .terms-section {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        border-left: 5px solid #4154f1;
        transition: transform 0.3s ease;
    }
    .terms-section:hover {
        transform: translateY(-5px);
    }
    .terms-section h2 {
        color: #4154f1;
        font-size: 1.5rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .terms-section p {
        color: #444;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    .terms-section ul {
        list-style: none;
        padding-left: 0;
    }
    .terms-section ul li {
        position: relative;
        padding: 10px 0 10px 35px;
        border-bottom: 1px solid #f0f0f0;
    }
    .terms-section ul li:last-child {
        border-bottom: none;
    }
    .terms-section ul li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #4154f1;
        font-weight: bold;
    }
    .terms-header {
        background: #4154f1;
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        text-align: center;
    }
    .terms-header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
    }
    .terms-header p {
        margin: 10px 0 0;
        opacity: 0.9;
    }
    @media (max-width: 768px) {
        .terms-content {
            padding: 10px;
        }
        .terms-section {
            padding: 20px;
        }
    }
</style>

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