<?php
// Mock de uma API externa que retorna notícias dinâmicas de trânsito.
// Retorna JSON com formato compatível com assets/traffic_news.json
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/Sao_Paulo');
$now = new DateTime();

$sample = [
    [
        "id" => 101,
        "title" => "Acidente na Av. dos Bandeirantes",
        "description" => "Carro capotou na pista marginal, tráfego lento no sentido centro.",
        "time" => $now->format('Y-m-d H:i'),
        "location" => "Av. dos Bandeirantes",
        "lat" => -23.620,
        "lng" => -46.700,
        "severity" => "high"
    ],
    [
        "id" => 102,
        "title" => "Tráfego intenso na Marginal Pinheiros",
        "description" => "Fluxo acima do normal devido a volume de veículos.",
        "time" => $now->modify('-10 minutes')->format('Y-m-d H:i'),
        "location" => "Marginal Pinheiros",
        "lat" => -23.589,
        "lng" => -46.685,
        "severity" => "medium"
    ],
    [
        "id" => 103,
        "title" => "Sem incidentes na região central",
        "description" => "Trânsito fluido.",
        "time" => $now->modify('-20 minutes')->format('Y-m-d H:i'),
        "location" => "Centro",
        "lat" => -23.550,
        "lng" => -46.633,
        "severity" => "low"
    ]
];

echo json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
