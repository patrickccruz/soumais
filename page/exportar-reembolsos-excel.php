<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("Location: ../page/autenticacao.php");
    exit;
}

// Verificação de permissão de administrador
if (!isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Definição para indicar que estamos em uma página dentro da pasta 'page'
$is_page = true;

// Conexão com o banco de dados
require_once '../includes/db.php';

// Configurar cabeçalhos para download de arquivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reembolsos_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Construir a consulta SQL com filtros
$where = [];
if (!empty($_GET['status'])) {
    $where[] = "r.status = '{$_GET['status']}'";
}
if (!empty($_GET['tipo'])) {  
    $where[] = "r.tipo_reembolso = '{$_GET['tipo']}'";
}
if (!empty($_GET['month'])) {
    $where[] = "MONTH(r.data_chamado) = {$_GET['month']}";
}
if (!empty($_GET['search'])) {
    $search = $_GET['search'];
    $where[] = "(r.informacoes_adicionais LIKE '%{$search}%' OR u.name LIKE '%{$search}%' OR u.email LIKE '%{$search}%' OR r.numero_chamado LIKE '%{$search}%')";
}

$whereSQL = implode(' AND ', $where);
$sql = "SELECT r.*, u.name as user_name, u.email as user_email 
        FROM reembolsos r 
        JOIN users u ON r.user_id = u.id 
        " . ($whereSQL ? "WHERE $whereSQL" : "") . " 
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);

// Iniciar a saída do Excel
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Worksheet ss:Name="Reembolsos">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">ID</Data></Cell>
    <Cell><Data ss:Type="String">Solicitante</Data></Cell>
    <Cell><Data ss:Type="String">Email</Data></Cell>
    <Cell><Data ss:Type="String">Data</Data></Cell>
    <Cell><Data ss:Type="String">Número do Chamado</Data></Cell>
    <Cell><Data ss:Type="String">Tipo</Data></Cell>
    <Cell><Data ss:Type="String">Valor</Data></Cell>
    <Cell><Data ss:Type="String">Status</Data></Cell>
    <Cell><Data ss:Type="String">Informações</Data></Cell>
    <Cell><Data ss:Type="String">Comentário Admin</Data></Cell>
    <Cell><Data ss:Type="String">Data de Criação</Data></Cell>
    <Cell><Data ss:Type="String">Última Atualização</Data></Cell>
   </Row>
   <?php
   if ($result && $result->num_rows > 0) {
       while ($row = $result->fetch_assoc()) {
   ?>
   <Row>
    <Cell><Data ss:Type="Number"><?php echo $row['id']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['user_name']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['user_email']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo date('d/m/Y', strtotime($row['data_chamado'])); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['numero_chamado']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['tipo_reembolso']); ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?php echo $row['valor']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['status']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['informacoes_adicionais']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['comentario_admin']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo date('d/m/Y H:i', strtotime($row['updated_at'])); ?></Data></Cell>
   </Row>
   <?php
       }
   }
   ?>
  </Table>
 </Worksheet>
</Workbook> 