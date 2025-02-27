<?php
    session_start();
    ob_start(); // Inicia o buffer de saída
    require_once '../includes/upload_functions.php';

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
        header("Location: autenticacao.php");
        exit;
    }

    // Se for uma requisição AJAX/POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            $conn = new mysqli('localhost', 'root', '', 'sou_digital');
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            $user = $_SESSION['user'];
            $dataChamado = $_POST['dataChamado'];
            $numeroChamado = !empty($_POST['numeroChamado']) ? intval($_POST['numeroChamado']) : null;
            $informacoesAdicionais = $_POST['informacoesAdicionais'];
            $valor = str_replace(',', '.', $_POST['valor']);
            $tipo_reembolso = $_POST['tipo_reembolso'];
            $status = 'pendente';

            // Inserir reembolso
            $stmt = $conn->prepare("INSERT INTO reembolsos (user_id, data_chamado, numero_chamado, informacoes_adicionais, valor, tipo_reembolso, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isissss", $user['id'], $dataChamado, $numeroChamado, $informacoesAdicionais, $valor, $tipo_reembolso, $status);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao salvar reembolso: " . $stmt->error);
            }

            $reembolso_id = $conn->insert_id;
            $arquivoPaths = array();

            // Processamento de arquivos
            if (isset($_FILES['arquivos'])) {
                $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
                $upload_path = get_upload_path('reimbursement', ['reimbursement_id' => $reembolso_id]);
                
                $fileCount = count($_FILES['arquivos']['name']);
                
                for($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['arquivos']['error'][$i] == UPLOAD_ERR_OK) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime_type = $finfo->file($_FILES['arquivos']['tmp_name'][$i]);
                        
                        if (!is_allowed_file_type($mime_type, $allowed_types)) {
                            continue;
                        }

                        $new_filename = generate_unique_filename($_FILES['arquivos']['name'][$i], 'reembolso_');
                        $full_path = $upload_path . '/' . $new_filename;
                        
                        if (move_uploaded_file_safe($_FILES['arquivos']['tmp_name'][$i], $full_path)) {
                            $arquivoPaths[] = str_replace('../', '', $full_path);
                        }
                    }
                }

                if (!empty($arquivoPaths)) {
                    $arquivo_path = implode(',', $arquivoPaths);
                    $stmt = $conn->prepare("UPDATE reembolsos SET arquivo_path = ? WHERE id = ?");
                    $stmt->bind_param("si", $arquivo_path, $reembolso_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao atualizar caminhos dos arquivos: " . $stmt->error);
                    }
                }
            }

            // Notificar administradores
            $admin_query = $conn->prepare("SELECT id FROM users WHERE is_admin = 1");
            $admin_query->execute();
            $admin_result = $admin_query->get_result();
            
            while ($admin = $admin_result->fetch_assoc()) {
                $notif_stmt = $conn->prepare("INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, link) VALUES (?, 'sistema', ?, ?, ?)");
                $notif_titulo = "Nova Solicitação de Reembolso";
                $mensagem = "O usuário " . $user['name'] . " solicitou um reembolso no valor de R$ " . number_format($valor, 2, ',', '.');
                $link = "gerenciar-reembolsos.php";
                $notif_stmt->bind_param("isss", $admin['id'], $notif_titulo, $mensagem, $link);
                $notif_stmt->execute();
            }

            $response['success'] = true;
            $response['message'] = "Reembolso solicitado com sucesso!";
            
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }

    // Se não for POST/AJAX, continua com a renderização normal da página
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : ['id' => 0, 'name' => 'Usuário', 'username' => 'username'];
    $is_page = true;
    include_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Solicitar Reembolso - Sou + Digital</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="../assets/img/Icon geral.png" rel="icon">
  <link href="../assets/img/Icon geral.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="../assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    :focus {
      border-color: #1bd81b !important;
      box-shadow: 0 0 5px rgb(7, 228, 25) !important;
      outline: none !important;
    }
    .preview-image {
      max-width: 200px;
      max-height: 200px;
      margin: 10px;
    }
    #preview-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }
  </style>
</head>

