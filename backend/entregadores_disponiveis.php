<?php
require_once '../backend/conexao.php';
$data = $_GET['data'] ?? date('Y-m-d');

$sql = "SELECT id, nome FROM entregadores WHERE id NOT IN (
          SELECT entregador_id FROM entregas WHERE DATE(data_entrega)=? AND estado != 'Cancelada'
        ) ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $data);
$stmt->execute();
$res = $stmt->get_result();
$entregadores = [];
while($row = $res->fetch_assoc()) {
    $entregadores[] = $row;
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($entregadores);
