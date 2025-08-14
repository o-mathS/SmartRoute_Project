<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SmartRoute - Registro</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header></header>
    <div class="main">
        <div class="left-panel">
            <div class="logo-group">
                <img src="../assets/img/logo.png" alt="Smart Route Logo" class="logo">
            </div>
            <div class="login-title">Registrar</div>
            <?php
            session_start();
            $erro = '';
            $sucesso = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                include_once '../backend/conexao.php';
                $usuario = isset($_POST['registroUsuario']) ? trim($_POST['registroUsuario']) : '';
                $senha = isset($_POST['registroSenha']) ? $_POST['registroSenha'] : '';
                $senha2 = isset($_POST['registroSenha2']) ? $_POST['registroSenha2'] : '';
                if (empty($usuario) || empty($senha) || empty($senha2)) {
                    $erro = 'Preencha todos os campos!';
                } elseif ($senha !== $senha2) {
                    $erro = 'As senhas não coincidem!';
                } else {
                    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE usuario = ?');
                    $stmt->execute([$usuario]);
                    if ($stmt->fetch()) {
                        $erro = 'Usuário já existe!';
                    } else {
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
            ?>
            <form id="formRegistro" method="post" action="index.php">
                <input type="text" name="registroUsuario" id="registroUsuario" placeholder="Usuário" required>
                <input type="password" name="registroSenha" id="registroSenha" placeholder="Senha" required>
                <input type="password" name="registroSenha2" id="registroSenha2" placeholder="Confirme a senha" required>
                <button type="submit">Registrar</button>
                <div class="register-link">
                    Já tem conta? <a href="index.html">Entrar</a>
                </div>
                <?php if (!empty($erro)): ?>
                    <div style="color:red; margin-top:10px;"> <?= $erro ?> </div>
                <?php elseif ($sucesso): ?>
                    <div style="color:green; margin-top:10px;"> Usuário cadastrado com sucesso! <a href="index.html">Clique para entrar</a></div>
                <?php endif; ?>
            </form>
        </div>
        <div class="right-panel">
            <img src="../assets/img/truck2.webp" alt="Caminhão" class="truck-img">
        </div>
    </div>
    <footer></footer>
</body>
</html>
