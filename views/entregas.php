<?php
require_once '../backend/conexao.php';

// Processa inserção
$erro = '';
$sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['lat'], $_POST['lng'])) {
    $nome = trim($_POST['nome']);
    $endereco = trim($_POST['endereco']);
    $lat = trim($_POST['lat']);
    $lng = trim($_POST['lng']);

    if (!$nome || !$endereco || !$lat || !$lng) {
        $erro = 'Preencha todos os campos!';
    } else {
        $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $nome, $endereco, $lat, $lng);
        if ($stmt->execute()) {
            $sucesso = true;
            header("Location: entregas.php"); // reload para ver atualização
            exit;
        } else {
            $erro = 'Erro ao salvar entrega: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>CRUD de Entregas</title>
  <link href="https://fonts.googleapis.com/css2?family=Sofia+Sans:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../css/entregas.css" />
</head>
<body>
  <div class="top-bar"></div>
  <div class="side-bar">
    <img src="../assets/img/logo.png" class="logo" alt="Logo Smart Route" />
    <div class="monitoramento">
      Monitoramento de fretes em andamento:<br /><br />
      <span>Fretes abertos:</span> 
      <span id="fretesAbertos">
        <?php
        $countAbertos = $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Em andamento'")->fetch_assoc()['total'];
        echo $countAbertos;
        ?>
      </span><br />
      <span>Fretes concluídos:</span> 
      <span id="fretesConcluidos">
        <?php
        $countConcluidos = $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Concluído'")->fetch_assoc()['total'];
        echo $countConcluidos;
        ?>
      </span>
    </div>
  </div>
  <div class="main-content">
    <button class="add-btn" onclick="document.getElementById('formularioModal').style.display='block'">+</button>

    <h3 style="margin-top: 30px">Fretes em andamento</h3>
    <div class="grid" id="gridEntregas">
      <?php
      $result = $conn->query("SELECT * FROM entregas WHERE estado = 'Em andamento' ORDER BY id DESC");
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<div class='card'>";
              echo "<h3>" . htmlspecialchars($row['nome']) . "</h3>";
              echo "<p><strong>Endereço:</strong> " . htmlspecialchars($row['endereco']) . "</p>";
              echo "<p><strong>Status:</strong> " . htmlspecialchars($row['estado']) . "</p>";
              echo "</div>";
          }
      } else {
          echo "<p>Nenhuma entrega em andamento.</p>";
      }
      ?>
    </div>

    <h3 style="margin-top: 40px">Fretes concluídos</h3>
    <div class="grid" id="gridConcluidos">
      <?php
      $result = $conn->query("SELECT * FROM entregas WHERE estado = 'Concluído' ORDER BY id DESC");
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<div class='card card-status-done'>";
              echo "<h3>" . htmlspecialchars($row['nome']) . "</h3>";
              echo "<p><strong>Endereço:</strong> " . htmlspecialchars($row['endereco']) . "</p>";
              echo "<p><strong>Status:</strong> " . htmlspecialchars($row['estado']) . "</p>";
              echo "</div>";
          }
      } else {
          echo "<p>Nenhuma entrega concluída.</p>";
      }
      ?>
    </div>
  </div>

  <!-- Formulário modal -->
  <div id="formularioModal" style="display:none; position: fixed; top: 20%; left: 35%; background: #fff; border: 1px solid #ccc; padding: 20px;">
    <h3>Nova Entrega</h3>
    <form method="post">
      <label>Nome: <input type="text" name="nome" required></label><br>
      <label>Endereço: <input type="text" name="endereco" required></label><br>
      <label>Latitude: <input type="text" name="lat" required></label><br>
      <label>Longitude: <input type="text" name="lng" required></label><br>
      <button type="submit">Salvar</button>
      <button type="button" onclick="document.getElementById('formularioModal').style.display='none'">Cancelar</button>
    </form>
    <?php if (!empty($erro)) echo "<div style='color:red;'>$erro</div>"; ?>
    <?php if ($sucesso) echo "<div style='color:green;'>Entrega salva com sucesso!</div>"; ?>
  </div>
</body>
</html>
