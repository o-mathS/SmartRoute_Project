<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SmartRoute - Login</title>
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
            <div class="login-title">Log In</div>

            <?php
            session_start();
            $loginErro = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginUsuario'], $_POST['loginSenha'])) {
                include_once '../backend/conexao.php';
                $usuario = trim($_POST['loginUsuario']);
                $senha = $_POST['loginSenha'];
                $stmt = $conn->prepare('SELECT id, senha FROM usuarios WHERE usuario = ?');
                $stmt->execute([$usuario]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($senha, $user['senha'])) {
                    $_SESSION['usuario_id'] = $user['id'];
                    header('Location: entregas.html');
                    exit;
                } else {
                    $loginErro = 'Usuário ou senha inválidos!';
                }
            }
            ?>
            <form id="formLogin" method="post" action="entregas.php">
                <input type="text" name="loginUsuario" id="loginUsuario" placeholder="Usuário" required>
                <input type="password" name="loginSenha" id="loginSenha" placeholder="Senha" required>
                <button type="submit">Entrar</button>
                <?php if (!empty($loginErro)): ?>
                    <div style="color:red; margin-top:10px;"> <?= $loginErro ?> </div>
                <?php endif; ?>
            </form>
           
        </div>
        <div class="right-panel">
            <img src="../assets/img/truck2.webp  " alt="Caminhão" class="truck-img">
        </div>
    </div>
    <footer></footer>
</body>
</html>