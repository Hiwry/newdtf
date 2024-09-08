<?php include $_SERVER['DOCUMENT_ROOT'] . '/sistema_dtf/config.php'; ?>

<nav class="navbar">
    <div class="navbar-brand">Calculadora DTF</div>
    <ul class="navbar-links">
        <li><a href="<?= BASE_URL ?>index.php">Página Inicial</a></li>
        <li><a href="<?= BASE_URL ?>pages/dtf.php">DTF</a></li>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="<?= BASE_URL ?>pages/admin.php">Controle de Usuários</a></li>
            <li><a href="<?= BASE_URL ?>pages/historico_pedidos.php">Histórico de Pedidos</a></li>
        <?php endif; ?>

        <li><a href="<?= BASE_URL ?>logout.php" class="logout-btn">Sair</a></li>
    </ul>
</nav>
