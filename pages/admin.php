<?php
session_start();
include '../db/db_connection.php';
include '../config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pdo = getPDOConnection();

// Função para adicionar usuário
function adicionarUsuario($pdo, $username, $password, $role) {
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $role]);
    } catch (PDOException $e) {
        die('Erro ao adicionar usuário: ' . $e->getMessage());
    }
}

// Função para editar usuário
function editarUsuario($pdo, $id, $username, $role, $password = null) {
    try {
        if ($password) {
            $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $role, $id]);
        }
    } catch (PDOException $e) {
        die('Erro ao editar usuário: ' . $e->getMessage());
    }
}

// Função para excluir usuário
function excluirUsuario($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die('Erro ao excluir usuário: ' . $e->getMessage());
    }
}

// Processamento de ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        adicionarUsuario($pdo, $_POST['new_username'], $_POST['new_password'], $_POST['role']);
    }

    if (isset($_POST['edit_user'])) {
        editarUsuario($pdo, $_POST['edit_id'], $_POST['edit_username'], $_POST['edit_role'], $_POST['edit_password'] ?? null);
    }

    if (isset($_POST['update_valor_cm2'])) {
        $novo_valor_cm2 = floatval($_POST['valor_por_cm2']);
        try {
            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'valor_por_cm2'");
            $stmt->execute([$novo_valor_cm2]);
        } catch (PDOException $e) {
            die('Erro ao atualizar valor por cm²: ' . $e->getMessage());
        }
    }
}

// Processamento de exclusão via GET
if (isset($_GET['delete_id'])) {
    excluirUsuario($pdo, $_GET['delete_id']);
}

$usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);

// Busca o valor atual por cm²
$stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'valor_por_cm2'");
$stmt->execute();
$valor_por_cm2 = $stmt->fetchColumn();

include '../includes/navbar.php';
?>

<!-- HTML permanece o mesmo com a inclusão de sanitização de saída -->

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Controle de Usuários e Valores</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <h1>Controle de Usuários e Valores</h1>

    <h2>Adicionar Novo Usuário</h2>
    <form action="admin.php" method="post">
        <label for="new_username">Nome de Usuário:</label>
        <input type="text" id="new_username" name="new_username" placeholder="Nome de Usuário" required><br>

        <label for="new_password">Senha:</label>
        <input type="password" id="new_password" name="new_password" placeholder="Senha" required><br>

        <label for="role">Papel:</label>
        <select id="role" name="role">
            <option value="user">Usuário</option>
            <option value="admin">Admin</option>
        </select><br>

        <button type="submit" name="add_user">Adicionar Usuário</button>
    </form>

    <h2>Lista de Usuários</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome de Usuário</th>
                <th>Papel</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['id']); ?></td>
                    <td><?= htmlspecialchars($usuario['username']); ?></td>
                    <td><?= htmlspecialchars($usuario['role']); ?></td>
                    <td>
                        <form action="admin.php" method="post" style="display:inline;">
                            <input type="hidden" name="edit_id" value="<?= $usuario['id']; ?>">
                            <input type="text" name="edit_username" value="<?= htmlspecialchars($usuario['username']); ?>" required>
                            <input type="password" name="edit_password" placeholder="Nova senha (opcional)">
                            <select name="edit_role">
                                <option value="user" <?= $usuario['role'] === 'user' ? 'selected' : ''; ?>>Usuário</option>
                                <option value="admin" <?= $usuario['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <button type="submit" name="edit_user">Salvar</button>
                        </form>
                        <a href="admin.php?delete_id=<?= $usuario['id']; ?>" class="delete-btn">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Alterar Valor por cm² (Usado na Calculadora DTF)</h2>
    <form method="POST" action="admin.php">
        <label for="valor_por_cm2">Valor por cm² (R$):</label>
        <input type="number" step="0.01" id="valor_por_cm2" name="valor_por_cm2" value="<?= htmlspecialchars($valor_por_cm2) ?>" required>
        <button type="submit" name="update_valor_cm2">Atualizar Valor</button>
    </form>

</body>
</html>
