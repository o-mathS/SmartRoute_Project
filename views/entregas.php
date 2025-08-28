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
      <ul class="mini-menu-list">
        <li>
          <a href="../views/relatorios.php" class="menu-button">
            <span class="mini-menu-icon">📊</span>
            <span class="mini-menu-text">Relatórios</span>
          </a>
        </li>
      </ul>

    </div>
    <!-- Botão de Logout -->
    <form method="post" action="logout.php" style=" margin-top: 20px;">
      <button type="submit" style="
        position: absolute;
        top: 860px;
        left: 80px;
        padding: 10px 20px;
        background-color: #d11a1a;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s;
      " onmouseover="this.style.backgroundColor='#b00';" onmouseout="this.style.backgroundColor='#d11a1a';
      " title="Sair do sistema
    ">Sair</button>
    </form>

  </div>
  <div class="main-content">
    <button class="add-btn" onclick="document.getElementById('formularioModal').style.display='block'">+</button>
    <h2>Gerenciamento de Entregas</h2>
    <p>Adicione, visualize e gerencie suas entregas de forma simples e rápida.</p>

    <h3 style="margin-top: 30px">Fretes em andamento</h3>
    <div class="grid" id="gridEntregas">
      <?php
      require_once '../backend/conexao.php';

      // Consulta entregas "Em andamento"
      $sql = "SELECT * FROM entregas WHERE estado = 'Em andamento' ORDER BY id DESC";
      $result = $conn->query($sql);

      // Verifica se encontrou resultados
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          // Dados da entrega para JSON
          $dadosEntrega = htmlspecialchars(json_encode([
            'id' => $row['id'],
            'nome' => $row['nome'],
            'lat' => $row['lat'],
            'lng' => $row['lng']
          ]), ENT_QUOTES, 'UTF-8');

          echo '<div class="card">';
          echo '<h3>' . htmlspecialchars($row['nome']) . '</h3>';
          echo '<p><strong>Endereço:</strong> ' . htmlspecialchars($row['endereco']) . '</p>';
          echo '<p><strong>Status:</strong> ' . htmlspecialchars($row['estado']) . '</p>';

          $dadosEntrega = htmlspecialchars(json_encode([
            'id' => $row['id'],
            'nome' => $row['nome'],
            'lat' => $row['lat'],
            'lng' => $row['lng']
          ]), ENT_QUOTES, 'UTF-8');

          echo '<div class="card-actions">';
          echo "<button class='rota-btn' onclick='abrirRota($dadosEntrega)'>Ver Rota</button>";
          echo "<button class='remover-btn' onclick='if(confirm(\"Deseja remover esta entrega?\")) { window.location.href=\"../backend/remover_entrega.php?id=" . $row['id'] . "\"; }'>Remover</button>";
          echo "<form method='post' style='display:inline; flex:1;'>
        <input type='hidden' name='concluir_id' value='" . $row['id'] . "' />
        <button type='submit' class='concluir-btn'>Concluir</button>
      </form>";
          echo '</div>';
          echo '</div>';
        }
      } else {
        echo "<p>Nenhuma entrega em andamento.</p>";
      }

      // Lógica para concluir entrega
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_id'])) {
        $idConcluir = intval($_POST['concluir_id']);
        $stmt = $conn->prepare("UPDATE entregas SET estado='Concluído' WHERE id=?");
        $stmt->bind_param("i", $idConcluir);
        $stmt->execute();
        $stmt->close();
        // Atualiza a página para refletir a alteração
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
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
  <div id="formularioModal" style="
  display: none;
  position: fixed;
  top: 20%;
  left: 35%;
  background: #fff;
  border: 1px solid #ccc;
  padding: 20px;
  z-index: 100;
  font-family:  Sofia Sans, Arial, sans-serif;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  width: 30%;
  min-width: 300px;
  ">
    <h3>Nova Entrega</h3>
    <form method="post" style="width: 80%;" action="entregas.php">
      <p>Preencha os dados da entrega:</p>
      <input type="text" name="nome" required placeholder="Nome" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite o nome do destinatário"></label><br>
      <input type="text" name="endereco" required placeholder="Endereço" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite o endereço completo"></label><br>
      <input type="text" name="lat" required placeholder="Latitude" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite a latitude do local de entrega"></label><br>
      <input type="text" name="lng" required placeholder="Longitude" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite a longitude do local de entrega"></label><br>
      <button type="submit" style="
      margin-top: 10px;
      background-color: #1a7a1a;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
      " onmouseover="this.style.backgroundColor='#155a15';" onmouseout="this.style.backgroundColor='#1a7a1a';" title="Salvar entrega
      ">Salvar</button>
      <button type="button" onclick="document.getElementById('formularioModal').style.display='none'" style="
      background-color: #d11a1a;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
      " onmouseover="this.style.backgroundColor='#b00';" onmouseout="this.style.backgroundColor='#d11a1a';" title="Cancelar
      ">Cancelar</button>
    </form>
    <?php if (!empty($erro)) echo "<div style='color:red;'>$erro</div>"; ?>
    <?php if ($sucesso) echo "<div style='color:green;'>Entrega salva com sucesso!</div>"; ?>
  </div>
  <script>
    function abrirRota(entrega) {
      localStorage.setItem('entregaSelecionada', JSON.stringify(entrega));
      window.location.href = 'rotas.html';
    }
  </script>
</body>

</html>