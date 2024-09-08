<?php
session_start();
include '../db/db_connection.php';
include '../config.php';

$pdo = getPDOConnection();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora DTF</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php include '../includes/navbar.php'; ?>

    <h1>Calculadora DTF</h1>

    <div id="pre-selecao">
        <!-- Pre-seleção de tamanhos -->
        <button type="button" onclick="adicionarAplicacao('Escudo', 10, 10)">
            <i class="fas fa-shield-alt"></i> Escudo (10x10 cm)
        </button>
        <button type="button" onclick="adicionarAplicacao('A4', 29, 21)">
            <i class="fas fa-file-alt"></i> A4 (29x21 cm)
        </button>
        <button type="button" onclick="adicionarAplicacao('A3', 29.7, 42)">
            <i class="fas fa-file-alt"></i> A3 (29.7x42 cm)
        </button>
    </div>

    <div id="aplicacoes"></div>
    <div id="relatorioAplicacoes"></div>

    <div>
        <h3>Gerar Pedido</h3>
        <form id="gerar-pedido-form">
            <label for="nome-cliente">Nome do Cliente:</label>
            <input type="text" id="nome-cliente" required>
            
            <label for="numero-nt">Número da NT:</label>
            <input type="text" id="numero-nt" required>
            
            <label for="nome-arte">Nome da Arte:</label> <!-- Campo para nome da arte -->
            <input type="text" id="nome-arte" required>
            
            <label for="data-entrega">Data de Entrega:</label>
            <input type="date" id="data-entrega" required>

            <button type="submit">Gerar Pedido</button>
        </form>

    </div>

    <script src="../js/dtf_calculadora.js"></script>

</body>
</html>
