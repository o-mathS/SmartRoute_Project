<?php
include 'conexao.php';
session_start();
$erro = '';
$sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['registroUsuario']) ? trim($_POST['registroUsuario']) : '';
    $senha = isset($_POST['registroSenha']) ? $_POST['registroSenha'] : '';
    $senha2 = isset($_POST['registroSenha2']) ? $_POST['registroSenha2'] : '';

    if (empty($usuario) || empty($senha) || empty($senha2)) {
        $erro = 'Preencha todos os campos!';
    } elseif ($senha !== $senha2) {
        $erro = 'As senhas não coincidem!';
    } else {
        // Verifica se o usuário já existe
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);
        if ($stmt->fetch()) {
            $erro = 'Usuário já existe!';
        } else {
            // Insere novo usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO usuarios (usuario, senha) VALUES (?, ?)');
            if ($stmt->execute([$usuario, $senha_hash])) {
                $sucesso = true;
            } else {
                $erro = 'Erro ao registrar usuário.';
            }
        }
    }
}
