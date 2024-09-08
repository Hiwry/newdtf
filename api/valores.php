<?php
include '../db/db_connection.php';
header('Content-Type: application/json');

try {
    // Estabelece a conexão com o banco de dados
    $pdo = getPDOConnection();

    // Executa a consulta no banco de dados
    $stmt = $pdo->query("SELECT * FROM valores_por_quantidade");
    $valores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verifica se há dados disponíveis
    if ($valores) {
        // Retorna os dados como JSON
        echo json_encode($valores, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // Caso não existam registros, retorna uma mensagem informativa
        echo json_encode(['message' => 'Nenhum valor encontrado']);
    }
} catch (PDOException $e) {
    // Retorna erro em caso de falha na consulta
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao consultar o banco de dados', 'details' => $e->getMessage()]);
}

// Adiciona cabeçalhos para controlar cache (opcional, se aplicável)
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');
?>
