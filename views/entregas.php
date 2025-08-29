<?php
require_once '../backend/conexao.php';

// Processa inserção de nova entrega
$erro = '';
$sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['lat'], $_POST['lng']) && !isset($_POST['concluir_id'])) {
  $nome = trim($_POST['nome']);
  $endereco = trim($_POST['endereco']);
  $lat = trim($_POST['lat']);
  $lng = trim($_POST['lng']);

  if (!$nome || !$endereco || !$lat || !$lng) {
    $erro = 'Preencha todos os campos!';
  } else {
    $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng, estado) VALUES (?, ?, ?, ?, "Agendada")');
    $stmt->bind_param('ssss', $nome, $endereco, $lat, $lng);
    if ($stmt->execute()) {
      $sucesso = true;
      header("Location: entregas.php");
      exit;
    } else {
      $erro = 'Erro ao salvar entrega: ' . $conn->error;
    }
  }
}

// Concluir entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_id'])) {
  $idConcluir = intval($_POST['concluir_id']);
  $stmt = $conn->prepare("UPDATE entregas SET estado='Concluído', data_conclusao=NOW() WHERE id=?");
  $stmt->bind_param("i", $idConcluir);
  $stmt->execute();
  $stmt->close();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Status selecionado na aba
$status = isset($_GET['status']) ? $_GET['status'] : 'Agendada';

// Consulta entregas por status
$stmt = $conn->prepare("SELECT * FROM entregas WHERE estado = ? ORDER BY id DESC");
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <title>SmartRoute - Entregas</title>
  <link href="https://fonts.googleapis.com/css2?family=Sofia+Sans:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../css/entregas.css" />
</head>

<body>
  <div class="top-bar"></div>
  <div class="side-bar">
    <img src="../assets/img/logo.png" class="logo" alt="Logo Smart Route" />
    <div class="monitoramento">
      <span>📦 Fretes Abertos:</span>
      <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado IN ('Agendada','Em andamento')")->fetch_assoc()['total'] ?></b><br>
      <span>✅ Concluídos:</span>
      <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Concluído'")->fetch_assoc()['total'] ?></b>
    </div>
    <nav class="left-mini-menu">
      <ul class="mini-menu-list">
        <li><a href="relatorios.php" class="mini-menu-item"><span class="mini-menu-icon">📊</span>Relatórios</a></li>
      </ul>
    </nav>
  </div>

  <div class="main-content">
    <button class="add-btn" onclick="document.getElementById('formularioModal').style.display='block'">+</button>
    <h2>Gerenciamento de Entregas</h2>
    <p>Adicione, visualize e gerencie suas entregas.</p>

    <!-- Abas -->
    <div class="tabs">
      <a href="?status=Agendada" class="<?= $status == 'Agendada' ? 'active' : '' ?>">Agendadas</a>
      <a href="?status=Em andamento" class="<?= $status == 'Em andamento' ? 'active' : '' ?>">Em andamento</a>
      <a href="?status=Concluído" class="<?= $status == 'Concluído' ? 'active' : '' ?>">Concluídas</a>
      <a href="?status=Cancelada" class="<?= $status == 'Cancelada' ? 'active' : '' ?>">Canceladas</a>
    </div>

    <!-- Lista de Entregas -->
    <div class="grid">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="card<?= $row['estado'] === 'Concluído' ? ' card-concluida' : '' ?>">
            <h3><?= htmlspecialchars($row['nome']) ?></h3>
            <p><b>Endereço:</b> <?= htmlspecialchars($row['endereco']) ?></p>
            <p><b>Status:</b> <?= htmlspecialchars($row['estado']) ?></p>
            <?php if ($row['estado'] === 'Concluído'): ?>
              <p><b>Data de Conclusão:</b> <?= htmlspecialchars($row['data_conclusao']) ?></p>
            <?php endif; ?>
            <div class="card-actions">
              <?php if ($row['estado'] !== 'Concluído'): ?>
                <button class="rota-btn" onclick='abrirRota(<?= json_encode($row) ?>)'>Ver Rota</button>
                <button class="remover-btn" onclick='if(confirm("Deseja remover?")) window.location.href="../backend/remover_entrega.php?id=<?= $row['id'] ?>"'>Remover</button>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="concluir_id" value="<?= $row['id'] ?>" />
                  <button type="submit" class="concluir-btn">Concluir</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>Nenhuma entrega <?= strtolower($status) ?>.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Nova Entrega -->
  <div id="formularioModal" style="display:none;position:fixed;top:20%;left:35%;background:#fff;padding:20px;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,0.1);width:30%;min-width:300px;">
    <h3>Nova Entrega</h3>
    <form method="post" style="width: 80%;" action="entregas.php">
      <p>Preencha os dados da entrega:</p>
      <input type="text" name="nome" required placeholder="Nome" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite o nome do destinatário"><br>
      <input type="text" name="endereco" required placeholder="Endereço" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite o endereço completo"><br>
      <input type="text" name="lat" required placeholder="Latitude" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite a latitude do local de entrega"><br>
      <input type="text" name="lng" required placeholder="Longitude" style="border: 1px solid #ccc; padding: 4px; margin: 5px; border-radius: 4px;width: 100%;box-sizing: border-box;" title="Digite a longitude do local de entrega"><br>
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