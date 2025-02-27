<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$user = $_SESSION['user'];

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da notificação inválido']);
    exit;
}

$notifId = (int)$data['id'];

// Conexão com o banco de dados
// $conn = new mysqli('localhost', 'root', '', 'sou_digital');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco']);
    exit;
}

// Marcar notificação como lida
$sql = "UPDATE notificacoes SET lida = TRUE WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $notifId, $user['id']);
$success = $stmt->execute();

// Contar total de notificações não lidas
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE user_id = ? AND lida = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$totalUnread = $result->fetch_assoc()['total'];

echo json_encode([
    'success' => $success,
    'totalUnread' => $totalUnread
]); 