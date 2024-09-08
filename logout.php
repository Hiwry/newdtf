<?php
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy(); // Destruir todas as sessões ativas
}
include 'config.php'; // Inclui a constante de caminho base
header('Location: ' . BASE_URL . 'pages/login.php'); // Redirecionar para a página de login
exit;
?>
