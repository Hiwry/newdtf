<?php
session_start();
include '../db/db_connection.php'; // Inclui a conexão ao banco de dados
$pdo = getPDOConnection();

// Capturar dados do pedido via POST
$cliente = $_POST['cliente'];
$numero_nt = $_POST['numero_nt'];
$data_entrega = $_POST['data_entrega'];
$usuario_responsavel = $_SESSION['username'];
$data_pedido = date('Y-m-d H:i:s', time());

// Verificar o último número de pedido para gerar o próximo número em sequência
$stmt = $pdo->query("SELECT numero_pedido FROM pedidos_dtf ORDER BY id DESC LIMIT 1");
$ultimo_numero_pedido = $stmt->fetchColumn();
$numero_pedido = $ultimo_numero_pedido ? $ultimo_numero_pedido + 1 : 1; // Se não existir, começa em 1

// Inserir o novo pedido
$stmt = $pdo->prepare("INSERT INTO pedidos_dtf (cliente, data_pedido, data_entrega, numero_pedido, numero_nt, usuario_responsavel) 
    VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$cliente, $data_pedido, $data_entrega, $numero_pedido, $numero_nt, $usuario_responsavel]);

$pedido_id = $pdo->lastInsertId(); // Captura o ID do pedido gerado

// Inserir itens do pedido
foreach ($_POST['itens'] as $item) {
    $stmt = $pdo->prepare("INSERT INTO itens_pedido_dtf (pedido_id, nome_arte, largura, altura, quantidade, area, valor_unitario, valor_total) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $pedido_id, 
        $item['nome_arte'], 
        $item['largura'], 
        $item['altura'], 
        $item['quantidade'], 
        $item['area'], 
        $item['valor_unitario'], 
        $item['valor_total']
    ]);
}

echo json_encode(['success' => true, 'numero_pedido' => $numero_pedido]);
