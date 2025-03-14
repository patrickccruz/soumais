<?php
session_start();
require_once '../db.php';

// Definição para indicar que estamos em uma página dentro da pasta 'page'
$is_page = true;

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
try {
    // Verificar qual tabela está disponível
    $users_table = 'users'; // Padrão
    
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($table_check->num_rows === 0) {
        // Se não existir, verificar 'usuarios'
        $table_check = $conn->query("SHOW TABLES LIKE 'usuarios'");
        if ($table_check->num_rows > 0) {
            $users_table = 'usuarios';
            error_log("gerenciar-usuarios.php: Usando tabela 'usuarios'");
        } else {
            error_log("gerenciar-usuarios.php: ATENÇÃO - Nenhuma tabela de usuários encontrada");
        }
    } else {
        error_log("gerenciar-usuarios.php: Usando tabela 'users'");
    }
    
    // Verificar colunas disponíveis
    $columns_result = $conn->query("DESCRIBE {$users_table}");
    $available_columns = [];
    
    if ($columns_result) {
        while ($column = $columns_result->fetch_assoc()) {
            $available_columns[] = $column['Field'];
        }
        error_log("gerenciar-usuarios.php: Colunas disponíveis: " . implode(", ", $available_columns));
    }
    
    $query = "SELECT id, name, username, email, profile_image, is_admin FROM {$users_table} ORDER BY id DESC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }
    
    $users = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Adicionar status padrão para compatibilidade com o template
            $row['status'] = 'active'; // Valor padrão para todos os usuários
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Erro em gerenciar-usuarios.php: " . $e->getMessage());
    $_SESSION['error'] = "Ocorreu um erro ao buscar os usuários: " . $e->getMessage();
}

include_once '../includes/header.php';
include_once '../includes/sidebar.php';
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
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error'] . 
                     '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['error']);
            }
            
            // Exibir mensagens de sucesso se existirem
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success'] . 
                     '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <a href="criar-usuario.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Novo Usuário
              </a>
              <div class="d-flex">
                <div class="input-group me-2">
                  <span class="input-group-text" id="search-addon"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control" id="userSearchBox" placeholder="Buscar usuário..." aria-label="Buscar" aria-describedby="search-addon">
                </div>
                <div class="dropdown">
                  <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-funnel"></i> Filtrar
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                    <li><a class="dropdown-item filter-option" data-filter="all" href="#">Todos</a></li>
                    <li><a class="dropdown-item filter-option" data-filter="admin" href="#">Administradores</a></li>
                    <li><a class="dropdown-item filter-option" data-filter="user" href="#">Usuários</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item filter-option" data-filter="active" href="#">Ativos</a></li>
                    <li><a class="dropdown-item filter-option" data-filter="inactive" href="#">Inativos</a></li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 1 Lista de Usuários -->
            <div class="table-responsive">
              <table class="table table-hover datatable" id="usersTable">
                <thead>
                  <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Usuário</th>
                    <th scope="col">Email</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Status</th>
                    <th scope="col">Criado em</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $user): ?>
                  <tr data-user-type="<?php echo $user['is_admin'] ? 'admin' : 'user'; ?>" data-user-status="<?php echo $user['status']; ?>">
                    <th scope="row"><?php echo $user['id']; ?></th>
                    <td>
                      <?php if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])): ?>
                      <img src="../<?php echo $user['profile_image']; ?>" alt="Perfil" class="rounded-circle" width="30">
                      <?php else: ?>
                      <div class="avatar-placeholder rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-secondary" style="width: 30px; height: 30px; font-size: 12px;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                      </div>
                      <?php endif; ?>
                      <span class="ms-2"><?php echo htmlspecialchars($user['name']); ?></span>
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
                    <td>N/A</td>
                    <td>
                      <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-info me-1" onclick="window.location.href='editar-usuario.php?id=<?php echo $user['id']; ?>'">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
                        <button type="button" class="btn btn-sm btn-danger delete-user" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                          <i class="bi bi-trash"></i>
                        </button>
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
  
  <!-- Modal de Confirmação de Exclusão -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Tem certeza que deseja excluir o usuário <strong id="userName"></strong>?</p>
          <p class="text-danger">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
            <i class="bi bi-trash"></i> Excluir Usuário
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Overlay de Carregamento com estilo corrigido -->
  <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.7); z-index: 9999; display: flex; justify-content: center; align-items: center;">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Carregando...</span>
    </div>
  </div>
