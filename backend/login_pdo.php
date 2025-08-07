<?php
session_start();
include 'conexao.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$usuario = $data['usuario'] ?? '';
$senha = $data['senha'] ?? '';
$stmt = $conn->prepare('SELECT id, senha FROM usuarios WHERE usuario = ?');
$stmt->execute([$usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify($senha, $user['senha'])) {
    $_SESSION['usuario_id'] = $user['id'];
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
