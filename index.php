<?php
include 'config.php'; // Inclui o arquivo de configuração para definir BASE_URL
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Inicial</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="icon" href="<?= BASE_URL ?>images/favicon.ico" type="image/x-icon"> <!-- Caminho mais consistente -->
</head>
<body>

    <?php include 'includes/navbar.php'; // Inclui a navbar ?>

    <main>
        <h1>Bem-vindo ao Sistema</h1>
    </main>

</body>
</html>
