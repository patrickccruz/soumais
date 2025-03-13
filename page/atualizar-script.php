<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: autenticacao.php");
    exit;
}

require_once '../includes/upload_functions.php';

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'sou_digital');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_id = $_POST['report_id'];
    $data_chamado = $_POST['data_chamado'];
    $numero_chamado = $_POST['numero_chamado'];
    $status_chamado = $_POST['status_chamado'];
    $cliente = $_POST['cliente'];
    $nome_informante = $_POST['nome_informante'];
    $endereco_partida = $_POST['endereco_partida'];
    $endereco_chegada = $_POST['endereco_chegada'];
    $informacoes_adicionais = $_POST['informacoes_adicionais'];
    $km_inicial = $_POST['km_inicial'];
    $km_final = $_POST['km_final'];
    $hora_chegada = $_POST['hora_chegada'];
    $hora_saida = $_POST['hora_saida'];
    $tipo_chamado = $_POST['tipo_chamado'];

    // Processar arrays de patrimônios
    $quantidades = $_POST['quantidade_patrimonio'];
    $tipos = $_POST['tipo_patrimonio'];
    
    // Combinar as quantidades e tipos em uma string JSON
    $patrimonios = array();
    for ($i = 0; $i < count($quantidades); $i++) {
        $patrimonios[] = array(
            'quantidade' => $quantidades[$i],
            'tipo' => $tipos[$i]
        );
    }
    $tipo_patrimonio = json_encode($patrimonios);

    // Processar upload do arquivo
    $arquivo_path = null;
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($_FILES['arquivo']['tmp_name']);
        
        if (!is_allowed_file_type($mime_type, $allowed_types)) {
            $_SESSION['error_message'] = "Tipo de arquivo não permitido. Use apenas PDF.";
            header("Location: meus-scripts.php");
            exit;
        }

        // Gerar nome único e mover arquivo
        $new_filename = generate_unique_filename($_FILES['arquivo']['name'], 'rat_');
        $upload_path = get_upload_path('reports', ['report_id' => $report_id]);
        $full_path = $upload_path . '/' . $new_filename;

        if (move_uploaded_file_safe($_FILES['arquivo']['tmp_name'], $full_path)) {
            $arquivo_path = 'uploads/reports/' . $report_id . '/' . $new_filename;
        } else {
            $_SESSION['error_message'] = "Erro ao fazer upload do arquivo";
            header("Location: meus-scripts.php");
            exit;
        }
    }

    // Preparar a query de atualização
    $query = "UPDATE reports SET 
        data_chamado = ?,
        numero_chamado = ?,
        status_chamado = ?,
        cliente = ?,
        nome_informante = ?,
        endereco_partida = ?,
        endereco_chegada = ?,
        informacoes_adicionais = ?,
        km_inicial = ?,
        km_final = ?,
        hora_chegada = ?,
        hora_saida = ?,
        tipo_chamado = ?,
        tipo_patrimonio = ?";

    $params = [
        $data_chamado,
        $numero_chamado,
        $status_chamado,
        $cliente,
        $nome_informante,
        $endereco_partida,
        $endereco_chegada,
        $informacoes_adicionais,
        $km_inicial,
        $km_final,
        $hora_chegada,
        $hora_saida,
        $tipo_chamado,
        $tipo_patrimonio
    ];
    $types = "ssssssssiiisss";

    // Adicionar arquivo_path à query se um novo arquivo foi enviado
    if ($arquivo_path) {
        $query .= ", arquivo_path = ?";
        $params[] = $arquivo_path;
        $types .= "s";
    }

    $query .= " WHERE id = ? AND user_id = ?";
    $params[] = $report_id;
    $params[] = $_SESSION['user']['id'];
    $types .= "ii";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Script atualizado com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao atualizar o script: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
header("Location: meus-scripts.php");
exit;
?> 