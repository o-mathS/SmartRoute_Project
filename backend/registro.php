<?php
include 'conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$usuario = $data['usuario'] ?? '';
$senha = $data['senha'] ?? '';

if (!$usuario || !$senha) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos']);
    exit;
}

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO usuarios (usuario, senha) VALUES (?, ?)');
$stmt->bind_param('ss', $usuario, $senha_hash);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Usuário já existe']);
}