<body>


  <?php include_once '../includes/sidebar.php'; ?>

  <main id="main" class="main">
    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <div class="pagetitle">
                <h1>Solicitar Reembolso</h1>
                <nav>
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Inicial</a></li>
                    <li class="breadcrumb-item active">Solicitar Reembolso</li>
                  </ol>
                </nav>
              </div>

              <!-- Novo alerta com observações importantes -->
              <div class="alert alert-info alert-dismissible fade show" role="alert">
                <h4 class="alert-heading"><i class="bi bi-info-circle"></i> Observações Importantes:</h4>
                <ul>
                  <li>Anexe todos os comprovantes fiscais (notas fiscais, recibos, cupons) legíveis</li>
                  <li>O reembolso será analisado em até 5 dias úteis</li>
                  <li>Valores acima de R$ 500,00 precisam de aprovação adicional</li>
                  <li>Mantenha os comprovantes originais por 6 meses</li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>

              <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?php 
                  echo $_SESSION['error'];
                  unset($_SESSION['error']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <form id="reembolsoForm" method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="dataChamado" class="form-label">Data do Gasto</label>
                    <input type="date" class="form-control" id="dataChamado" name="dataChamado" required>
                  </div>
                  <div class="col-md-6">
                    <label for="numeroChamado" class="form-label">Número do Chamado (opcional)</label>
                    <input type="number" 
                           class="form-control" 
                           id="numeroChamado" 
                           name="numeroChamado" 
                           min="1"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <div class="form-text">Digite apenas números. Deixe em branco se não houver chamado associado.</div>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="valor" class="form-label">Valor do Reembolso (R$)</label>
                    <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                  </div>
                  <div class="col-md-6">
                    <label for="tipo_reembolso" class="form-label">Tipo de Reembolso</label>
                    <select class="form-select" id="tipo_reembolso" name="tipo_reembolso" required>
                      <option value="">Selecione o tipo</option>
                      <option value="estacionamento">Estacionamento</option>
                      <option value="pedagio">Pedágio</option>
                      <option value="alimentacao">Alimentação</option>
                      <option value="transporte">Transporte</option>
                      <option value="hospedagem">Hospedagem</option>
                      <option value="outros">Outros</option>
                    </select>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="informacoesAdicionais" class="form-label">Descrição Detalhada</label>
                  <textarea class="form-control" id="informacoesAdicionais" name="informacoesAdicionais" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                  <label for="arquivos" class="form-label">Comprovantes (Notas fiscais, recibos, etc.)</label>
                  <input type="file" class="form-control" id="arquivos" name="arquivos[]" accept=".pdf,image/*" multiple required>
                  <div id="preview-container"></div>
                </div>

                <!-- Nova caixa de confirmação dos termos -->
                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" id="concordoTermos" required>
                  <label class="form-check-label" for="concordoTermos">
                    Declaro que li e concordo com os termos para solicitação de reembolso. Confirmo que todos os dados e documentos enviados são verdadeiros.
                  </label>
                </div>

                <div class="row mt-4">
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                    <button type="reset" class="btn btn-secondary">Limpar Formulário</button>
                  </div>
                </div>
              </form>

              <!-- Modal de Sucesso -->
              <div class="modal fade" id="sucessoModal" tabindex="-1" aria-labelledby="sucessoModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                      <h5 class="modal-title" id="sucessoModalLabel">Sucesso!</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p id="mensagemSucesso"></p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-success" data-bs-dismiss="modal">Fechar</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal de Erro -->
              <div class="modal fade" id="erroModal" tabindex="-1" aria-labelledby="erroModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                      <h5 class="modal-title" id="erroModalLabel">Erro!</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p id="mensagemErro"></p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include_once '../includes/footer.php'; ?>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="../assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/chart.js/chart.umd.js"></script>
  <script src="../assets/vendor/echarts/echarts.min.js"></script>
  <script src="../assets/vendor/quill/quill.min.js"></script>
  <script src="../assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="../assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="../assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="../assets/js/main.js"></script>

  <script>
  // Armazenar o nome e o ID do usuário logado na sessionStorage
  sessionStorage.setItem('nomeUsuario', '<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>');
  sessionStorage.setItem('idUsuario', '<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>');

  // Função para mostrar modal de sucesso
  function mostrarSucesso(mensagem) {
      document.getElementById('mensagemSucesso').textContent = mensagem;
      const modal = new bootstrap.Modal(document.getElementById('sucessoModal'));
      modal.show();
  }

  // Função para mostrar modal de erro
  function mostrarErro(mensagem) {
      document.getElementById('mensagemErro').textContent = mensagem;
      const modal = new bootstrap.Modal(document.getElementById('erroModal'));
      modal.show();
  }

  // Função para limpar o formulário
  function limparFormulario() {
      document.getElementById('reembolsoForm').reset();
      mostrarSucesso('Formulário limpo com sucesso!');
  }

  // Função para enviar dados para o Discord
  async function enviarParaDiscord(dados) {
      const webhookUrl = 'https://discord.com/api/webhooks/1333406850187526184/vOEWFHFRY-I8Vs7A5M3CD71REU6fr60vChk_J7-C8-8eUM4DUnm2kMahjvLfajkpR3Xm';
      
      // Criar o FormData para enviar arquivos
      const formData = new FormData();
      
      // Adicionar a mensagem como um campo JSON
      const mensagem = {
          content: 'Nova solicitação de reembolso',
          embeds: [{
              title: `Reembolso - Chamado #${dados.numeroChamado}`,
              color: 0x00ff00,
              fields: [
                  {
                      name: 'Data do Chamado',
                      value: dados.dataChamado,
                      inline: true
                  },
                  {
                      name: 'Número do Chamado',
                      value: dados.numeroChamado,
                      inline: true
                  },
                  {
                      name: 'Descrição',
                      value: dados.informacoesAdicionais || 'Sem descrição',
                      inline: false
                  }
              ],
              timestamp: new Date().toISOString()
          }]
      };

      // Adicionar os arquivos primeiro
      const fileInput = document.getElementById('arquivos');
      const files = fileInput.files;
      
      // Se não houver arquivos, envia apenas a mensagem
      if (files.length === 0) {
          const response = await fetch(webhookUrl, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json'
              },
              body: JSON.stringify(mensagem)
          });

          if (!response.ok) {
              const errorText = await response.text();
              throw new Error(`Erro ao enviar mensagem para o Discord: ${errorText}`);
          }
          return response;
      }

      // Se houver arquivos, envia com FormData
      formData.append('payload_json', JSON.stringify(mensagem));
      
      // Adiciona cada arquivo com o nome 'files[n]'
      for (let i = 0; i < files.length; i++) {
          formData.append(`files[${i}]`, files[i]);
      }

      try {
          const response = await fetch(webhookUrl, {
              method: 'POST',
              body: formData
          });

          if (!response.ok) {
              const errorText = await response.text();
              throw new Error(`Erro ao enviar mensagem para o Discord: ${errorText}`);
          }

          return response;
      } catch (error) {
          console.error('Erro ao enviar para o Discord:', error);
          throw error;
      }
  }

  // Evento do botão Enviar para Discord
  document.getElementById('reembolsoForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch(window.location.href, {
          method: 'POST',
          headers: {
              'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
      })
      .then(response => response.json())
      .then(data => {
          if(data.success) {
              mostrarSucesso(data.message);
              this.reset();
              setTimeout(() => {
                  window.location.href = 'meus-reembolsos.php';
              }, 2000);
          } else {
              mostrarErro(data.message);
          }
      })
      .catch(error => {
          console.error('Erro:', error);
          mostrarErro("Erro ao salvar os dados. Por favor, tente novamente.");
      });
  });

  document.getElementById('arquivos').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('preview-container');
    previewContainer.innerHTML = '';
    
    Array.from(e.target.files).forEach(file => {
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'preview-image';
          previewContainer.appendChild(img);
        }
        reader.readAsDataURL(file);
      } else {
        const fileInfo = document.createElement('div');
        fileInfo.className = 'alert alert-info';
        fileInfo.textContent = `Arquivo: ${file.name}`;
        previewContainer.appendChild(fileInfo);
      }
    });
  });

  // Formatar campo de valor para mostrar duas casas decimais
  document.getElementById('valor').addEventListener('change', function(e) {
    if (e.target.value) {
      e.target.value = parseFloat(e.target.value).toFixed(2);
    }
  });
  </script>
</body>

</html>