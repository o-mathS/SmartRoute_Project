<?php
require_once '../backend/conexao.php';
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
// ---- STATUS COUNTS (pizza)
$sqlStatus = "SELECT estado, COUNT(*) qt FROM entregas GROUP BY estado";
$resStatus = $conn->query($sqlStatus);
$status_counts = ['Agendada'=>0,'Em andamento'=>0,'Concluído'=>0,'Cancelada'=>0];
while ($r = $resStatus->fetch_assoc()) {
    $status_counts[$r['estado']] = (int)$r['qt'];
}

// ---- Concluídas por dia (linha)
$sqlLinha = "SELECT DATE(data_conclusao) dia, COUNT(*) qt 
             FROM entregas 
             WHERE estado='Concluído' AND data_conclusao IS NOT NULL
             GROUP BY DATE(data_conclusao) ORDER BY dia ASC";
$resLinha = $conn->query($sqlLinha);
$concluidas_labels = $concluidas_values = [];
while ($r = $resLinha->fetch_assoc()) {
    $concluidas_labels[] = $r['dia'];
    $concluidas_values[] = (int)$r['qt'];
}

// ---- Por entregador (barras)
$sqlEnt = "SELECT COALESCE(e.nome,'Indefinido') entregador, COUNT(*) qt
           FROM entregas t
           LEFT JOIN entregadores e ON t.entregador_id = e.id
           GROUP BY e.nome ORDER BY qt DESC";
$resEnt = $conn->query($sqlEnt);
$por_entregador = [];
while ($r = $resEnt->fetch_assoc()) {
    $por_entregador[$r['entregador']] = (int)$r['qt'];
}

// ---- KPIs
$sqlHoje = "SELECT COUNT(*) qt FROM entregas WHERE estado='Concluído' AND DATE(data_conclusao)=CURDATE()";
$kpi_concluidas_hoje = (int)$conn->query($sqlHoje)->fetch_assoc()['qt'];

$sqlCanc = "SELECT COUNT(*) qt FROM entregas WHERE estado='Cancelada'";
$kpi_cancelamentos = (int)$conn->query($sqlCanc)->fetch_assoc()['qt'];

$sqlAtraso = "SELECT AVG(GREATEST(DATEDIFF(data_conclusao, data_entrega),0)) media FROM entregas WHERE estado='Concluído'";
$media_atraso = (float)($conn->query($sqlAtraso)->fetch_assoc()['media'] ?? 0);

$sqlTotConc = "SELECT COUNT(*) qt FROM entregas WHERE estado='Concluído' AND data_entrega IS NOT NULL";
$totConc = (int)$conn->query($sqlTotConc)->fetch_assoc()['qt'];

$sqlAtr = "SELECT COUNT(*) qt FROM entregas WHERE estado='Concluído' AND data_entrega IS NOT NULL AND data_conclusao > data_entrega";
$totAtr = (int)$conn->query($sqlAtr)->fetch_assoc()['qt'];

$perc_atrasadas = $totConc > 0 ? ($totAtr/$totConc) : 0.0;

// ---- resposta final
echo json_encode([
    'status_counts' => $status_counts,
    'concluidas_por_dia' => [
        'labels' => $concluidas_labels,
        'values' => $concluidas_values
    ],
    'por_entregador' => $por_entregador,
    'kpis' => [
        'concluidas_hoje' => $kpi_concluidas_hoje,
        'media_atraso_dias' => $media_atraso,
        'cancelamentos' => $kpi_cancelamentos,
        'perc_atrasadas' => $perc_atrasadas
    ]
], JSON_UNESCAPED_UNICODE);
