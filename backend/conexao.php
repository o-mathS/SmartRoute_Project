<?php
// Arquivo de conexão com o banco de dados MySQL
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'smartroute';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}
?>
