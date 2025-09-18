<?php
session_start();
include 'conexao.php';
header('Content-Type: application/json');
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $stmt = $conn->prepare('SELECT * FROM entregas WHERE usuario_id = ?');
    $stmt->execute([$usuario_id]);
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($entregas);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nome = $data['nome'] ?? '';
    $endereco = $data['endereco'] ?? '';
    $lat = $data['lat'] ?? '';
    $lng = $data['lng'] ?? '';
    $estado = $data['estado'] ?? 'Em andamento';
    $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?)');
    $ok = $stmt->execute([$nome, $endereco, $lat, $lng, $estado, $usuario_id]);
    echo json_encode(['success' => $ok]);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $nome = $data['nome'] ?? '';
    $endereco = $data['endereco'] ?? '';
    $lat = $data['lat'] ?? '';
    $lng = $data['lng'] ?? '';
    $estado = $data['estado'] ?? 'Em andamento';
    $stmt = $conn->prepare('UPDATE entregas SET nome=?, endereco=?, lat=?, lng=?, estado=? WHERE id=? AND usuario_id=?');
    $ok = $stmt->execute([$nome, $endereco, $lat, $lng, $estado, $id, $usuario_id]);
    echo json_encode(['success' => $ok]);
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $stmt = $conn->prepare('DELETE FROM entregas WHERE id=? AND usuario_id=?');
    $ok = $stmt->execute([$id, $usuario_id]);
    echo json_encode(['success' => $ok]);
}
