<?php
// Caminho para o index real dentro do projeto
$mainIndex = __DIR__ . '/SmartRoute_Project/views/index.php';

// Verifica se o arquivo existe antes de incluir
if (file_exists($mainIndex)) {
    include_once $mainIndex;
} else {
    http_response_code(500);
    echo "<h2>Erro interno: arquivo principal não encontrado.</h2>";
    echo "<p>Verifique se o caminho SmartRoute_Project/views/index.php está correto.</p>";
}
?>
