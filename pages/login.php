<?php
session_start();
include '../db/db_connection.php'; // Inclui a conexão com o banco de dados
include '../config.php'; // Inclui o arquivo de configuração

// Exibir erros para ajudar no desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = ''; // Variável para armazenar mensagens de erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $pdo = getPDOConnection(); // Obter a conexão com o banco de dados

        // Verificar se o usuário existe no banco de dados
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirecionar com base no papel do usuário
                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . 'pages/admin.php');
                } else {
                    header('Location: ' . BASE_URL . 'index.php');
                }
                exit;
            } else {
                $error = "Senha incorreta!";
            }
        } else {
            $error = "Nome de usuário não encontrado!";
        }
    } catch (PDOException $e) {
        $error = "Erro ao conectar ao banco de dados: " . $e->getMessage();
    }
}

include '../includes/navbar.php'; // Inclui a navbar
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css"> <!-- Link para o CSS -->
</head>
<body>

    <div class="login-container">
        <h2>Login</h2>

        <!-- Exibe a mensagem de erro, se houver -->
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Formulário de login -->
        <form action="<?= BASE_URL ?>pages/login.php" method="post">
            <label for="username">Nome de usuário:</label>
            <input type="text" id="username" name="username" required><br>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Entrar</button>
        </form>
    </div>

</body>
</html>