</main><!-- End #main -->

<?php include_once '../includes/footer.php'; ?>

<!-- Garantir que jQuery e DataTables estejam disponíveis -->
<script>
// Variável global para a instância do DataTable
let userDataTable;

// Função para ocultar o overlay de carregamento
function ocultarOverlay() {
  const overlay = document.getElementById('loading-overlay');
  if (overlay) {
    overlay.style.display = 'none';
    console.log('Overlay de carregamento ocultado');
  }
}

// Verificar se jQuery está carregado, se não, carregá-lo
if (typeof jQuery === 'undefined') {
  console.log('jQuery não detectado. Carregando...');
  const jqueryScript = document.createElement('script');
  jqueryScript.src = 'https://code.jquery.com/jquery-3.6.4.min.js';
  jqueryScript.integrity = 'sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=';
  jqueryScript.crossOrigin = 'anonymous';
  jqueryScript.onload = function() {
    console.log('jQuery carregado com sucesso.');
    carregarDataTable();
  };
  document.head.appendChild(jqueryScript);
} else {
  carregarDataTable();
}

// Função para carregar DataTables se necessário
function carregarDataTable() {
  if (typeof $.fn.DataTable === 'undefined') {
    console.log('DataTable não detectada. Carregando...');
    
    // Carregar CSS do DataTables
    const dtCSS = document.createElement('link');
    dtCSS.rel = 'stylesheet';
    dtCSS.href = 'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css';
    document.head.appendChild(dtCSS);
    
    // Carregar JavaScript do DataTables
    const dtScript = document.createElement('script');
    dtScript.src = 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js';
    dtScript.onload = function() {
      console.log('DataTable core carregada.');
      
      // Carregar integração com Bootstrap
      const dtBootstrap = document.createElement('script');
      dtBootstrap.src = 'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js';
      dtBootstrap.onload = function() {
        console.log('DataTable Bootstrap carregada.');
        
        // Carregar pacote de tradução PT-BR
        const dtLang = document.createElement('script');
        dtLang.src = 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json';
        dtLang.onload = function() {
          console.log('Tradução PT-BR carregada.');
          inicializarTabela(true);
        };
        document.head.appendChild(dtLang);
      };
      document.head.appendChild(dtBootstrap);
    };
    document.head.appendChild(dtScript);
  } else {
    inicializarTabela(true);
  }
}
</script>

