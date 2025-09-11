<?php
require_once '../backend/conexao.php';
header('Content-Type: application/json; charset=utf-8');

// ---- filtros
$start       = $_GET['start_date'] ?? null;
$end         = $_GET['end_date'] ?? null;
$entregador  = trim($_GET['entregador'] ?? '');
$status      = trim($_GET['status'] ?? '');

// --- helpers
function runQuery($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// ---- Construindo WHERE dinâmico
$where = ['1=1']; // sempre verdadeiro pra não quebrar
$params = [];
$types = '';

if ($start) {
    $where[] = "(t.data_entrega >= ? OR t.data_conclusao >= ?)";
    $params[] = $start; $params[] = $start;
    $types .= 'ss';
}
if ($end) {
    $where[] = "(t.data_entrega <= ? OR t.data_conclusao <= ?)";
    $params[] = $end; $params[] = $end;
    $types .= 'ss';
}
if ($entregador) {
    $where[] = "e.nome = ?";
    $params[] = $entregador;
    $types .= 's';
}
if ($status) {
    $where[] = "t.estado = ?";
    $params[] = $status;
    $types .= 's';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ---- STATUS COUNTS (pizza)
$sqlStatus = "SELECT t.estado, COUNT(*) qt
              FROM entregas t
              LEFT JOIN entregadores e ON t.entregador_id = e.id
              $whereSql
              GROUP BY t.estado";
$resStatus = runQuery($conn, $sqlStatus, $types, $params);
$status_counts = ['Agendada'=>0,'Em andamento'=>0,'Concluído'=>0,'Cancelada'=>0];
while ($r = $resStatus->fetch_assoc()) {
    $status_counts[$r['estado']] = (int)$r['qt'];
}

// ---- Concluídas por dia (linha)
$sqlLinha = "SELECT DATE(t.data_conclusao) dia, COUNT(*) qt
             FROM entregas t
             LEFT JOIN entregadores e ON t.entregador_id = e.id
             $whereSql
             AND t.estado='Concluído' AND t.data_conclusao IS NOT NULL
             GROUP BY DATE(t.data_conclusao)
             ORDER BY dia ASC";
$resLinha = runQuery($conn, $sqlLinha, $types, $params);
$concluidas_labels = [];
$concluidas_values = [];
while ($r = $resLinha->fetch_assoc()) {
    $concluidas_labels[] = $r['dia'];
    $concluidas_values[] = (int)$r['qt'];
}

// ---- Por entregador (barras)
$sqlEnt = "SELECT COALESCE(e.nome,'Indefinido') entregador, COUNT(*) qt
           FROM entregas t
           LEFT JOIN entregadores e ON t.entregador_id = e.id
           $whereSql
           GROUP BY e.nome
           ORDER BY qt DESC";
$resEnt = runQuery($conn, $sqlEnt, $types, $params);
$por_entregador = [];
while ($r = $resEnt->fetch_assoc()) {
    $por_entregador[$r['entregador']] = (int)$r['qt'];
}

// ---- KPIs
// Concluídas hoje
$sqlHoje = "SELECT COUNT(*) qt
            FROM entregas t
            LEFT JOIN entregadores e ON t.entregador_id = e.id
            $whereSql
            AND t.estado='Concluído' AND DATE(t.data_conclusao) = CURDATE()";
$resHoje = runQuery($conn, $sqlHoje, $types, $params);
$kpi_concluidas_hoje = (int)$resHoje->fetch_assoc()['qt'];

// Total de cancelamentos
$sqlCanc = "SELECT COUNT(*) qt
            FROM entregas t
            LEFT JOIN entregadores e ON t.entregador_id = e.id
            $whereSql
            AND t.estado='Cancelada'";
$resCanc = runQuery($conn, $sqlCanc, $types, $params);
$kpi_cancelamentos = (int)$resCanc->fetch_assoc()['qt'];

// Atraso médio em dias
$sqlAtraso = "SELECT AVG(GREATEST(DATEDIFF(t.data_conclusao, t.data_entrega),0)) media
              FROM entregas t
              LEFT JOIN entregadores e ON t.entregador_id = e.id
              $whereSql
              AND t.estado='Concluído'
              AND t.data_conclusao IS NOT NULL
              AND t.data_entrega IS NOT NULL";
$resAtraso = runQuery($conn, $sqlAtraso, $types, $params);
$media_atraso = (float)($resAtraso->fetch_assoc()['media'] ?? 0);

// Percentual de entregas atrasadas
$sqlTotConc = "SELECT COUNT(*) qt
               FROM entregas t
               LEFT JOIN entregadores e ON t.entregador_id = e.id
               $whereSql
               AND t.estado='Concluído' AND t.data_entrega IS NOT NULL";
$resTotConc = runQuery($conn, $sqlTotConc, $types, $params);
$totConc = (int)$resTotConc->fetch_assoc()['qt'];

$sqlAtr = "SELECT COUNT(*) qt
           FROM entregas t
           LEFT JOIN entregadores e ON t.entregador_id = e.id
           $whereSql
           AND t.estado='Concluído' AND t.data_entrega IS NOT NULL AND t.data_conclusao > t.data_entrega";
$resAtr = runQuery($conn, $sqlAtr, $types, $params);
$totAtr = (int)$resAtr->fetch_assoc()['qt'];

$perc_atrasadas = $totConc > 0 ? ($totAtr / $totConc) : 0.0;

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
