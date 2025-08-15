<?php
// Arquivo de conexão com o banco de dados MySQL usando MySQLi orientado a objetos
$host = 'localhost';
$user = 'root';
$pass = 'Home@spSENAI2025!';
$db = 'smartroute';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}
$conn->set_charset('utf8');
?>

