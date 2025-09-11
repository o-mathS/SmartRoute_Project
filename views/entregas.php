<?php
require_once '../backend/conexao.php';

// Processa inserção de nova entrega
$erro = '';
$sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['lat'], $_POST['lng']) && !isset($_POST['concluir_id']) && !isset($_POST['cancelar_id'])) {
  $nome = trim($_POST['nome']);
  $endereco = trim($_POST['endereco']);
  $lat = trim($_POST['lat']);
  $lng = trim($_POST['lng']);
  $dataEntrega = trim($_POST['data_entrega']);

  if (!$nome || !$endereco || !$lat || !$lng || !$dataEntrega) {
    $erro = 'Preencha todos os campos e selecione uma data!';
  } else {
    $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng, estado, data_entrega) VALUES (?, ?, ?, ?, "Agendada", ?)');
    $stmt->bind_param('sssss', $nome, $endereco, $lat, $lng, $_POST['data_entrega']);
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
  $stmt = $conn->prepare("UPDATE entregas SET estado='Concluído', data_entrega=NOW() WHERE id=?");
  $stmt->bind_param("i", $idConcluir);
  $stmt->execute();
  $stmt->close();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Cancelar entrega (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
  $idCancelar = intval($_POST['cancelar_id']);
  $stmt = $conn->prepare("UPDATE entregas SET estado='Cancelada' WHERE id=?");
  $stmt->bind_param("i", $idCancelar);
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
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
  <style>
    /* Estilo extra para canceladas */
    .card-cancelada {
      background-color: #ffe6e6;
      border-left: 6px solid #cc0000;
      opacity: 0.95;
    }

    .card-cancelada h3,
    .card-cancelada p {
      color: #a10000;
    }
    .card-andamento{
      background-color: #fffde6ff;
      border-left: 6px solid #ccbe00ff;
      opacity: 0.95;
    }
    .card-andamento h3,
    .card-andamento p {
      color: #a19100ff;
    }
    
  </style>
</head>

<body>
  <div class="top-bar"></div>
  <div class="side-bar">
    <img src="../assets/img/logo.png" class="logo" alt="Logo Smart Route" />
    <div class="monitoramento">
      <span>📦 Fretes Abertos:</span>
      <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado IN ('Agendada','Em andamento')")->fetch_assoc()['total'] ?></b><br>
      <span>✅ Concluídos:</span>
      <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Concluído'")->fetch_assoc()['total'] ?></b><br>
      <span>❌ Cancelados:</span>
      <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Cancelada'")->fetch_assoc()['total'] ?></b>
    </div>
    <nav class="left-mini-menu">
      <ul class="mini-menu-list">
        <li><a href="entregas.php" class="mini-menu-item active"><span class="mini-menu-icon">📦</span>Entregas</a></li>
        <li><a href="relatorios.php" class="mini-menu-item"><span class="mini-menu-icon">📊</span>Relatórios</a></li>
      </ul>
    </nav>
  </div>

  <!-- Botão de Logout -->
  <form method="post" action="logout.php" style=" margin-top: 20px;">
    <button type="submit" style="position: absolute;top: 860px;left: 80px;padding: 10px 20px;background-color: #d11a1a;color: white;border: none;border-radius: 5px;cursor: pointer;font-weight: bold;transition: background-color 0.3s;"
      onmouseover="this.style.backgroundColor='#b00';" onmouseout="this.style.backgroundColor='#d11a1a';">Sair</button>
  </form>

  <div class="main-content">
    <!-- Botão de adicionar -->
    <button class="add-btn" onclick="abrirFormulario()">+</button>
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
          <div class="card
            <?= $row['estado'] === 'Em andamento' ? 'card-andamento' : '' ?>
            <?= $row['estado'] === 'Concluído' ? ' card-concluida' : '' ?>
            <?= $row['estado'] === 'Cancelada' ? ' card-cancelada' : '' ?>">
            <h3><?= htmlspecialchars($row['nome']) ?></h3>
            <p><b>Endereço:</b> <?= htmlspecialchars($row['endereco']) ?></p>
            <p><b>Status:</b> <?= htmlspecialchars($row['estado']) ?></p>
            <?php if ($row['estado'] === 'Concluído'): ?>
              <p><b>Data de Conclusão:</b> <?= htmlspecialchars($row['data_entrega']) ?></p>
            <?php elseif ($row['estado'] === 'Cancelada'): ?>
              <p><b>Entrega Cancelada</b></p>
            <?php elseif (!empty($row['data_entrega'])): ?>
              <p><b>Data Agendada:</b> <?= htmlspecialchars($row['data_entrega']) ?></p>
            <?php endif; ?>
            <div class="card-actions">
              <?php if ($row['estado'] !== 'Concluído' && $row['estado'] !== 'Cancelada'): ?>
                <button class="rota-btn" onclick='abrirRota(<?= json_encode($row) ?>)'>Ver Rota</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Deseja cancelar esta entrega?')">
                  <input type="hidden" name="cancelar_id" value="<?= $row['id'] ?>" />
                  <button type="submit" class="remover-btn">Cancelar</button>
                </form>
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
      <input type="text" name="nome" required placeholder="Nome" style="margin:5px;width:100%;" title="Digite o nome do destinatário"><br>
      <input type="text" name="endereco" required placeholder="Endereço" style="margin:5px;width:100%;" title="Digite o endereço completo"><br>
      <input type="text" name="lat" required placeholder="Latitude" style="margin:5px;width:100%;" title="Digite a latitude do local de entrega"><br>
      <input type="text" name="lng" required placeholder="Longitude" style="margin:5px;width:100%;" title="Digite a longitude do local de entrega"><br>
      <input type="hidden" id="dataEntrega" name="data_entrega">
      <div id="calendar" style="max-width:100%; margin:10px 0;"></div>
      <p id="dataSelecionada" style="font-weight:bold; color:#1a7a1a;"></p>
      <button type="submit" style="margin-top:10px;background-color:#1a7a1a;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-weight:bold;">Salvar</button>
      <button type="button" onclick="document.getElementById('formularioModal').style.display='none'" style="background-color:#d11a1a;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-weight:bold;">Cancelar</button>
      <?php if (!empty($erro)) echo "<div style='color:red;'>$erro</div>"; ?>
      <?php if ($sucesso) echo "<div style='color:green;'>Entrega salva com sucesso!</div>"; ?>
    </form>
  </div>

  <script>
    let calendar; // global pra não recriar várias vezes

    function abrirFormulario() {
      let modal = document.getElementById('formularioModal');
      modal.style.display = 'block';

      if (!calendar) {
        let calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          locale: 'pt-br',
          selectable: true,
          dateClick: function(info) {
            document.getElementById('dataEntrega').value = info.dateStr;
            document.getElementById('dataSelecionada').textContent = "Entrega marcada para: " + info.dateStr;
          }
        });
        calendar.render();
      }
    }
  </script>

</body>

</html>