<?php
require_once '../backend/conexao.php';
header('Content-Type: application/json; charset=utf-8');

// ---- filtros
$start = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end   = isset($_GET['end_date'])   ? $_GET['end_date']   : null;
$entregador = isset($_GET['entregador']) ? trim($_GET['entregador']) : null;
$status     = isset($_GET['status'])     ? trim($_GET['status'])     : null;

// constrói WHERE dinâmico
$where = [];
$params = [];
$types  = '';

if ($start) { $where[] = "( (data_entrega IS NOT NULL AND data_entrega >= ?) OR (data_conclusao IS NOT NULL AND data_conclusao >= ?) )"; $params[]=$start; $params[]=$start; $types.='ss'; }
if ($end)   { $where[] = "( (data_entrega IS NOT NULL AND data_entrega <= ?) OR (data_conclusao IS NOT NULL AND data_conclusao <= ?) )"; $params[]=$end; $params[]=$end; $types.='ss'; }
if ($entregador) { $where[] = "COALESCE(entregador,'Indefinido') = ?"; $params[]=$entregador; $types.='s'; }
if ($status)     { $where[] = "estado = ?"; $params[]=$status; $types.='s'; }

$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

// --- helper de query preparada
function runQuery($conn, $sql, $types='', $params=[]) {
  $stmt = $conn->prepare($sql);
  if ($types && $params) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  return $stmt->get_result();
}

// ---- STATUS COUNTS (pizza)
$sqlStatus = "SELECT estado, COUNT(*) qt FROM entregas $whereSql GROUP BY estado";
$res = runQuery($conn, $sqlStatus, $types, $params);
$status_counts = ['Agendada'=>0,'Em andamento'=>0,'Concluído'=>0,'Cancelada'=>0];
while ($row = $res->fetch_assoc()) {
  $status_counts[$row['estado']] = (int)$row['qt'];
}

// ---- CONCLUÍDAS POR DIA (linha)
$sqlLinha = "SELECT DATE(data_conclusao) dia, COUNT(*) qt
             FROM entregas
             $whereSql
             AND estado='Concluído' AND data_conclusao IS NOT NULL
             GROUP BY DATE(data_conclusao)
             ORDER BY dia ASC";
$resLinha = runQuery($conn, $sqlLinha, $types, $params);
$concluidas_labels = [];
$concluidas_values = [];
while ($r = $resLinha->fetch_assoc()) {
  $concluidas_labels[] = $r['dia'];
  $concluidas_values[] = (int)$r['qt'];
}

// ---- POR ENTREGADOR (barras)
$sqlEnt = "SELECT COALESCE(entregador,'Indefinido') entregador, COUNT(*) qt
           FROM entregas
           $whereSql
           GROUP BY COALESCE(entregador,'Indefinido')
           ORDER BY qt DESC";
$resEnt = runQuery($conn, $sqlEnt, $types, $params);
$por_entregador = [];
while ($r = $resEnt->fetch_assoc()) {
  $por_entregador[$r['entregador']] = (int)$r['qt'];
}

// ---- KPIs
// concluidas hoje
$sqlHoje = "SELECT COUNT(*) qt FROM entregas $whereSql AND estado='Concluído' AND DATE(data_conclusao)=CURDATE()";
$resHoje = runQuery($conn, $sqlHoje, $types, $params);
$kpi_concluidas_hoje = (int)$resHoje->fetch_assoc()['qt'];

// total de cancelamentos
$sqlCanc = "SELECT COUNT(*) qt FROM entregas $whereSql AND estado='Cancelada'";
$resCanc = runQuery($conn, $sqlCanc, $types, $params);
$kpi_cancelamentos = (int)$resCanc->fetch_assoc()['qt'];

// atraso médio em dias (somente concluídas com data_entrega preenchida)
$sqlAtraso = "SELECT AVG(GREATEST(DATEDIFF(data_conclusao, data_entrega),0)) media
              FROM entregas
              $whereSql
              AND estado='Concluído'
              AND data_conclusao IS NOT NULL
              AND data_entrega IS NOT NULL";
$resAtraso = runQuery($conn, $sqlAtraso, $types, $params);
$media_atraso = (float)($resAtraso->fetch_assoc()['media'] ?? 0);

// percentual de atrasadas (concluídas com data_conclusao > data_entrega)
$sqlTotConc = "SELECT COUNT(*) qt FROM entregas $whereSql AND estado='Concluído' AND data_entrega IS NOT NULL";
$resTotConc = runQuery($conn, $sqlTotConc, $types, $params);
$totConc = (int)$resTotConc->fetch_assoc()['qt'];

$sqlAtr = "SELECT COUNT(*) qt FROM entregas $whereSql AND estado='Concluído' AND data_entrega IS NOT NULL AND data_conclusao > data_entrega";
$resAtr = runQuery($conn, $sqlAtr, $types, $params);
$totAtr = (int)$resAtr->fetch_assoc()['qt'];

$perc_atrasadas = $totConc > 0 ? ($totAtr / $totConc) : 0.0;

// ---- resposta
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
