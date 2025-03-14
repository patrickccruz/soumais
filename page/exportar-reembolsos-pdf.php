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

// Verificar se as bibliotecas PDF estão disponíveis
$useTCPDF = false;
$useMPDF = false;

if (file_exists('../vendor/tecnickcom/tcpdf/tcpdf.php')) {
    require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
    $useTCPDF = true;
} elseif (file_exists('../vendor/mpdf/mpdf/src/Mpdf.php')) {
    require_once '../vendor/mpdf/mpdf/src/Mpdf.php';
    $useMPDF = true;
}

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
$reembolsos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reembolsos[] = $row;
    }
}

// Se nenhuma biblioteca PDF estiver disponível, usar HTML que pode ser salvo como PDF
if (!$useTCPDF && !$useMPDF) {
    // Configurar cabeçalhos para HTML
    header('Content-Type: text/html; charset=utf-8');
    
    // HTML para exportação
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reembolsos - ' . date('d/m/Y') . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 30px; }
            h1 { color: #1d8031; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
            .alert { background-color: #e0f0e3; border: 1px solid #1d8031; color: #0f2714; padding: 10px; margin-bottom: 20px; border-radius: 4px; }
            .btn { display: inline-block; font-weight: 400; text-align: center; vertical-align: middle; cursor: pointer; padding: .375rem .75rem; font-size: 1rem; line-height: 1.5; border-radius: .25rem; color: #fff; background-color: #1d8031; border: 1px solid #1d8031; text-decoration: none; margin-right: 10px; }
            .btn-primary { background-color: #1d8031; border-color: #1d8031; }
            .btn-secondary { background-color: #0f2714; border-color: #0f2714; }
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Relatório de Reembolsos</h1>
            <p>Data de geração: ' . date('d/m/Y H:i') . '</p>
        </div>
        
        <div class="no-print" style="margin-bottom: 20px;">
            <div class="alert">
                <strong>Nota:</strong> Para salvar como PDF, use a função de impressão do seu navegador (Ctrl+P) e selecione "Salvar como PDF" na opção de impressora.
            </div>
            <button class="btn btn-primary" onclick="window.print()">Imprimir / Salvar como PDF</button>
            <a class="btn btn-secondary" href="todos-reembolsos.php">Voltar para Reembolsos</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Solicitante</th>
                    <th>Email</th>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Informações</th>
                </tr>
            </thead>
            <tbody>';
            
    foreach ($reembolsos as $reembolso) {
        $html .= '<tr>
            <td>' . $reembolso['id'] . '</td>
            <td>' . htmlspecialchars($reembolso['user_name']) . '</td>
            <td>' . htmlspecialchars($reembolso['user_email']) . '</td>
            <td>' . date('d/m/Y', strtotime($reembolso['data_chamado'])) . '</td>
            <td>' . htmlspecialchars($reembolso['tipo_reembolso']) . '</td>
            <td>R$ ' . number_format($reembolso['valor'], 2, ',', '.') . '</td>
            <td>' . htmlspecialchars($reembolso['status']) . '</td>
            <td>' . htmlspecialchars($reembolso['informacoes_adicionais']) . '</td>
        </tr>';
    }
            
    $html .= '</tbody>
        </table>
        
        <div class="footer">
            <p>© ' . date('Y') . ' SouDigital - Relatório gerado automaticamente</p>
        </div>
        
        <script>
            // Sugestão automática para imprimir após carregar
            window.addEventListener("load", function() {
                // Mostrar dica para o usuário
                setTimeout(function() {
                    const printMsg = document.createElement("div");
                    printMsg.style.position = "fixed";
                    printMsg.style.bottom = "20px";
                    printMsg.style.right = "20px";
                    printMsg.style.padding = "10px 15px";
                    printMsg.style.backgroundColor = "#d4edda";
                    printMsg.style.color = "#155724";
                    printMsg.style.borderRadius = "4px";
                    printMsg.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
                    printMsg.style.zIndex = "9999";
                    printMsg.innerHTML = "Clique em \"Imprimir / Salvar como PDF\" para salvar o relatório";
                    document.body.appendChild(printMsg);
                    
                    setTimeout(function() {
                        printMsg.style.display = "none";
                    }, 5000);
                }, 1000);
            });
        </script>
    </body>
    </html>';
    
    echo $html;
    exit;
}

// Usar TCPDF para gerar PDF
if ($useTCPDF) {
    // Criar uma instância do TCPDF
    class MYPDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 15);
            $this->Cell(0, 10, 'Relatório de Reembolsos - ' . date('d/m/Y'), 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->Ln(15);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }
    
    $pdf = new MYPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurações do documento
    $pdf->SetCreator('SouDigital');
    $pdf->SetAuthor('SouDigital');
    $pdf->SetTitle('Reembolsos - ' . date('d/m/Y'));
    $pdf->SetSubject('Relatório de Reembolsos');
    
    // Configurações de margens
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Adicionar uma página
    $pdf->AddPage();
    
    // Conteúdo
    $pdf->SetFont('helvetica', '', 10);
    
    // Criar tabela
    $html = '<table border="1" cellpadding="5">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>ID</th>
                <th>Solicitante</th>
                <th>Email</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Informações</th>
            </tr>
        </thead>
        <tbody>';
        
    foreach ($reembolsos as $reembolso) {
        $html .= '<tr>
            <td>' . $reembolso['id'] . '</td>
            <td>' . htmlspecialchars($reembolso['user_name']) . '</td>
            <td>' . htmlspecialchars($reembolso['user_email']) . '</td>
            <td>' . date('d/m/Y', strtotime($reembolso['data_chamado'])) . '</td>
            <td>' . htmlspecialchars($reembolso['tipo_reembolso']) . '</td>
            <td>R$ ' . number_format($reembolso['valor'], 2, ',', '.') . '</td>
            <td>' . htmlspecialchars($reembolso['status']) . '</td>
            <td>' . htmlspecialchars($reembolso['informacoes_adicionais']) . '</td>
        </tr>';
    }
        
    $html .= '</tbody></table>';
    
    // Escrever HTML no PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Fechar e enviar o PDF
    $pdf->Output('reembolsos_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}

// Usar mPDF como fallback se TCPDF não estiver disponível
if ($useMPDF) {
    $mpdf = new \Mpdf\Mpdf([
        'orientation' => 'L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
    ]);
    
    // Adicionar estilos
    $stylesheet = '
        body { font-family: Arial, sans-serif; }
        h1 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { text-align: center; font-size: 10px; color: #777; margin-top: 20px; }
    ';
    
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    
    // Conteúdo
    $html = '
    <h1>Relatório de Reembolsos - ' . date('d/m/Y') . '</h1>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Solicitante</th>
                <th>Email</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Informações</th>
            </tr>
        </thead>
        <tbody>';
        
    foreach ($reembolsos as $reembolso) {
        $html .= '<tr>
            <td>' . $reembolso['id'] . '</td>
            <td>' . htmlspecialchars($reembolso['user_name']) . '</td>
            <td>' . htmlspecialchars($reembolso['user_email']) . '</td>
            <td>' . date('d/m/Y', strtotime($reembolso['data_chamado'])) . '</td>
            <td>' . htmlspecialchars($reembolso['tipo_reembolso']) . '</td>
            <td>R$ ' . number_format($reembolso['valor'], 2, ',', '.') . '</td>
            <td>' . htmlspecialchars($reembolso['status']) . '</td>
            <td>' . htmlspecialchars($reembolso['informacoes_adicionais']) . '</td>
        </tr>';
    }
        
    $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>© ' . date('Y') . ' SouDigital - Relatório gerado automaticamente</p>
    </div>';
    
    $mpdf->WriteHTML($html);
    
    // Saída
    $mpdf->Output('reembolsos_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}
?> 