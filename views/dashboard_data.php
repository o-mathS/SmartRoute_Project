<?php
require_once '../backend/conexao.php';

// Inicializa arrays de resposta
$response = [
    'status_counts' => [],
    'concluidas_por_dia' => ['labels' => [], 'values' => []],
    'por_entregador' => [],
    'kpis' => []
];

// 1️⃣ Contagem por status
$result = $conn->query("
    SELECT estado, COUNT(*) as total
    FROM entregas
    GROUP BY estado
");
while ($row = $result->fetch_assoc()) {
    $response['status_counts'][$row['estado']] = intval($row['total']);
}

// 2️⃣ Concluídas por dia (últimos 30 dias)
$result = $conn->query("
    SELECT DATE(data_entrega) as dia, COUNT(*) as total
    FROM entregas
    WHERE estado = 'Concluído'
    AND data_entrega >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(data_entrega)
    ORDER BY DATE(data_entrega)
");
while ($row = $result->fetch_assoc()) {
    $response['concluidas_por_dia']['labels'][] = $row['dia'];
    $response['concluidas_por_dia']['values'][] = intval($row['total']);
}

// 3️⃣ Entregas por entregador (somente com entregador_id)
$result = $conn->query("
    SELECT e.nome as entregador, COUNT(en.id) as total
    FROM entregadores e
    LEFT JOIN entregas en ON en.entregador_id = e.id
    GROUP BY e.id
    ORDER BY e.nome
");
while ($row = $result->fetch_assoc()) {
    $response['por_entregador'][$row['entregador']] = intval($row['total']);
}

// 4️⃣ KPIs
// Entregas concluídas hoje
$response['kpis']['concluidas_hoje'] = intval($conn->query("
    SELECT COUNT(*) FROM entregas
    WHERE estado='Concluído' AND DATE(data_entrega)=CURDATE()
")->fetch_row()[0]);

// Média de atraso (em dias)
$media_atraso = $conn->query("
    SELECT AVG(DATEDIFF(NOW(), data_entrega)) 
    FROM entregas
    WHERE estado='Concluído' AND data_entrega < NOW()
")->fetch_row()[0];
$response['kpis']['media_atraso_dias'] = floatval($media_atraso);

// Cancelamentos
$response['kpis']['cancelamentos'] = intval($conn->query("
    SELECT COUNT(*) FROM entregas WHERE estado='Cancelada'
")->fetch_row()[0]);

// % entregas atrasadas
$total = intval($conn->query("SELECT COUNT(*) FROM entregas WHERE estado='Concluído'")->fetch_row()[0]);
$atrasadas = intval($conn->query("
    SELECT COUNT(*) FROM entregas WHERE estado='Concluído' AND data_entrega < NOW()
")->fetch_row()[0]);
$response['kpis']['perc_atrasadas'] = $total > 0 ? ($atrasadas/$total) : 0;

// Retorna JSON
header('Content-Type: application/json');
echo json_encode($response);