<script>
// Função para inicializar a tabela (com ou sem DataTable)
function inicializarTabela(comBiblioteca = false) {
  // Remover overlay de carregamento imediatamente
  ocultarOverlay();
  
  try {
    // Verificar se a tabela contém dados
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    if (tableRows.length === 0) {
      // Se não houver dados, exibir mensagem na tabela
      document.querySelector('#usersTable tbody').innerHTML = 
        '<tr><td colspan="8" class="text-center py-4">Nenhum usuário encontrado</td></tr>';
    }
    
    // Se não temos a biblioteca ou ela falha, implementamos a funcionalidade manualmente
    if (!comBiblioteca || typeof $.fn.DataTable === 'undefined') {
      console.log('Usando implementação nativa da tabela');
      
      // Implementação básica de busca
      const userSearchBox = document.getElementById('userSearchBox');
      if (userSearchBox) {
        userSearchBox.addEventListener('keyup', function() {
          const searchText = this.value.trim();
          userDataTable.search(searchText).draw();
          console.log('Busca aplicada:', searchText);
        });
      }
      
      // Implementação básica de paginação
      const rowsPerPage = 10;
      const rows = Array.from(document.querySelectorAll('#usersTable tbody tr:not(.no-results)'));
      const totalPages = Math.ceil(rows.length / rowsPerPage);
      
      // Adicionar controles de paginação se houver mais de uma página
      if (totalPages > 1) {
        // Criar elemento de paginação
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'dataTables_paginate paging_simple_numbers mt-3';
        paginationContainer.innerHTML = `
          <ul class="pagination">
            <li class="paginate_button page-item previous disabled">
              <a href="#" class="page-link">Anterior</a>
            </li>
            ${Array.from({ length: totalPages }, (_, i) => 
              `<li class="paginate_button page-item ${i === 0 ? 'active' : ''}">
                <a href="#" class="page-link" data-page="${i + 1}">${i + 1}</a>
               </li>`
            ).join('')}
            <li class="paginate_button page-item next">
              <a href="#" class="page-link">Próximo</a>
            </li>
          </ul>
        `;
        
        // Adicionar após a tabela
        document.querySelector('#usersTable').parentNode.appendChild(paginationContainer);
        
        // Função para mostrar página específica
        function showPage(pageNum) {
          const start = (pageNum - 1) * rowsPerPage;
          const end = start + rowsPerPage;
          
          rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? 'table-row' : 'none';
          });
          
          // Atualizar estado dos botões de paginação
          document.querySelectorAll('.paginate_button').forEach(button => {
            button.classList.remove('active');
          });
          
          document.querySelector(`.paginate_button:nth-child(${pageNum + 1})`).classList.add('active');
          
          // Atualizar botões anterior/próximo
          document.querySelector('.paginate_button.previous').classList.toggle('disabled', pageNum === 1);
          document.querySelector('.paginate_button.next').classList.toggle('disabled', pageNum === totalPages);
        }
        
        // Mostrar primeira página inicialmente
        showPage(1);
        
        // Adicionar event listeners para paginação
        document.querySelectorAll('.paginate_button:not(.previous):not(.next)').forEach(button => {
          button.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.querySelector('a').getAttribute('data-page'));
            showPage(page);
          });
        });
        
        // Botões anterior/próximo
        document.querySelector('.paginate_button.previous').addEventListener('click', function(e) {
          e.preventDefault();
          const activePage = parseInt(document.querySelector('.paginate_button.active a').getAttribute('data-page'));
          if (activePage > 1) {
            showPage(activePage - 1);
          }
        });
        
        document.querySelector('.paginate_button.next').addEventListener('click', function(e) {
          e.preventDefault();
          const activePage = parseInt(document.querySelector('.paginate_button.active a').getAttribute('data-page'));
          if (activePage < totalPages) {
            showPage(activePage + 1);
          }
        });
      }
      
      // Implementar filtros
      document.querySelectorAll('.filter-option').forEach(option => {
        option.addEventListener('click', function(e) {
          e.preventDefault();
          const filterValue = this.getAttribute('data-filter');
          
          // Limpar mensagem de "sem resultados"
          const mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
          if (mensagemRow) mensagemRow.style.display = 'none';
          
          // Aplicar filtro
          const rows = document.querySelectorAll('#usersTable tbody tr:not(.no-results)');
          let encontrados = 0;
          
          rows.forEach(row => {
            if (row.querySelector('td')) { // Verificar se é uma linha de dados
              let mostrar = false;
              
              if (filterValue === 'all') {
                mostrar = true;
              } else if (filterValue === 'admin' || filterValue === 'user') {
                // Usar os atributos data para filtrar
                const userType = row.getAttribute('data-user-type');
                mostrar = (filterValue === userType); 
              } else if (filterValue === 'active' || filterValue === 'inactive') {
                // Usar os atributos data para filtrar
                const userStatus = row.getAttribute('data-user-status');
                mostrar = (filterValue === userStatus);
              }
              
              row.style.display = mostrar ? 'table-row' : 'none';
              if (mostrar) encontrados++;
            }
          });
          
          // Atualizar texto do botão de filtro
          document.getElementById('filterDropdown').innerHTML = '<i class="bi bi-funnel"></i> ' + this.textContent;
          
          // Se nenhum resultado for encontrado, mostrar mensagem
          if (encontrados === 0) {
            let mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
            if (!mensagemRow) {
              mensagemRow = document.createElement('tr');
              mensagemRow.className = 'no-results';
              mensagemRow.innerHTML = '<td colspan="8" class="text-center py-4 text-muted">Nenhum usuário encontrado com o filtro selecionado</td>';
              document.querySelector('#usersTable tbody').appendChild(mensagemRow);
            } else {
              mensagemRow.querySelector('td').textContent = 'Nenhum usuário encontrado com o filtro selecionado';
              mensagemRow.style.display = 'table-row';
            }
          }
          
          // Visualização para debug
          console.log('Filtro aplicado (nativo):', filterValue, 'Resultados:', encontrados);
        });
      });
    } else {
      // Usar DataTable normalmente se disponível
      try {
        console.log('Inicializando DataTable...');
        userDataTable = $('#usersTable').DataTable({
          language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json',
            emptyTable: "Nenhum usuário encontrado",
            zeroRecords: "Nenhum usuário encontrado com os critérios de busca",
            info: "Mostrando _START_ até _END_ de _TOTAL_ usuários",
            infoEmpty: "Mostrando 0 até 0 de 0 usuários",
            search: "",
            lengthMenu: "Mostrar _MENU_ usuários por página",
            paginate: {
              first: "Primeiro",
              last: "Último",
              next: "Próximo",
              previous: "Anterior"
            }
          },
          responsive: true,
          pageLength: 10,
          lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'Todos']],
          dom: 'rtip',
          searching: true,
          lengthChange: false,
          columnDefs: [
            { orderable: false, targets: [7] } // Desabilitar ordenação na coluna de ações
          ],
          initComplete: function() {
            // Estilizar os controles de paginação
            const paginationContainer = document.querySelector('.dataTables_paginate');
            if (paginationContainer) {
              paginationContainer.classList.add('mt-3', 'pagination-container');
              
              // Adicionar estilo aos botões de paginação
              const paginationButtons = paginationContainer.querySelectorAll('.paginate_button:not(.previous):not(.next)');
              paginationButtons.forEach(button => {
                button.classList.add('rounded-circle');
                const link = button.querySelector('a');
                if (link) {
                  link.classList.add('d-flex', 'align-items-center', 'justify-content-center');
                  link.style.width = '36px';
                  link.style.height = '36px';
                }
              });
              
              // Adicionar ícones aos botões anterior/próximo
              const prevButton = paginationContainer.querySelector('.previous a');
              const nextButton = paginationContainer.querySelector('.next a');
              
              if (prevButton) prevButton.innerHTML = '<i class="bi bi-chevron-left"></i>';
              if (nextButton) nextButton.innerHTML = '<i class="bi bi-chevron-right"></i>';
            }
            
            // Adicionar atributos de acessibilidade
            document.querySelectorAll('.paginate_button a').forEach(link => {
              link.setAttribute('role', 'button');
              link.setAttribute('aria-label', link.textContent.trim());
            });
            
            // Remover TODOS os campos de busca do DataTable
            const dtFilters = document.querySelectorAll('.dataTables_filter');
            dtFilters.forEach(filter => {
              if (filter && filter.parentNode) {
                filter.parentNode.removeChild(filter);
              }
            });
            
            // Configurar o campo de busca personalizado
            const userSearchBox = document.getElementById('userSearchBox');
            if (userSearchBox) {
              userSearchBox.addEventListener('keyup', function() {
                try {
                  const searchText = this.value.trim();
                  
                  // Limpar filtros ativos ao iniciar uma busca
                  if (searchText !== '') {
                    localStorage.removeItem('selectedFilter');
                    document.getElementById('filterDropdown').innerHTML = '<i class="bi bi-funnel"></i> Filtrar';
                  }
                  
                  // Tentar usar DataTable API para busca
                  if (typeof userDataTable !== 'undefined' && userDataTable) {
                    userDataTable.search(searchText).draw();
                    console.log('Busca aplicada via DataTable API:', searchText);
                  } else {
                    // Busca manual via DOM
                    const lowercaseSearch = searchText.toLowerCase();
                    const rows = document.querySelectorAll('#usersTable tbody tr:not(.no-results)');
                    
                    let encontrados = 0;
                    rows.forEach(row => {
                      if (row.querySelector('td')) {
                        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                        const username = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                        const email = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                        
                        const visible = name.includes(lowercaseSearch) || 
                                       username.includes(lowercaseSearch) || 
                                       email.includes(lowercaseSearch);
                        
                        row.style.display = visible ? 'table-row' : 'none';
                        if (visible) encontrados++;
                      }
                    });
                    
                    // Se nenhum resultado for encontrado, mostrar mensagem
                    if (encontrados === 0 && searchText !== '') {
                      let mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
                      if (!mensagemRow) {
                        mensagemRow = document.createElement('tr');
                        mensagemRow.className = 'no-results';
                        mensagemRow.innerHTML = '<td colspan="8" class="text-center py-4 text-muted">Nenhum usuário encontrado com o termo de busca</td>';
                        document.querySelector('#usersTable tbody').appendChild(mensagemRow);
                      } else {
                        mensagemRow.querySelector('td').textContent = 'Nenhum usuário encontrado com o termo de busca';
                        mensagemRow.style.display = 'table-row';
                      }
                    } else {
                      // Esconder mensagem de "sem resultados" se houver resultados
                      const mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
                      if (mensagemRow) {
                        mensagemRow.style.display = 'none';
                      }
                    }
                    
                    console.log('Busca aplicada via DOM:', searchText, 'Resultados:', encontrados);
                  }
                } catch (error) {
                  console.error('Erro na busca:', error);
                }
              });
            }
          }
        });
        
        // Estilos para o campo de busca nativo (caso esteja visível)
        const searchInput = document.querySelector('.dataTables_filter input');
        if (searchInput) {
          searchInput.classList.add('form-control', 'form-control-sm');
          searchInput.placeholder = "Buscar usuário...";
          
          const searchContainer = searchInput.closest('.dataTables_filter');
          if (searchContainer) {
            searchContainer.classList.add('input-group-sm');
            // Substituir o label por um design com ícone
            const label = searchContainer.querySelector('label');
            if (label) {
              const searchText = label.textContent.replace(':', '').trim();
              label.innerHTML = '<div class="input-group">' +
                                '<span class="input-group-text"><i class="bi bi-search"></i></span>' +
                                label.innerHTML.replace(searchText+':', '') +
                                '</div>';
            }
          }
        }
        
        // Filtro por tipo e status
        document.querySelectorAll('.filter-option').forEach(option => {
          option.addEventListener('click', function(e) {
            e.preventDefault();
            const filterValue = this.getAttribute('data-filter');
            
            if (filterValue === 'all') {
              userDataTable.search('').draw();
              userDataTable.columns().search('').draw();
            } else if (filterValue === 'admin' || filterValue === 'user') {
              // Limpar outras buscas primeiro
              userDataTable.search('').columns().search('').draw();
              // Aplicar filtro na coluna de tipo (coluna 4)
              const searchTerm = filterValue === 'admin' ? 'Administrador' : 'Usuário';
              userDataTable.column(4).search(searchTerm, true, false).draw();
            } else if (filterValue === 'active' || filterValue === 'inactive') {
              // Limpar outras buscas primeiro
              userDataTable.search('').columns().search('').draw();
              // Aplicar filtro na coluna de status (coluna 5)
              const searchTerm = filterValue === 'active' ? 'Ativo' : 'Inativo';
              userDataTable.column(5).search(searchTerm, true, false).draw();
            }
            
            // Atualizar texto do botão de filtro
            document.getElementById('filterDropdown').innerHTML = '<i class="bi bi-funnel"></i> ' + this.textContent;
            
            // Visualização para debug
            console.log('Filtro aplicado:', filterValue);
          });
        });
        
        console.log('DataTable inicializada com sucesso');
      } catch (dtError) {
        console.error('Erro ao inicializar DataTable:', dtError);
        // Se falhar, recorrer à implementação básica
        inicializarTabela(false);
      }
    }
  } catch (error) {
    console.error('Erro geral ao inicializar a tabela:', error);
    
    // Exibir mensagem de erro para o administrador
    const table = document.querySelector('#usersTable');
    if (table) {
      table.insertAdjacentHTML('beforebegin', 
        '<div class="alert alert-warning alert-dismissible fade show" role="alert">' +
        '<strong>Atenção!</strong> Houve um problema ao carregar a tabela. Erro: ' + error.message + 
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
    }
  }
  
  // Adicionar efeito hover nas linhas da tabela (independente do DataTable)
  document.querySelectorAll('#usersTable tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
      this.style.transition = 'background-color 0.2s ease';
      this.style.backgroundColor = 'rgba(0, 123, 255, 0.05)';
    });
    
    row.addEventListener('mouseleave', function() {
      this.style.backgroundColor = '';
    });
  });
}

