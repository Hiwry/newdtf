<?php
session_start();
include '../db/db_connection.php';
include '../config.php';

$pdo = getPDOConnection();

// Não limitar o histórico apenas para admins
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Função para formatar a data no formato desejado
function formatarData($dataPedido) {
    $timestamp = strtotime($dataPedido);
    $hora = date('H:i', $timestamp); // Exibe a hora no formato 16:38
    $data = date('d/m/Y', $timestamp); // Exibe a data no formato 08/09/2024
    return "$hora - $data"; // Retorna a hora e a data concatenados
}

// Definir o número de itens por página
$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Variável para armazenar os resultados da pesquisa
$searchTerm = '';
$searchVendedor = '';
$searchDataEntrega = '';

// Ajustar a consulta com filtros (vendedor, data de entrega e nome do cliente/NT)
$queryBase = "
    SELECT 
        p.numero_pedido, 
        p.cliente, 
        p.data_pedido, 
        p.data_entrega, 
        p.numero_nt, 
        p.valor_total AS valor_total_pedido, 
        p.usuario_responsavel AS vendedor,  -- Renomear para vendedor
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
    WHERE 1=1
";

$params = [];

// Consulta para buscar o histórico de pedidos com filtros
try {
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = htmlspecialchars($_GET['search']);
        $queryBase .= " AND (p.numero_pedido LIKE ? OR p.cliente LIKE ? OR p.numero_nt LIKE ?)";
        $params = array_merge($params, ['%' . $searchTerm . '%', '%' . $searchTerm . '%', '%' . $searchTerm . '%']);
    }

    if (isset($_GET['vendedor']) && !empty($_GET['vendedor'])) {
        $searchVendedor = htmlspecialchars($_GET['vendedor']);
        $queryBase .= " AND p.usuario_responsavel LIKE ?";
        $params[] = '%' . $searchVendedor . '%';
    }

    if (isset($_GET['data_entrega']) && !empty($_GET['data_entrega'])) {
        $searchDataEntrega = htmlspecialchars($_GET['data_entrega']);
        $queryBase .= " AND p.data_entrega = ?";
        $params[] = $searchDataEntrega;
    }

    // Adicionar ordenação e paginação sem usar parâmetros no LIMIT
    $queryBase .= " ORDER BY p.data_pedido DESC LIMIT $offset, $itensPorPagina";
    $stmt = $pdo->prepare($queryBase);

    // Executar a consulta
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contagem total de pedidos (para a paginação)
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM pedidos");
    $totalPedidos = $stmtTotal->fetchColumn();
    $totalPaginas = ceil($totalPedidos / $itensPorPagina);
} catch (PDOException $e) {
    error_log("Erro ao buscar pedidos: " . $e->getMessage());
    die("Erro ao buscar pedidos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pedidos</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>

<body>

    <?php include '../includes/navbar.php'; ?>

    <h1>Histórico de Pedidos</h1>

    <!-- Barra de pesquisa -->
    <form method="GET" action="" style="margin-bottom: 20px;">
        <label for="search">Pesquisar Pedido/Cliente/NT:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($searchTerm); ?>" placeholder="Número do Pedido, Cliente ou NT">

        <label for="vendedor">Vendedor:</label>
        <input type="text" id="vendedor" name="vendedor" value="<?= htmlspecialchars($searchVendedor); ?>" placeholder="Nome do Vendedor">

        <label for="data_entrega">Data de Entrega:</label>
        <input type="date" id="data_entrega" name="data_entrega" value="<?= htmlspecialchars($searchDataEntrega); ?>">

        <button type="submit">Pesquisar</button>
        <a href="historico_pedidos.php">Limpar Pesquisa</a>
    </form>

    <?php if ($_SESSION['role'] === 'admin'): ?> <!-- Apenas para administradores -->
    <form action="export_excel.php" method="post">
        <button type="submit" class="btn-download-excel">Download Histórico Completo (Excel)</button>
    </form>
<?php endif; ?>


    <!-- Tabela de pedidos -->
    <table>
        <thead>
            <tr>
                <th>Nº do Pedido</th>
                <th>Cliente</th>
                <th>Data do Pedido</th>
                <th>Data de Entrega</th>
                <th>Número da NT</th>
                <th>Valor Total do Pedido</th>
                <th>Vendedor</th> <!-- Alterado para "Vendedor" -->
                <th>Aplicações</th>
            </tr>
        </thead>
        <tbody>
    <?php if (!empty($pedidos)): ?>
        <?php 
        $ultimoPedido = null;
        foreach ($pedidos as $pedido): 
            // Exibe o pedido somente uma vez
            if ($ultimoPedido !== $pedido['numero_pedido']):
                // Inicializando variáveis de resumo
                $totalAltura = 0;
                $totalValorAplicacao = 0;
                $totalArea = 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($pedido['numero_pedido']) ?></td>
                    <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                    <td><?= formatarData($pedido['data_pedido']) ?></td> <!-- Exibindo data formatada -->
                    <td><?= htmlspecialchars($pedido['data_entrega']) ?></td>
                    <td><?= htmlspecialchars($pedido['numero_nt']) ?></td>
                    <td>R$ <?= number_format($pedido['valor_total_pedido'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pedido['vendedor']) ?></td>
                    <td>
                        <ul>
            <?php endif; ?>
                        <li>
                            <?php
                            // Cálculo do valor unitário e altura total para cada aplicação
                            $area = $pedido['largura'] * $pedido['altura'] * $pedido['quantidade']; // área total
                            $totalAltura += $pedido['altura'] * $pedido['quantidade']; // altura total utilizada
                            $totalArea += $area;
                            $totalValorAplicacao += $pedido['valor_total_aplicacao']; // soma valor total

                            ?>
                            Tamanho: <?= htmlspecialchars($pedido['largura']) ?> x <?= htmlspecialchars($pedido['altura']) ?> cm, 
                            Quantidade: <?= htmlspecialchars($pedido['quantidade']) ?>,
                            Valor Total da Aplicação: R$ <?= number_format($pedido['valor_total_aplicacao'], 2, ',', '.') ?>
                        </li>
            <?php 
            // Fecha o bloco de exibição de pedidos
            $ultimoPedido = $pedido['numero_pedido'];
            if ($ultimoPedido !== $pedido['numero_pedido'] || next($pedidos) === false): ?>
                        </ul>
                        <p><strong>Área Total Utilizada:</strong> <?= number_format($totalArea, 2, ',', '.') ?> cm²</p>
                        <p><strong>Altura Total Utilizada:</strong> <?= number_format($totalAltura, 2, ',', '.') ?> cm</p>
                        <p><strong>Valor Unitário Total:</strong> R$ <?= number_format($totalValorAplicacao / $totalArea, 2, ',', '.') ?> por cm²</p>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">Nenhum pedido encontrado.</td>
        </tr>
    <?php endif; ?>
</tbody>

    </table>

<!-- Paginação -->
<?php if ($totalPaginas > 1): ?>
    <div class="pagination-container">
        <ul class="pagination">
            <?php if ($paginaAtual > 1): ?>
                <li><a href="?pagina=1<?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>"><i class="fas fa-angle-double-left"></i> Primeira página</a></li>
                <li><a href="?pagina=<?= $paginaAtual - 1; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>"><i class="fas fa-angle-left"></i> Página anterior</a></li>
            <?php else: ?>
                <li class="disabled"><i class="fas fa-angle-double-left"></i> Primeira página</li>
                <li class="disabled"><i class="fas fa-angle-left"></i> Página anterior</li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li><a href="?pagina=<?= $i; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="<?= ($i == $paginaAtual) ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a></li>
            <?php endfor; ?>

            <?php if ($paginaAtual < $totalPaginas): ?>
                <li><a href="?pagina=<?= $paginaAtual + 1; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">Próxima página <i class="fas fa-angle-right"></i></a></li>
                <li><a href="?pagina=<?= $totalPaginas; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">Última página <i class="fas fa-angle-double-right"></i></a></li>
            <?php else: ?>
                <li class="disabled">Próxima página <i class="fas fa-angle-right"></i></li>
                <li class="disabled">Última página <i class="fas fa-angle-double-right"></i></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>



</body>
</html>
