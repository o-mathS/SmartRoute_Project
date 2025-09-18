<?php
require_once '../backend/conexao.php';
$entregador_id = intval($_GET['entregador_id']);
$data = $_GET['data'] ?? '';
$disponivel = true;

if ($entregador_id && $data) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM entregas WHERE entregador_id=? AND DATE(data_entrega)=DATE(?) AND estado!='Cancelada'");
    $stmt->bind_param("is", $entregador_id, $data);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $disponivel = $total == 0;
}

echo json_encode(['disponivel'=>$disponivel]);
