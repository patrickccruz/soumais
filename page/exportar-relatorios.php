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
header('Content-Disposition: attachment;filename="relatorios_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Construir a consulta SQL com filtros
$where = [];
if (!empty($_GET['data_chamado'])) {
    $where[] = "reports.data_chamado = '{$_GET['data_chamado']}'";
}
if (!empty($_GET['cliente'])) {  
    $where[] = "reports.cliente LIKE '%{$_GET['cliente']}%'";
}
if (!empty($_GET['user_id'])) {
    $where[] = "reports.user_id = {$_GET['user_id']}";
}
$whereSQL = implode(' AND ', $where);
$sql = "SELECT reports.*, users.name as user_name 
        FROM reports 
        JOIN users ON reports.user_id = users.id 
        " . ($whereSQL ? "WHERE $whereSQL" : "") . " 
        ORDER BY reports.data_chamado DESC";

$result = $conn->query($sql);

// Iniciar a saída do Excel
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Worksheet ss:Name="Relatórios">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">ID</Data></Cell>
    <Cell><Data ss:Type="String">Técnico</Data></Cell>
    <Cell><Data ss:Type="String">Data</Data></Cell>
    <Cell><Data ss:Type="String">Número do Chamado</Data></Cell>
    <Cell><Data ss:Type="String">Cliente</Data></Cell>
    <Cell><Data ss:Type="String">Tipo de Chamado</Data></Cell>
    <Cell><Data ss:Type="String">Status</Data></Cell>
    <Cell><Data ss:Type="String">KM Inicial</Data></Cell>
    <Cell><Data ss:Type="String">KM Final</Data></Cell>
    <Cell><Data ss:Type="String">KM Percorrido</Data></Cell>
    <Cell><Data ss:Type="String">Hora Chegada</Data></Cell>
    <Cell><Data ss:Type="String">Hora Saída</Data></Cell>
    <Cell><Data ss:Type="String">Tempo de Atendimento</Data></Cell>
    <Cell><Data ss:Type="String">Informações Adicionais</Data></Cell>
   </Row>
   <?php
   if ($result && $result->num_rows > 0) {
       while ($row = $result->fetch_assoc()) {
           // Cálculo de KM percorridos
           $km_percorrido = $row['km_final'] - $row['km_inicial'];
           
           // Cálculo do tempo de atendimento
           $hora_chegada = strtotime($row['hora_chegada']);
           $hora_saida = strtotime($row['hora_saida']);
           $diferenca = $hora_saida - $hora_chegada;
           $horas = floor($diferenca / 3600);
           $minutos = floor(($diferenca % 3600) / 60);
           $tempo_atendimento = '';
           if ($horas > 0) {
               $tempo_atendimento .= $horas . 'h ';
           }
           $tempo_atendimento .= $minutos . 'min';
   ?>
   <Row>
    <Cell><Data ss:Type="Number"><?php echo $row['id']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['user_name']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo date('d/m/Y', strtotime($row['data_chamado'])); ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?php echo $row['numero_chamado']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['cliente']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['tipo_chamado']); ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['status_chamado']); ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?php echo $row['km_inicial']; ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?php echo $row['km_final']; ?></Data></Cell>
    <Cell><Data ss:Type="Number"><?php echo $km_percorrido; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $row['hora_chegada']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $row['hora_saida']; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo $tempo_atendimento; ?></Data></Cell>
    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($row['informacoes_adicionais']); ?></Data></Cell>
   </Row>
   <?php
       }
   }
   ?>
  </Table>
 </Worksheet>
</Workbook>
