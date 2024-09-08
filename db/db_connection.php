<?php
function getPDOConnection() {
    // Utilize variáveis de ambiente para obter as credenciais do banco de dados
    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_NAME') ?: 'meu_sistema';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';  // Pode ser vazia para ambientes locais

    // Define o charset UTF-8 explicitamente
    $charset = 'utf8mb4';
    
    // Usar uma única instância de conexão (padrão singleton)
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erro ao conectar ao banco de dados: " . $e->getMessage());
        }
    }

    return $pdo;
}
