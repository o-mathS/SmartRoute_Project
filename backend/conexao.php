
<?php
// --- Caminho absoluto seguro ---
$rootPath = __DIR__;

// --- Configurações do banco ---
$servername = "devgom44_sapoeslight";
$username   = "devgom44_sapoeslight";
$password   = "sapoeslight@1234!";
$dbname     = "smartroute";

// --- Conexão ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Tratamento de erro ---
if ($conn->connect_error) {
    error_log("Erro na conexão: " . $conn->connect_error);
    die("<h3>⚠️ Erro ao conectar ao banco de dados.</h3>");
}

// --- Define charset UTF-8 ---
$conn->set_charset("utf8mb4");

// --- (Opcional) Confirma a conexão em ambiente de teste ---
// echo "Conectado com sucesso!";
?>
