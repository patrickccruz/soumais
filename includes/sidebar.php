<?php
if (!isset($_SESSION)) {
    session_start();
}

// Determinar se estamos em uma subpasta ou na raiz
$isSubfolder = strpos($_SERVER['PHP_SELF'], '/page/') !== false;
$basePath = $isSubfolder ? '../' : '';
?>
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <!-- Categoria: Principal -->
    <li class="nav-heading">Principal</li>
    <li class="nav-item">
      <a class="nav-link" href="<?php echo $basePath; ?>index.php">
        <i class="bi bi-house"></i>
        <span>Sou Mais Acontece</span>
      </a>
    </li>

    <!-- Categoria: Ferramentas -->
    <li class="nav-heading">Ferramentas</li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#scripts-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-journal-text"></i><span>Lançamentos</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="scripts-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="<?php echo $basePath; ?>page/gerar-script.php">
            <i class="bi bi-circle"></i><span>Gerar lançamento</span>
          </a>
        </li>
        <li>
          <a href="<?php echo $basePath; ?>page/meus-scripts.php">
            <i class="bi bi-circle"></i><span>Meus lançamentos</span>
          </a>
        </li>
      </ul>
    </li>

    <!-- Categoria: Reembolsos -->
    <li class="nav-heading">Reembolsos</li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#reembolsos-nav" data-bs-toggle="collapse" href="#">
        <i class="bx bx-money"></i><span>Gestão de Reembolsos</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="reembolsos-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="<?php echo $basePath; ?>page/solicitar-reembolso.php">
            <i class="bi bi-circle"></i><span>Solicitar Reembolso</span>
          </a>
        </li>
        <li>
          <a href="<?php echo $basePath; ?>page/meus-reembolsos.php">
            <i class="bi bi-circle"></i><span>Meus Reembolsos</span>
          </a>
        </li>
      </ul>
    </li>

    <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true): ?>
    <!-- Categoria: Administração -->
    <li class="nav-heading">Administração</li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#admin-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-gear"></i><span>Configurações</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="admin-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="<?php echo $basePath; ?>page/gerenciar-usuarios.php">
            <i class="bi bi-circle"></i><span>Gerenciar Usuários</span>
          </a>
        </li>
      </ul>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#relatorios-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-file-earmark-text"></i><span>Relatórios</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="relatorios-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li>
          <a href="<?php echo $basePath; ?>page/todos-reembolsos.php">
            <i class="bi bi-circle"></i><span>Todos os Reembolsos</span>
          </a>
        </li>
        <li>
          <a href="<?php echo $basePath; ?>page/visualizar-relatorios.php">
            <i class="bi bi-circle"></i><span>Relatórios de Atendimento</span>
          </a>
        </li>
      </ul>
    </li>
    <?php endif; ?>
  </ul>
</aside><!-- End Sideba--> 