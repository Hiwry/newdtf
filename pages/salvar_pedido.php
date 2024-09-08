<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../db/db_connection.php';
header('Content-Type: application/json');

// Conectar ao banco de dados
try {
    $pdo = getPDOConnection();
    error_log("Conexão com o banco de dados estabelecida com sucesso.");
} catch (PDOException $e) {
    error_log("Erro ao conectar ao banco de dados: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

// Captura os dados enviados via POST
$inputData = json_decode(file_get_contents('php://input'), true);

// Verificar se os dados foram capturados corretamente
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Erro ao decodificar JSON: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Erro ao decodificar JSON']);
    exit;
}

error_log("Dados recebidos no salvar_pedido.php: " . print_r($inputData, true));

// Variáveis capturadas do pedido
$nomeCliente = $inputData['nomeCliente'] ?? '';
$numeroNT = $inputData['numeroNT'] ?? '';
$dataEntrega = $inputData['dataEntrega'] ?? '';
$aplicacoes = $inputData['aplicacoes'] ?? [];
$valorTotal = $inputData['valorTotal'] ?? 0;

// Captura o nome do usuário da sessão
$usuarioResponsavel = $_SESSION['username'] ?? 'Desconhecido';  // Se o usuário não estiver logado, insere 'Desconhecido'

// Verifica se os campos obrigatórios estão preenchidos
if (empty($nomeCliente) || empty($numeroNT) || empty($dataEntrega) || empty($aplicacoes)) {
    error_log("Erro: campos obrigatórios faltando. NomeCliente: $nomeCliente, NumeroNT: $numeroNT, DataEntrega: $dataEntrega, Aplicacoes: " . print_r($aplicacoes, true));
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios faltando.']);
    exit;
}

// Prepara a data do pedido
$dataPedido = date('Y-m-d H:i:s');
error_log("Dados recebidos: NomeCliente - $nomeCliente, NumeroNT - $numeroNT, DataEntrega - $dataEntrega, ValorTotal - $valorTotal, Usuario - $usuarioResponsavel");

// Inicia a transação e tenta inserir o pedido
try {
    $pdo->beginTransaction();

    // Gera um novo número de pedido único
    $stmt = $pdo->query("SELECT IFNULL(MAX(numero_pedido), 0) + 1 AS novo_numero_pedido FROM pedidos");
    $novoNumeroPedido = $stmt->fetchColumn();
    error_log("Número de pedido gerado: $novoNumeroPedido");

    // Insere o pedido na tabela `pedidos`, incluindo o campo para o nome do usuário
    $stmt = $pdo->prepare("INSERT INTO pedidos (numero_pedido, cliente, data_pedido, data_entrega, numero_nt, valor_total, usuario_responsavel) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$novoNumeroPedido, $nomeCliente, $dataPedido, $dataEntrega, $numeroNT, $valorTotal, $usuarioResponsavel]);

    if (!$result) {
        error_log("Erro ao inserir pedido: " . print_r($stmt->errorInfo(), true));
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao inserir pedido']);
        exit;
    }
    error_log("Pedido inserido com sucesso: Pedido Nº $novoNumeroPedido");

    // Insere os itens do pedido na tabela `pedido_aplicacoes`
    foreach ($aplicacoes as $aplicacao) {
        $nomeArte = $inputData['nomeArte'] ?? '';

        if (empty($nomeArte)) {
            echo json_encode(['success' => false, 'message' => 'O nome da arte é obrigatório.']);
            exit;
        }

        // Verifica se todos os campos obrigatórios estão presentes
        if (!isset($aplicacao['largura']) || !isset($aplicacao['altura']) || !isset($aplicacao['quantidade']) || !isset($aplicacao['valorTotal'])) {
            error_log("Erro: Campos obrigatórios faltando no item: " . print_r($aplicacao, true));
            echo json_encode(['success' => false, 'message' => 'Erro ao gerar pedido. Dados incompletos.']);
            exit;
        }

        error_log("Inserindo aplicação no pedido: " . print_r($aplicacao, true));

        // Insere os dados no banco, incluindo o nome da arte
        $stmt = $pdo->prepare("INSERT INTO pedido_aplicacoes (numero_pedido, nome_arte, largura, altura, quantidade, valor_total) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $novoNumeroPedido,
            $nomeArte,
            $aplicacao['largura'],
            $aplicacao['altura'],
            $aplicacao['quantidade'],
            $aplicacao['valorTotal']
        ]);

        if (!$result) {
            error_log("Erro ao inserir item de aplicação: " . print_r($stmt->errorInfo(), true));
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro ao inserir item de aplicação']);
            exit;
        }
    }

    // Confirma a transação
    $pdo->commit();
    error_log("Pedido e itens confirmados com sucesso. Pedido Nº: $novoNumeroPedido");

    echo json_encode(['success' => true, 'message' => 'Pedido gerado com sucesso!']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao gerar pedido: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao gerar pedido']);
}
