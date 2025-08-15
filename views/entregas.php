<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <title>CRUD de Entregas</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Sofia+Sans:wght@400;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../css/entregas.css" />
    <style></style>
  </head>
  <body>
    <div class="top-bar"></div>
    <div class="side-bar">
      <img src="../assets/img/logo.png" class="logo" alt="Logo Smart Route" />
      <div class="monitoramento">
        Monitoramento de fretes em andamento:<br /><br />
        <span>Fretes abertos:</span> <span id="fretesAbertos"><?php echo isset($abertos) ? $abertos : 0; ?></span><br />
<span>Fretes concluídos:</span> <span id="fretesConcluidos"><?php echo isset($concluidos) ? $concluidos : 0; ?></span>
      </div>
    </div>
    <div class="main-content">
      <button class="add-btn" onclick="abrirFormulario()">+</button>
      <input
        type="text"
        id="busca"
        placeholder="Buscar..."
        oninput="filtrarEntregas()"
        style="margin-bottom: 20px; width: 220px; padding: 6px"
      />
      <h3 style="margin-top: 30px">Fretes em andamento</h3>
      <!-- Fretes em andamento -->
      <div class="grid" id="gridEntregas">
        <?php
        include_once '../backend/conexao.php';
        $stmt = $conn->query('SELECT * FROM entregas ORDER BY id DESC');
        $abertos = 0;
        while ($entrega = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $estado = isset($entrega['estado']) && $entrega['estado'] === 'Concluído' ? 'Concluído' : 'Em andamento';
          if ($estado === 'Em andamento') {
            $abertos++;
            echo '<div class="card">';
            echo '<div class="card-header">Frete #' . $entrega['id'] . '</div>';
            echo '<div>Endereço: ' . htmlspecialchars($entrega['endereco']) . '</div>';
            echo '<div class="' . ($estado === 'Concluído' ? 'card-status-done' : 'card-status') . '">' . $estado . '</div>';
            echo '</div>';
          }
        }
        ?>
      </div>

      <h3 style="margin-top: 40px">Fretes concluídos</h3>
      <!-- Fretes concluídos -->
      <div class="grid" id="gridConcluidos">
        <?php
        $stmt = $conn->query('SELECT * FROM entregas ORDER BY id DESC');
        $concluidos = 0;
        while ($entrega = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $estado = isset($entrega['estado']) && $entrega['estado'] === 'Concluído' ? 'Concluído' : 'Em andamento';
          if ($estado === 'Concluído') {
            $concluidos++;
            echo '<div class="card card-status-done">';
            echo '<div class="card-header">Frete #' . $entrega['id'] . '</div>';
            echo '<div>Endereço: ' . htmlspecialchars($entrega['endereco']) . '</div>';
            echo '<div class="card-status-done">Concluído</div>';
            echo '</div>';
          }
        }
        ?>
      </div>
    </div>

    <!-- Formulário modal para criar/editar entrega -->
    <div
      id="formularioModal"
      style="
        display: none;
        position: fixed;
        top: 20%;
        left: 35%;
        background: #fff;
        border: 1px solid #ccc;
        padding: 20px;
      "
    >
      <h3 id="tituloFormulario">Nova Entrega</h3>
      <?php
      $erro = '';
      $sucesso = false;
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['endereco'], $_POST['lat'], $_POST['lng'])) {
        include_once '../backend/conexao.php';
        $nome = trim($_POST['nome']);
        $endereco = trim($_POST['endereco']);
        $lat = trim($_POST['lat']);
        $lng = trim($_POST['lng']);
        if (!$nome || !$endereco || !$lat || !$lng) {
          $erro = 'Preencha todos os campos!';
        } else {
          $stmt = $conn->prepare('INSERT INTO entregas (nome, endereco, lat, lng) VALUES (?, ?, ?, ?)');
          if ($stmt->execute([$nome, $endereco, $lat, $lng])) {
            $sucesso = true;
          } else {
            $erro = 'Erro ao salvar entrega.';
          }
        }
      }
      ?>z
      <form id="formEntrega" method="post" action="">
        <label>Nome: <input type="text" name="nome" id="nome" required /></label><br />
        <label>Ponto de Partida: <input type="text" name="endereco" id="endereco" required /></label><br />
        <label>Latitude: <input type="text" name="lat" id="lat" required /></label><br />
        <label>Longitude: <input type="text" name="lng" id="lng" required /></label><br />
        <button type="submit">Salvar</button>
        <button type="button" onclick="fecharFormulario()">Cancelar</button>
        <?php if (!empty($erro)): ?>
          <div style="color:red; margin-top:10px;"> <?= $erro ?> </div>
        <?php elseif ($sucesso): ?>
          <div style="color:green; margin-top:10px;"> Entrega salva com sucesso! </div>
        <?php endif; ?>
      </form>
    </div>

    <script>
      let entregas = [];

      function abrirFormulario(indice = null) {
        document.getElementById("formularioModal").style.display = "block";
        document.getElementById("formEntrega").reset();
        document.getElementById("indiceEdicao").value = "";
        document.getElementById("tituloFormulario").innerText = "Nova Entrega";
        if (indice !== null) {
          let entrega = entregas[indice];
          document.getElementById("nome").value = entrega.nome;
          document.getElementById("endereco").value = entrega.endereco;
          document.getElementById("lat").value = entrega.lat;
          document.getElementById("lng").value = entrega.lng;
          document.getElementById("indiceEdicao").value = indice;
          document.getElementById("tituloFormulario").innerText =
            "Editar Entrega";
        }
      }

      function fecharFormulario() {
        document.getElementById("formularioModal").style.display = "none";
      }

  // Função salvarEntrega removida: agora o formulário salva via PHP

      function editarEntrega(indice) {
        abrirFormulario(indice);
      }

      function excluirEntrega(indice) {
        if (confirm("Deseja excluir esta entrega?")) {
          entregas.splice(indice, 1);
          renderizarTabela();
        }
      }

      function renderizarTabela() {
        let gridAbertos = document.getElementById("gridEntregas");
        let gridConcluidos = document.getElementById("gridConcluidos");
        gridAbertos.innerHTML = "";
        gridConcluidos.innerHTML = "";
        let busca = document.getElementById("busca").value.toLowerCase();
        let abertos = 0;
        let concluidos = 0;
        entregas.forEach((entrega, i) => {
          if (
            entrega.nome.toLowerCase().includes(busca) ||
            entrega.endereco.toLowerCase().includes(busca)
          ) {
            let estado = entrega.estado || "Em andamento";
            let card = document.createElement("div");
            card.className =
              "card" + (estado === "Concluído" ? " card-status-done" : "");
            card.innerHTML = `
                        <div class="card-header">Frete #${i + 1}</div>
                        <div>Endereço: ${entrega.endereco}</div>
                        <div class="${
                          estado === "Concluído"
                            ? "card-status-done"
                            : "card-status"
                        }">${estado}</div>
                        <div class="card-actions">
                            <button onclick="editarEntrega(${i}); event.stopPropagation();">Editar</button>
                            <button class="cancel" onclick="excluirEntrega(${i}); event.stopPropagation();">Cancelar</button>
                            ${
                              estado !== "Concluído"
                                ? `<button class="concluir" onclick="concluirEntrega(${i}); event.stopPropagation();">Concluir</button>`
                                : ""
                            }
                        </div>
                    `;
            card.onclick = function (e) {
              if (e.target.tagName === "BUTTON") return;
              localStorage.setItem(
                "entregaSelecionada",
                JSON.stringify(entrega)
              );
              window.location.href = "../views/rotas.html";
            };
            if (estado === "Concluído") {
              concluidos++;
              gridConcluidos.appendChild(card);
            } else {
              abertos++;
              gridAbertos.appendChild(card);
            }
          }
        });
        document.getElementById("fretesAbertos").innerText = abertos;
        document.getElementById("fretesConcluidos").innerText = concluidos;
        // Preenche grid com cards vazios para manter layout
        let totalCardsAbertos = gridAbertos.children.length;
        for (let j = totalCardsAbertos; j < 12; j++) {
          let vazio = document.createElement("div");
          vazio.className = "card";
          gridAbertos.appendChild(vazio);
        }
        let totalCardsConcluidos = gridConcluidos.children.length;
        for (let j = totalCardsConcluidos; j < 4; j++) {
          let vazio = document.createElement("div");
          vazio.className = "card card-status-done";
          gridConcluidos.appendChild(vazio);
        }
      }

      function filtrarEntregas() {
        renderizarTabela();
      }

      async function concluirEntrega(indice) {
        let entrega = entregas[indice];
        // Se estiver usando API, atualiza no backend
        if (entrega.id) {
          await fetch("entregas_api.php", {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id: entrega.id,
              nome: entrega.nome,
              endereco: entrega.endereco,
              lat: entrega.lat,
              lng: entrega.lng,
              estado: "Concluído",
            }),
          });
        }
        entrega.estado = "Concluído";
        renderizarTabela();
      }

      // Inicializa tabela vazia
      renderizarTabela();
    </script>
  </body>
</html>
