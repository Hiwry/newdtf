<?php
include '../db/db_connection.php';

$quantidade = isset($_GET['quantidade']) ? (int)$_GET['quantidade'] : 0;
$pdo = getPDOConnection();

// Buscar o valor por cm² com base na quantidade
$stmt = $pdo->prepare("SELECT valor_por_cm2 FROM faixas_preco WHERE min_quantidade <= ? AND (max_quantidade >= ? OR max_quantidade = 0) LIMIT 1");
$stmt->execute([$quantidade, $quantidade]);
$valor_por_cm2 = $stmt->fetchColumn();

if ($valor_por_cm2 !== false) {
    echo json_encode(['valor_por_cm2' => $valor_por_cm2]);
} else {
    echo json_encode(['error' => 'Nenhum valor encontrado']);
}
?>
