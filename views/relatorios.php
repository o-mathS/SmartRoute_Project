<?php
require_once '../backend/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>SmartRoute - Relatórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Sofia+Sans:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../css/entregas.css" />
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    .filters { display:flex; gap:10px; flex-wrap:wrap; margin: 10px 0 20px; }
    .filters .field { display:flex; flex-direction:column; font-size:14px; }
    .filters input, .filters select { padding:6px 8px; border:1px solid #ccc; border-radius:6px; min-width:160px; }
    .kpis { display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin: 10px 0 24px; }
    .kpi-card { background:#fff; border:1px solid #e9e9e9; border-radius:10px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,.04); }
    .kpi-title { font-size:12px; color:#666; margin-bottom:6px; }
    .kpi-value { font-size:22px; font-weight:700; }
    .charts { display:grid; grid-template-columns: 1fr 1fr; gap:20px; }
    .chart-box { background:#fff; border:1px solid #e9e9e9; border-radius:10px; padding:12px; }
    @media (max-width: 1100px) { .charts { grid-template-columns: 1fr; } .kpis { grid-template-columns: repeat(2, 1fr); } }
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
      <b><?= $conn->query("SELECT COUNT(*) AS total FROM entregas WHERE estado = 'Concluído'")->fetch_assoc()['total'] ?></b>
    </div>
    <nav class="left-mini-menu">
      <ul class="mini-menu-list">
        <li><a href="entregas.php" class="mini-menu-item"><span class="mini-menu-icon">📦</span>Entregas</a></li>
        <li><a href="relatorios.php" class="mini-menu-item active"><span class="mini-menu-icon">📊</span>Relatórios</a></li>
      </ul>
    </nav>
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
  
  <div class="main-content">
    <h2>Dashboard – Relatórios e Gráficos</h2>
    <p>Filtros e visualizações em tempo real dos seus dados de entregas.</p>

    <!-- Filtros -->
    <div class="filters">
      <div class="field">
        <label>Início</label>
        <input type="date" id="startDate">
      </div>
      <div class="field">
        <label>Fim</label>
        <input type="date" id="endDate">
      </div>
      <div class="field">
        <label>Entregador</label>
        <select id="entregadorFilter">
          <option value="">Todos</option>
        </select>
      </div>
      <div class="field">
        <label>Status</label>
        <select id="statusFilter">
          <option value="">Todos</option>
          <option value="Agendada">Agendada</option>
          <option value="Em andamento">Em andamento</option>
          <option value="Concluído">Concluído</option>
          <option value="Cancelada">Cancelada</option>
        </select>
      </div>
      <div class="field" style="align-self:flex-end">
        <button id="btnApply" class="menu-button">Aplicar</button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpis">
      <div class="kpi-card">
        <div class="kpi-title">Entregas concluídas hoje</div>
        <div class="kpi-value" id="kpiHoje">—</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-title">Média de atraso (dias)</div>
        <div class="kpi-value" id="kpiAtrasoMedio">—</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-title">Total de cancelamentos</div>
        <div class="kpi-value" id="kpiCancelamentos">—</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-title">% entregas atrasadas</div>
        <div class="kpi-value" id="kpiPercAtrasadas">—</div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="charts">
      <div class="chart-box">
        <h4>Distribuição por Status</h4>
        <canvas id="chartStatus"></canvas>
      </div>
      <div class="chart-box">
        <h4>Concluídas ao longo do tempo</h4>
        <canvas id="chartLinha"></canvas>
      </div>
      <div class="chart-box" style="grid-column: 1 / -1;">
        <h4>Entregas por Entregador</h4>
        <canvas id="chartEntregador"></canvas>
      </div>
    </div>
  </div>

  <script>
    // --- helpers
    const $ = (sel) => document.querySelector(sel);

    let chartStatus, chartLinha, chartEntregador;
    const ctxStatus = document.getElementById('chartStatus').getContext('2d');
    const ctxLinha  = document.getElementById('chartLinha').getContext('2d');
    const ctxEnt    = document.getElementById('chartEntregador').getContext('2d');

    function initCharts() {
      chartStatus = new Chart(ctxStatus, {
        type: 'pie',
        data: { labels: [], datasets: [{ data: [] }] },
        options: { responsive: true }
      });

      chartLinha = new Chart(ctxLinha, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Concluídas', data: [] }] },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } }
        }
      });

      chartEntregador = new Chart(ctxEnt, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Entregas', data: [] }] },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } }
        }
      });
    }

    function applyToCharts(data) {
      // Pizza (status)
      chartStatus.data.labels = ['Agendada','Em andamento','Concluído','Cancelada'];
      chartStatus.data.datasets[0].data = [
        data.status_counts.Agendada || 0,
        data.status_counts['Em andamento'] || 0,
        data.status_counts['Concluído'] || 0,
        data.status_counts['Cancelada'] || 0
      ];
      chartStatus.update();

      // Linha (concluídas por dia)
      chartLinha.data.labels = data.concluidas_por_dia.labels;
      chartLinha.data.datasets[0].data = data.concluidas_por_dia.values;
      chartLinha.update();

      // Barras (por entregador)
      const nomes = Object.keys(data.por_entregador || {});
      const vals  = nomes.map(n => data.por_entregador[n]);
      chartEntregador.data.labels = nomes;
      chartEntregador.data.datasets[0].data = vals;
      chartEntregador.update();

      // KPIs
      document.getElementById('kpiHoje').textContent = data.kpis.concluidas_hoje ?? 0;
      document.getElementById('kpiAtrasoMedio').textContent = (data.kpis.media_atraso_dias ?? 0).toFixed(2);
      document.getElementById('kpiCancelamentos').textContent = data.kpis.cancelamentos ?? 0;
      document.getElementById('kpiPercAtrasadas').textContent = ((data.kpis.perc_atrasadas ?? 0) * 100).toFixed(1) + '%';

      // Popular select de entregador se estiver vazio (primeira carga)
      const sel = document.getElementById('entregadorFilter');
      if (sel.options.length <= 1) {
        nomes.forEach(n => {
          const opt = document.createElement('option');
          opt.value = n;
          opt.textContent = n;
          sel.appendChild(opt);
        });
      }
    }

    async function fetchData() {
      const params = new URLSearchParams();
      const s = document.getElementById('startDate').value;
      const e = document.getElementById('endDate').value;
      const entregador = document.getElementById('entregadorFilter').value;
      const status = document.getElementById('statusFilter').value;

      if (s) params.append('start_date', s);
      if (e) params.append('end_date', e);
      if (entregador) params.append('entregador', entregador);
      if (status) params.append('status', status);

      const res = await fetch('dashboard_data.php?' + params.toString(), { cache: 'no-store' });
      const data = await res.json();
      applyToCharts(data);
    }

    document.getElementById('btnApply').addEventListener('click', fetchData);

    // polling a cada 60s
    setInterval(fetchData, 60000);

    // init
    initCharts();
    fetchData();
  </script>
</body>
</html>
