<?php
session_start();
require_once '../backend/conexao.php';

// --- Verifica se está logado ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// --- Pega a role do usuário ---
$usuarioRole = $_SESSION['usuario_role'] ?? 'user';

// --- Atualiza status de entregas automaticamente ---
$conn->query("
    UPDATE entregas 
    SET estado = 'Em andamento' 
    WHERE estado = 'Agendada' 
    AND DATE(data_entrega) <= CURDATE()
");

// --- Variáveis de controle ---
$erro = '';
$sucesso = false;

// --- Registrar novo entregador (apenas admin) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_entregador']) && $usuarioRole === 'admin') {
    $nome = trim($_POST['nome_entregador']);
    $email = trim($_POST['email_entregador']);
    $telefone = trim($_POST['telefone_entregador']);

    if ($nome) {
        $stmt = $conn->prepare("INSERT INTO entregadores (nome, email, telefone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $email, $telefone);
        if (!$stmt->execute()) {
            $erro = "Erro ao salvar entregador: " . $conn->error;
        } else {
            $sucesso = true;
        }
        $stmt->close();
    }
}

// --- Inserção nova entrega ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['lat'], $_POST['lng']) && !isset($_POST['concluir_id']) && !isset($_POST['cancelar_id'])) {
    $nome = trim($_POST['nome']);
    $endereco = trim($_POST['endereco']);
    $lat = trim($_POST['lat']);
    $lng = trim($_POST['lng']);
    $dataEntrega = trim($_POST['data_entrega']);
    $entregador_id = isset($_POST['entregador_id']) ? intval($_POST['entregador_id']) : null;

    if (!$nome || !$endereco || !$lat || !$lng || !$dataEntrega || !$entregador_id) {
        $erro = 'Preencha todos os campos e selecione um entregador!';
    } else {
        $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng, estado, data_entrega, entregador_id) VALUES (?, ?, ?, ?, "Agendada", ?, ?)');
        $stmt->bind_param('sssssi', $nome, $endereco, $lat, $lng, $dataEntrega, $entregador_id);
        if ($stmt->execute()) {
            $sucesso = true;
            header("Location: entregas.php");
            exit;
        } else {
            $erro = 'Erro ao salvar entrega: ' . $conn->error;
        }
    }
}

// --- Concluir entrega ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_id'])) {
    $idConcluir = intval($_POST['concluir_id']);
    $stmt = $conn->prepare("UPDATE entregas SET estado='Concluído', data_entrega=NOW() WHERE id=?");
    $stmt->bind_param("i", $idConcluir);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Cancelar entrega ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
    $idCancelar = intval($_POST['cancelar_id']);
    $stmt = $conn->prepare("UPDATE entregas SET estado='Cancelada' WHERE id=?");
    $stmt->bind_param("i", $idCancelar);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Status selecionado ---
$status = isset($_GET['status']) ? $_GET['status'] : 'Agendada';
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

<form method="post" action="logout.php" style="margin-top: 20px;">
    <button type="submit" style="position: fixed; top: 860px; left: 80px; padding: 10px 20px; background-color: #d11a1a; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Sair</button>
</form>

<div class="main-content">
    <button class="add-btn" onclick="abrirFormulario()">+</button>

    <?php if ($usuarioRole === 'admin'): ?>
    <button class="add-btn" style="background-color: #007bff; font-size: 20px;" onclick="abrirModalEntregador()">👤</button>
    <?php endif; ?>

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
<div id="formularioModal">
    <h3>Nova Entrega</h3>
    <form method="post" action="entregas.php">
        <input type="text" name="nome" required placeholder="Nome" style="margin:5px 0;width:90%;border-radius:5px;padding:6px">
        <input type="text" name="endereco" required placeholder="Endereço" style="margin:5px 0;width:90%;border-radius:5px;padding:6px">
        <input type="text" name="lat" required placeholder="Latitude" style="margin:5px 0;width:90%;border-radius:5px;padding:6px">
        <input type="text" name="lng" required placeholder="Longitude" style="margin:5px 0;width:90%;border-radius:5px;padding:6px">
        
        <p>Entregador:</p>
        <select id="entregador_id" name="entregador_id" required style="margin:5px 0;width:100%;padding:6px;border-radius:5px;">
            <option value="">Selecione...</option>
            <?php
            $res = $conn->query("SELECT id, nome FROM entregadores ORDER BY nome");
            while ($e = $res->fetch_assoc()) {
                echo "<option value='{$e['id']}'>" . htmlspecialchars($e['nome']) . "</option>";
            }
            ?>
        </select>

        <input type="hidden" id="dataEntrega" name="data_entrega">
        <div id="calendar" style="max-width:100%; margin:10px 0;"></div>
        <p id="dataSelecionada" style="font-weight:bold;color:#1a7a1a;"></p>

        <button type="submit" style="margin-top:10px;background-color:#1a7a1a;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-weight:bold;">Salvar</button>
        <button type="button" onclick="document.getElementById('formularioModal').style.display='none'" style="background-color:#d11a1a;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-weight:bold;">Cancelar</button>

        <?php if (!empty($erro)) echo "<div style='color:red;margin-top:5px;'>$erro</div>"; ?>
        <?php if ($sucesso) echo "<div style='color:green;margin-top:5px;'>Operação realizada com sucesso!</div>"; ?>
    </form>
</div>

<!-- Modal Novo Entregador (apenas admin) -->
<?php if ($usuarioRole === 'admin'): ?>
<div id="modalEntregador" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:white; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.3); z-index:999;">
    <h3>Novo Entregador</h3>
    <form method="post" action="entregas.php">
        <input type="hidden" name="novo_entregador" value="1">
        <input type="text" name="nome_entregador" placeholder="Nome" required style="margin:5px 0;width:90%;padding:6px;border-radius:5px;">
        <input type="email" name="email_entregador" placeholder="Email" style="margin:5px 0;width:90%;padding:6px;border-radius:5px;">
        <input type="text" name="telefone_entregador" id="telefone_entregador" placeholder="Telefone celular" required
               style="margin:5px 0;width:90%;padding:6px;border-radius:5px;">
        <button type="submit" style="margin-top:10px;background-color:#1a7a1a;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-weight:bold;">Salvar</button>
        <button type="button" onclick="document.getElementById('modalEntregador').style.display='none'" style="background-color:#d11a1a;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-weight:bold;">Cancelar</button>
    </form>
</div>

<script>
const telefoneInput = document.getElementById('telefone_entregador');

telefoneInput.addEventListener('input', function (e) {
    let x = e.target.value.replace(/\D/g, '').slice(0, 11); // só números, máximo 11 dígitos
    let formatted = '';

    if (x.length > 0) formatted += '(' + x.slice(0, 2) + ')';
    if (x.length >= 7) formatted += ' ' + x.slice(2, 7) + '-' + x.slice(7);
    else if (x.length > 2) formatted += ' ' + x.slice(2);

    e.target.value = formatted;
});
</script>
<?php endif; ?>


<script>
let calendar;

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

function abrirModalEntregador() {
    document.getElementById('modalEntregador').style.display = 'block';
}

function abrirRota(entrega) {
    const url = `rotas.html?lat=${encodeURIComponent(entrega.lat)}&lng=${encodeURIComponent(entrega.lng)}&endereco=${encodeURIComponent(entrega.endereco)}`;
    window.location.href = url;
}
</script>
</body>
</html>
