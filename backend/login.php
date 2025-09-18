<?php
session_start();
include 'conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$usuario = $data['usuario'] ?? '';
$senha = $data['senha'] ?? '';

$stmt = $conn->prepare('SELECT id, senha FROM usuarios WHERE usuario = ?');
$stmt->bind_param('s', $usuario);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $senha_hash);
    $stmt->fetch();
    if (password_verify($senha, $senha_hash)) {
        $_SESSION['usuario_id'] = $id;
        echo json_encode(['success' => true]);
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
