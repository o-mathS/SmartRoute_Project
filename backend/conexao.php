<?php
// --- Caminho absoluto seguro ---
$rootPath = __DIR__;

// --- Configurações do banco ---
$servername = "localhost"; // <- Host correto no cPanel
$username   = "devgom44_sapoeslight";
$password   = "sapoeslight@1234!";
$dbname     = "devgom44_smartroute"; // <- provavelmente o banco correto tem prefixo igual ao usuário

// --- Conexão ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Tratamento de erro ---
if ($conn->connect_error) {
    error_log("Erro na conexão: " . $conn->connect_error);
    die("<h3>⚠️ Erro ao conectar ao banco de dados.</h3>");
}

// --- Define charset UTF-8 ---
$conn->set_charset("utf8mb4");
?>
