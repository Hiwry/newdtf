<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Acesso negado. Apenas administradores podem exportar dados.");
}

require '../vendor/autoload.php'; // Corrigido: volta um nível para acessar a pasta 'vendor'
include '../db/db_connection.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Conectar ao banco de dados
$pdo = getPDOConnection();

try {
    // Consulta para buscar todos os pedidos e suas aplicações
    $query = "
        SELECT 
            p.numero_pedido, 
            p.cliente, 
            p.data_pedido, 
            p.data_entrega, 
            p.numero_nt, 
            p.valor_total AS valor_total_pedido, 
            p.usuario_responsavel AS vendedor, 
            a.nome_arte, 
            a.largura, 
            a.altura, 
            a.quantidade, 
            a.valor_total AS valor_total_aplicacao
        FROM 
            pedidos p
        INNER JOIN 
            pedido_aplicacoes a 
        ON 
            p.numero_pedido = a.numero_pedido
        ORDER BY p.data_pedido DESC
    ";
    
    $stmt = $pdo->query($query);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cria uma nova planilha
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Define os títulos das colunas
    $sheet->setCellValue('A1', 'Nº do Pedido');
    $sheet->setCellValue('B1', 'Cliente');
    $sheet->setCellValue('C1', 'Data do Pedido');
    $sheet->setCellValue('D1', 'Data de Entrega');
    $sheet->setCellValue('E1', 'Número da NT');
    $sheet->setCellValue('F1', 'Valor Total do Pedido');
    $sheet->setCellValue('G1', 'Vendedor');
    $sheet->setCellValue('H1', 'Nome da Arte');
    $sheet->setCellValue('I1', 'Largura (cm)');
    $sheet->setCellValue('J1', 'Altura (cm)');
    $sheet->setCellValue('K1', 'Quantidade');
    $sheet->setCellValue('L1', 'Valor Total da Aplicação');

    // Preenche os dados dos pedidos
    $row = 2; // Começa na linha 2, já que a linha 1 contém os títulos
    foreach ($pedidos as $pedido) {
        $sheet->setCellValue('A' . $row, $pedido['numero_pedido']);
        $sheet->setCellValue('B' . $row, $pedido['cliente']);
        $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($pedido['data_pedido'])));
        $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($pedido['data_entrega'])));
        $sheet->setCellValue('E' . $row, $pedido['numero_nt']);
        $sheet->setCellValue('F' . $row, number_format($pedido['valor_total_pedido'], 2, ',', '.'));
        $sheet->setCellValue('G' . $row, $pedido['vendedor']);
        $sheet->setCellValue('H' . $row, $pedido['nome_arte']);
        $sheet->setCellValue('I' . $row, $pedido['largura']);
        $sheet->setCellValue('J' . $row, $pedido['altura']);
        $sheet->setCellValue('K' . $row, $pedido['quantidade']);
        $sheet->setCellValue('L' . $row, number_format($pedido['valor_total_aplicacao'], 2, ',', '.'));
        $row++;
    }

    // Gera o arquivo Excel
    $writer = new Xlsx($spreadsheet);

    // Define o cabeçalho para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="historico_pedidos.xlsx"');
    header('Cache-Control: max-age=0');

    // Envia o arquivo para o navegador
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    error_log("Erro ao gerar arquivo Excel: " . $e->getMessage());
    echo "Erro ao gerar arquivo Excel.";
}
