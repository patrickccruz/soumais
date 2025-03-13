<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    die("Acesso não autorizado");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_SESSION['user'];
    
    $dataChamado = $_POST['dataChamado'];
    $numeroChamado = $_POST['numeroChamado'];
    $cliente = $_POST['cliente'];
    $nomeInformante = $_POST['nomeInformante'];
    $quantidadePatrimonios = $_POST['quantidadePatrimonios'];
    $kmInicial = $_POST['kmInicial'];
    $kmFinal = $_POST['kmFinal'];
    $horaChegada = $_POST['horaChegada'];
    $horaSaida = $_POST['horaSaida'];
    $enderecoPartida = $_POST['enderecoPartida'];
    $enderecoChegada = $_POST['enderecoChegada'];
    $informacoesAdicionais = $_POST['informacoesAdicionais'];
    $arquivoPath = '';

    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $arquivoPath = $uploadDir . basename($_FILES['arquivo']['name']);
        move_uploaded_file($_FILES['arquivo']['tmp_name'], $arquivoPath);
    }

    $conn = new mysqli('localhost', 'root', '', 'sou_digital');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO reports (user_id, data_chamado, numero_chamado, cliente, nome_informante, quantidade_patrimonios, km_inicial, km_final, hora_chegada, hora_saida, endereco_partida, endereco_chegada, informacoes_adicionais, arquivo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiiissssss", $user['id'], $dataChamado, $numeroChamado, $cliente, $nomeInformante, $quantidadePatrimonios, $kmInicial, $kmFinal, $horaChegada, $horaSaida, $enderecoPartida, $enderecoChegada, $informacoesAdicionais, $arquivoPath);

    if ($stmt->execute()) {
        echo "Dados salvos com sucesso!";
    } else {
        echo "Erro ao salvar os dados: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?> 