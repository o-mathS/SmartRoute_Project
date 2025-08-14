<?php
// Arquivo de conexão com o banco de dados MySQL usando PDO
$host = 'localhost';
$user = 'root';
$pass = 'Home@spSENAI2025!';
$db = 'smartroute';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}
?>
