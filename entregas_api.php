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
    $result = $conn->query("SELECT * FROM entregas WHERE usuario_id = $usuario_id");
    $entregas = [];
    while ($row = $result->fetch_assoc()) {
        $entregas[] = $row;
    }
    echo json_encode($entregas);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nome = $data['nome'] ?? '';
    $endereco = $data['endereco'] ?? '';
    $lat = $data['lat'] ?? '';
    $lng = $data['lng'] ?? '';
    $estado = $data['estado'] ?? 'Em andamento';
    $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssi', $nome, $endereco, $lat, $lng, $estado, $usuario_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $nome = $data['nome'] ?? '';
    $endereco = $data['endereco'] ?? '';
    $lat = $data['lat'] ?? '';
    $lng = $data['lng'] ?? '';
    $estado = $data['estado'] ?? 'Em andamento';
    $stmt = $conn->prepare('UPDATE entregas SET nome=?, endereco=?, lat=?, lng=?, estado=? WHERE id=? AND usuario_id=?');
    $stmt->bind_param('ssssiii', $nome, $endereco, $lat, $lng, $estado, $id, $usuario_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $stmt = $conn->prepare('DELETE FROM entregas WHERE id=? AND usuario_id=?');
    $stmt->bind_param('ii', $id, $usuario_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