document.addEventListener('DOMContentLoaded', function() {
  // Garantir que o overlay seja ocultado
  ocultarOverlay();
  
  // NOVA FUNÇÃO: para realizar busca de forma confiável
  window.aplicarBusca = function(searchText) {
    searchText = searchText.trim().toLowerCase();
    console.log('Aplicando busca com termo:', searchText);
    
    // Limpar filtros ao buscar
    if (searchText !== '') {
      localStorage.removeItem('selectedFilter');
      document.getElementById('filterDropdown').innerHTML = '<i class="bi bi-funnel"></i> Filtrar';
    }
    
    // Implementação direta via DOM - sempre funciona
    const rows = document.querySelectorAll('#usersTable tbody tr:not(.no-results)');
    let encontrados = 0;
    
    rows.forEach(row => {
      if (row.querySelector('td')) {
        // Buscar em todos os campos relevantes
        const id = row.querySelector('th').textContent.toLowerCase();
        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const username = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        const email = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
        
        // Verificar se o termo de busca existe em algum dos campos
        const visible = searchText === '' || 
                        id.includes(searchText) || 
                        name.includes(searchText) || 
                        username.includes(searchText) || 
                        email.includes(searchText);
        
        // Mostrar ou esconder linha
        row.style.display = visible ? 'table-row' : 'none';
        
        if (visible) encontrados++;
      }
    });
    
    // Mensagem de "sem resultados"
    if (encontrados === 0 && searchText !== '') {
      let mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
      if (!mensagemRow) {
        mensagemRow = document.createElement('tr');
        mensagemRow.className = 'no-results';
        mensagemRow.innerHTML = '<td colspan="8" class="text-center py-4 text-muted">Nenhum usuário encontrado com o termo de busca</td>';
        document.querySelector('#usersTable tbody').appendChild(mensagemRow);
      } else {
        mensagemRow.querySelector('td').textContent = 'Nenhum usuário encontrado com o termo de busca';
        mensagemRow.style.display = 'table-row';
      }
    } else {
      // Esconder mensagem se houver resultados
      const mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
      if (mensagemRow) {
        mensagemRow.style.display = 'none';
      }
    }
    
    console.log(`Busca concluída com "${searchText}". Resultados encontrados: ${encontrados}`);
  };
  
  // Configurar o campo de busca corretamente
  const userSearchBox = document.getElementById('userSearchBox');
  if (userSearchBox) {
    console.log('Campo de busca encontrado, configurando event listener...');
    
    // Limpar qualquer conteúdo inicial
    userSearchBox.value = '';
    
    // Usar input event em vez de keyup para capturar todos os tipos de entrada
    userSearchBox.addEventListener('input', function() {
      const searchText = this.value.trim();
      console.log('Evento de busca disparado com texto:', searchText);
      
      // Usar nossa função de busca confiável
      window.aplicarBusca(searchText);
    });
    
    // Adicionar um second listener para garantir (usando setTimeout para evitar conflitos)
    setTimeout(function() {
      userSearchBox.addEventListener('keyup', function() {
        const searchText = this.value.trim();
        console.log('Evento keyup em campo de busca:', searchText);
        window.aplicarBusca(searchText);
      });
    }, 500);
  } else {
    console.error('Campo de busca não encontrado!');
  }
  
  // Função unificada para aplicar filtros - implementação simplificada e reforçada
  window.aplicarFiltro = function(filterValue) {
    console.log('Aplicando filtro com valor:', filterValue);
    
    // Buscar texto do filtro para o botão dropdown
    const filterTexts = {
      'all': 'Todos',
      'admin': 'Administradores',
      'user': 'Usuários',
      'active': 'Ativos',
      'inactive': 'Inativos'
    };
    
    // Atualizar texto do botão de filtro
    document.getElementById('filterDropdown').innerHTML = 
      '<i class="bi bi-funnel"></i> ' + (filterTexts[filterValue] || 'Filtrar');
    
    // Limpar campo de busca
    const searchBox = document.getElementById('userSearchBox');
    if (searchBox) {
      searchBox.value = '';
    }
    
    // Salvar o filtro em localStorage para persistência
    localStorage.setItem('selectedFilter', filterValue);
    
    // IMPLEMENTAÇÃO DIRETA: Sempre filtrar via DOM para garantir funcionamento
    console.log('Aplicando filtro diretamente no DOM para maior confiabilidade');
    
    // Selecionar todas as linhas da tabela
    const rows = document.querySelectorAll('#usersTable tbody tr:not(.no-results)');
    let encontrados = 0;
    
    rows.forEach(row => {
      if (row.querySelector('td')) { // Verificar se é uma linha de dados
        let mostrar = false;
        
        if (filterValue === 'all') {
          mostrar = true;
        } else if (filterValue === 'admin' || filterValue === 'user') {
          const userType = row.getAttribute('data-user-type');
          console.log('Linha:', row, 'Tipo:', userType, 'Filtro:', filterValue);
          mostrar = (filterValue === userType);
        } else if (filterValue === 'active' || filterValue === 'inactive') {
          const userStatus = row.getAttribute('data-user-status');
          console.log('Linha:', row, 'Status:', userStatus, 'Filtro:', filterValue);
          mostrar = (filterValue === userStatus);
        }
        
        // Alterar a visibilidade da linha
        row.style.display = mostrar ? 'table-row' : 'none';
        if (mostrar) encontrados++;
      }
    });
    
    // Feedback para usuário sobre resultados
    if (encontrados === 0) {
      let mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
      if (!mensagemRow) {
        mensagemRow = document.createElement('tr');
        mensagemRow.className = 'no-results';
        mensagemRow.innerHTML = '<td colspan="8" class="text-center py-4 text-muted">Nenhum usuário encontrado com o filtro selecionado</td>';
        document.querySelector('#usersTable tbody').appendChild(mensagemRow);
      } else {
        mensagemRow.querySelector('td').textContent = 'Nenhum usuário encontrado com o filtro selecionado';
        mensagemRow.style.display = 'table-row';
      }
    } else {
      // Esconder mensagem de "sem resultados" se houver resultados
      const mensagemRow = document.querySelector('#usersTable tbody tr.no-results');
      if (mensagemRow) {
        mensagemRow.style.display = 'none';
      }
    }
    
    console.log(`Filtro aplicado: ${filterValue}. Resultados: ${encontrados}`);
    return false; // Prevenir navegação
  };
  
  // Configurar eventos de clique para os filtros - implementação reforçada
  document.querySelectorAll('.filter-option').forEach(option => {
    option.addEventListener('click', function(e) {
      e.preventDefault();
      console.log('Filtro clicado:', this);
      const filterValue = this.getAttribute('data-filter');
      window.aplicarFiltro(filterValue);
    });
  });
  
  // Aplicar filtro salvo anteriormente (se existir)
  setTimeout(function() {
    const savedFilter = localStorage.getItem('selectedFilter');
    if (savedFilter) {
      console.log('Aplicando filtro salvo do localStorage:', savedFilter);
      window.aplicarFiltro(savedFilter);
    }
  }, 1000);
  
  // Evitar conflitos com CSS ou JavaScript externo
  setTimeout(function() {
    // Verificar se o filtro está funcionando corretamente
    document.querySelectorAll('.filter-option').forEach(option => {
      // Reforçar event listener para garantir que seja aplicado
      option.addEventListener('click', function(e) {
        e.preventDefault();
        const filterValue = this.getAttribute('data-filter');
        console.log('Clique em filtro (verificação secundária):', filterValue);
        
        // Implementação direta alternativa, caso a função principal falhe
        if (!window.aplicarFiltro) {
          console.log('Função aplicarFiltro não encontrada. Aplicando filtro diretamente.');
          
          // Atualizar texto do botão
          const filterTexts = {
            'all': 'Todos',
            'admin': 'Administradores',
            'user': 'Usuários',
            'active': 'Ativos',
            'inactive': 'Inativos'
          };
          
          document.getElementById('filterDropdown').innerHTML = 
            '<i class="bi bi-funnel"></i> ' + (filterTexts[filterValue] || 'Filtrar');
            
          // Aplicar filtro diretamente às linhas
          const rows = document.querySelectorAll('#usersTable tbody tr');
          rows.forEach(row => {
            if (row.querySelector('td')) {
              if (filterValue === 'all') {
                row.style.display = '';
              } else if (filterValue === 'admin' || filterValue === 'user') {
                const userType = row.getAttribute('data-user-type');
                row.style.display = (userType === filterValue) ? '' : 'none';
              } else if (filterValue === 'active' || filterValue === 'inactive') {
                const userStatus = row.getAttribute('data-user-status');
                row.style.display = (userStatus === filterValue) ? '' : 'none';
              }
            }
          });
        } else {
          window.aplicarFiltro(filterValue);
        }
      });
    });
  }, 1500);

  // Inserir CSS para corrigir possíveis problemas visuais com os filtros
  document.head.insertAdjacentHTML('beforeend', `
    <style>
      /* Garantir que o filtro seja clicável e visível */
      .dropdown-menu.dropdown-menu-end {
        z-index: 1050 !important;
      }
      
      .filter-option {
        cursor: pointer !important;
        user-select: none !important;
        background-color: transparent !important;
        transition: background-color 0.3s ease !important;
      }
      
      .filter-option:hover {
        background-color: rgba(13, 110, 253, 0.1) !important;
      }
      
      .dropdown-item:active, .dropdown-item:focus {
        background-color: rgba(13, 110, 253, 0.2) !important;
      }
      
      /* Se estiver usando Bootstrap 5, garanta que os dropdowns funcionem */
      [data-bs-toggle="dropdown"] {
        cursor: pointer !important;
      }
    </style>
  `);

  // Corrigir os atributos data-* em todas as linhas da tabela se necessário
  setTimeout(function() {
    const rows = document.querySelectorAll('#usersTable tbody tr');
    rows.forEach(row => {
      if (!row.classList.contains('no-results') && row.querySelector('td')) {
        // Verificar tipo (admin/user)
        if (!row.hasAttribute('data-user-type')) {
          const adminBadge = row.querySelector('td:nth-child(5) .badge.bg-primary');
          if (adminBadge) {
            row.setAttribute('data-user-type', 'admin');
          } else {
            row.setAttribute('data-user-type', 'user');
          }
        }
        
        // Verificar status (active/inactive)
        if (!row.hasAttribute('data-user-status')) {
          const activeBadge = row.querySelector('td:nth-child(6) .badge.bg-success');
          const inactiveBadge = row.querySelector('td:nth-child(6) .badge.bg-warning');
          
          if (activeBadge) {
            row.setAttribute('data-user-status', 'active');
          } else if (inactiveBadge) {
            row.setAttribute('data-user-status', 'inactive');
          } else {
            row.setAttribute('data-user-status', 'active'); // Valor padrão
          }
        }
      }
    });
    
    console.log('Atributos data-* verificados e corrigidos se necessário');
  }, 2000);

  // Verificações de segurança para garantir que tudo funcione
  setTimeout(function() {
    // Verificar se o campo de busca está funcionando
    const userSearchBox = document.getElementById('userSearchBox');
    if (userSearchBox && !userSearchBox._hasSearchListener) {
      console.log('Adicionando listener de busca de emergência');
      userSearchBox._hasSearchListener = true;
      
      userSearchBox.addEventListener('input', function() {
        const searchText = this.value.trim().toLowerCase();
        
        // Implementação direta em caso de falha da função principal
        if (!window.aplicarBusca) {
          console.log('Função aplicarBusca não encontrada, usando implementação direta');
          
          const rows = document.querySelectorAll('#usersTable tbody tr:not(.no-results)');
          rows.forEach(row => {
            if (row.querySelector('td')) {
              const content = row.textContent.toLowerCase();
              row.style.display = searchText === '' || content.includes(searchText) ? '' : 'none';
            }
          });
        } else {
          window.aplicarBusca(searchText);
        }
      });
    }
  }, 1000);

});
</script>

<!-- Script para garantir rápida remoção do overlay -->
<script>
// Remover overlay de loading imediatamente
(function() {
  function removerOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'none';
  }
  
  // Executar imediatamente
  removerOverlay();
  
  // Executar após carregamento do documento
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', removerOverlay);
  } else {
    removerOverlay();
  }
  
  // Executar após carregamento completo
  window.addEventListener('load', removerOverlay);
  
  // Executar após um curto período
  setTimeout(removerOverlay, 800);
})();
</script>
