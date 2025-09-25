<?php
if (!isset($_GET['address'])) exit;

$address = urlencode($_GET['address']);
$url = "https://nominatim.openstreetmap.org/search?format=json&q={$address}";

// Configura User-Agent obrigatório pelo Nominatim
$options = [
    "http" => [
        "header" => "User-Agent: SmartRouteApp/1.0\r\n"
    ]
];
$context = stream_context_create($options);
$data = file_get_contents($url, false, $context);

header('Content-Type: application/json');
echo $data;
