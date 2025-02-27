<?php
session_start();
require_once '../db.php';

// Verificação de autenticação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: autenticacao.php');
    exit;
}

// Verificação de permissão de administrador
if (!isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Buscar todos os usuários
$query = "SELECT id, name, username, email, profile_image, created_at, is_admin, status FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
$users = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

include_once '../includes/header.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Gerenciar Usuários</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
        <li class="breadcrumb-item active">Gerenciar Usuários</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Lista de Usuários</h5>

            <?php
            // Exibir mensagens de erro se existirem
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            
            // Exibir mensagens de sucesso se existirem
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="mb-3">
              <a href="criar-usuario.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Novo Usuário
              </a>
            </div>

            <!-- Lista de Usuários -->
            <div class="table-responsive">
              <table class="table table-hover datatable">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Usuário</th>
                    <th scope="col">Email</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Status</th>
                    <th scope="col">Criado em</th>
                    <th scope="col">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $user): ?>
                  <tr>
                    <th scope="row"><?php echo $user['id']; ?></th>
                    <td>
                      <?php if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])): ?>
                      <img src="../<?php echo $user['profile_image']; ?>" alt="Perfil" class="rounded-circle" width="30">
                      <?php endif; ?>
                      <?php echo htmlspecialchars($user['name']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                      <?php if ($user['is_admin']): ?>
                      <span class="badge bg-primary">Administrador</span>
                      <?php else: ?>
                      <span class="badge bg-secondary">Usuário</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($user['status'] === 'active'): ?>
                      <span class="badge bg-success">Ativo</span>
                      <?php elseif ($user['status'] === 'inactive'): ?>
                      <span class="badge bg-warning text-dark">Inativo</span>
                      <?php else: ?>
                      <span class="badge bg-danger">Bloqueado</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                    <td>
                      <div class="d-flex">
                        <a href="editar-usuario.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info me-1">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
                        <a href="excluir-usuario.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                          <i class="bi bi-trash"></i>
                        </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <!-- End Table -->

          </div>
        </div>
      </div>
    </div>
  </section>
</main><!-- End #main -->

<?php include_once '../includes/footer.php'; ?>
