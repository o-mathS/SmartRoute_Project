<?php
session_start();
require_once '../backend/conexao.php';

// --- Verifica se está logado ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// --- Role do usuário ---
$usuarioRole = $_SESSION['usuario_role'] ?? 'user';

// --- Atualiza status automaticamente ---
$conn->query("
    UPDATE entregas 
    SET estado = 'Em andamento' 
    WHERE estado = 'Agendada' 
    AND DATE(data_entrega) <= CURDATE()
");

// --- Endpoint interno para geocodificação (OpenStreetMap) ---
if (isset($_GET['geocode_endereco'])) {
    $endereco = urlencode($_GET['geocode_endereco']);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=$endereco";
    $opts = [
        "http" => [
            "header" => "User-Agent: smartroute/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    header("Content-Type: application/json");
    echo $response;
    exit;
}

// --- Variáveis de controle ---
$erro = '';
$sucesso = false;

// --- Registrar novo entregador (admin) ---
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['lat'], $_POST['lng'], $_POST['data_entrega']) 
    && !isset($_POST['concluir_id']) && !isset($_POST['cancelar_id'])) {

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
<meta charset="UTF-8">
<title>SmartRoute - Entregas</title>
<link href="https://fonts.googleapis.com/css2?family=Sofia+Sans:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/entregas.css">
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet'>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
</head>
<body>

<div class="top-bar"></div>
<div class="side-bar">
    <img src="../assets/img/logo.png" class="logo" alt="Logo Smart Route">
    <div class="monitoramento">
        <span>📦 Fretes Abertos:</span>
        <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado IN ('Agendada','Em andamento')")->fetch_assoc()['total'] ?></b><br>
        <span>✅ Concluídos:</span>
        <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Concluído'")->fetch_assoc()['total'] ?></b><br>
        <span>❌ Cancelados:</span>
        <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Cancelada'")->fetch_assoc()['total'] ?></b>
    </div>
</div>

<div class="main-content">
    <button class="add-btn" onclick="abrirFormulario()">+</button>

    <?php if ($usuarioRole === 'admin'): ?>
        <button class="add-btn" style="background-color:#007bff;font-size:20px;" onclick="abrirModalEntregador()">👤</button>
    <?php endif; ?>

    <h2>Gerenciamento de Entregas</h2>
    <p>Adicione, visualize e gerencie suas entregas.</p>

    <div class="tabs">
        <a href="?status=Agendada" class="<?= $status == 'Agendada' ? 'active' : '' ?>">Agendadas</a>
        <a href="?status=Em andamento" class="<?= $status == 'Em andamento' ? 'active' : '' ?>">Em andamento</a>
        <a href="?status=Concluído" class="<?= $status == 'Concluído' ? 'active' : '' ?>">Concluídas</a>
        <a href="?status=Cancelada" class="<?= $status == 'Cancelada' ? 'active' : '' ?>">Canceladas</a>
    </div>

    <div class="grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card
                    <?= $row['estado'] === 'Em andamento' ? 'card-andamento':'' ?>
                    <?= $row['estado'] === 'Concluído' ? 'card-concluida':'' ?>
                    <?= $row['estado'] === 'Cancelada' ? 'card-cancelada':'' ?>">
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
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Nenhuma entrega <?= strtolower($status) ?>.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Entrega -->
<div id="formularioModal" style="display:none;">
    <h3>Nova Entrega</h3>
    <form method="post" action="entregas.php">
        <input type="text" name="nome" required placeholder="Nome" style="width:90%;margin:5px 0;padding:6px;border-radius:5px;">
        <input type="text" id="cep" name="cep" required placeholder="CEP" style="width:90%;margin:5px 0;padding:6px;border-radius:5px;">
        <input type="text" id="endereco" name="endereco" required placeholder="Endereço" readonly style="width:90%;margin:5px 0;padding:6px;border-radius:5px;">
        <input id="lat" name="lat" type="text" required placeholder="Latitude" readonly style="width:90%;margin:5px 0;padding:6px;border-radius:5px;background:#f5f5f5;">
        <input id="lng" name="lng" type="text" required placeholder="Longitude" readonly style="width:90%;margin:5px 0;padding:6px;border-radius:5px;background:#f5f5f5;">

        <p>Entregador:</p>
        <select id="entregador_id" name="entregador_id" required style="width:100%;padding:6px;border-radius:5px;">
            <option value="">Selecione...</option>
            <?php
            $res = $conn->query("SELECT id,nome FROM entregadores ORDER BY nome");
            while ($e=$res->fetch_assoc()) {
                echo "<option value='{$e['id']}'>" . htmlspecialchars($e['nome']) . "</option>";
            }
            ?>
        </select>

        <input type="hidden" id="dataEntrega" name="data_entrega">
        <div id="calendar" style="max-width:100%;margin:10px 0;"></div>
        <p id="dataSelecionada" style="font-weight:bold;color:#1a7a1a;"></p>

        <button type="submit" style="margin-top:10px;background-color:#1a7a1a;color:white;padding:5px 10px;border-radius:5px;">Salvar</button>
        <button type="button" onclick="document.getElementById('formularioModal').style.display='none'" style="background-color:#d11a1a;color:white;padding:5px 10px;border-radius:5px;">Cancelar</button>
        <?php if ($erro) echo "<div style='color:red;margin-top:5px;'>$erro</div>"; ?>
        <?php if ($sucesso) echo "<div style='color:green;margin-top:5px;'>Operação realizada com sucesso!</div>"; ?>
    </form>
</div>

<script>
document.getElementById('cep').addEventListener('blur', async function() {
    const cep = this.value.replace(/\D/g,'');
    if (cep.length === 8) {
        try {
            const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await resp.json();
            if (!data.erro) {
                const endereco = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}`;
                document.getElementById('endereco').value = endereco;

                // Geocodificação via backend
                const geoResp = await fetch(`entregas.php?geocode_endereco=${encodeURIComponent(endereco)}`);
                const geoData = await geoResp.json();
                if (geoData.length > 0) {
                    document.getElementById('lat').value = geoData[0].lat;
                    document.getElementById('lng').value = geoData[0].lon;
                }
            } else alert('CEP não encontrado!');
        } catch(err){ console.error(err); }
    }
});

let calendar;
function abrirFormulario(){
    document.getElementById('formularioModal').style.display='block';
    if(!calendar){
        calendar=new FullCalendar.Calendar(document.getElementById('calendar'),{
            initialView:'dayGridMonth',
            locale:'pt-br',
            selectable:true,
            dateClick:function(info){
                document.getElementById('dataEntrega').value=info.dateStr;
                document.getElementById('dataSelecionada').textContent="Entrega marcada para: "+info.dateStr;
            }
        });
        calendar.render();
    }
}

function abrirModalEntregador(){ document.getElementById('modalEntregador').style.display='block'; }
</script>
</body>
</html>
