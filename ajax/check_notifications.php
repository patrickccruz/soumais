<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$user = $_SESSION['user'];

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$lastCheck = isset($data['lastCheck']) ? new DateTime($data['lastCheck']) : new DateTime('-30 seconds');

// Buscar novas notificações
$sql = "SELECT * FROM notificacoes WHERE user_id = ? AND data_criacao > ? ORDER BY data_criacao DESC";
$stmt = $conn->prepare($sql);
$lastCheckStr = $lastCheck->format('Y-m-d H:i:s');
$stmt->bind_param("is", $user['id'], $lastCheckStr);
$stmt->execute();
$result = $stmt->get_result();

$newNotifications = [];
while ($row = $result->fetch_assoc()) {
    $newNotifications[] = [
        'id' => $row['id'],
        'tipo' => $row['tipo'],
        'titulo' => $row['titulo'],
        'mensagem' => $row['mensagem'],
        'link' => $row['link'],
        'data_criacao' => $row['data_criacao']
    ];
}

// Contar total de notificações não lidas
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE user_id = ? AND lida = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$totalResult = $stmt->get_result();
$totalUnread = $totalResult->fetch_assoc()['total'];

echo json_encode([
    'success' => true,
    'newNotifications' => $newNotifications,
    'totalUnread' => $totalUnread
]); 