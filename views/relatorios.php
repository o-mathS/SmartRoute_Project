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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
      body {
        background: #f7f8fa;
      }
      .main-content {
        padding: 30px 40px;
      }
      .kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin: 18px 0 32px;
      }
      .kpi-card {
        background: linear-gradient(135deg, #f8fafc 80%, #e9e9e9 100%);
        border: 1px solid #e3e3e3;
        border-radius: 14px;
        padding: 18px 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-width: 0;
      }
      .kpi-title {
        font-size: 13px;
        color: #888;
        margin-bottom: 8px;
        font-weight: 500;
        letter-spacing: 0.5px;
      }
      .kpi-value {
        font-size: 24px;
        font-weight: 700;
        color: #1a7a1a;
        letter-spacing: 1px;
      }
      .charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        margin-bottom: 24px;
      }
      .chart-box {
        background: #fff;
        border: 1px solid #e3e3e3;
        border-radius: 18px;
        padding: 32px 24px 24px 24px;
        box-shadow: 0 6px 24px rgba(0,0,0,.08);
        min-height: 340px;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: box-shadow 0.2s;
      }
      .chart-box:hover {
        box-shadow: 0 12px 32px rgba(0,0,0,.12);
      }
      .chart-box h4 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 18px;
        color: #1a7a1a;
        letter-spacing: 1px;
        text-align: center;
      }
      .chart-box canvas {
        width: 90% !important;
        height: 220px !important;
        max-width: 600px !important;
        max-height: 220px !important;
        margin: 0 auto;
        background: #f7f8fa;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        display: block;
        border: 1px solid #e3e3e3;
      }
      #chartEntregador {
        width: 98% !important;
        height: 340px !important;
        max-width: 900px !important;
        max-height: 340px !important;
      }
      @media (max-width: 1100px) {
        .main-content {
          padding: 18px 6px;
        }
        .charts {
          grid-template-columns: 1fr;
          gap: 18px;
        }
        .kpis {
          grid-template-columns: repeat(2, 1fr);
          gap: 12px;
        }
        .chart-box {
          min-height: 220px;
          padding: 18px 6px 6px 6px;
        }
        .chart-box canvas {
          height: 120px !important;
          max-height: 120px !important;
        }
      }
      @media (max-width: 700px) {
        .main-content {
          padding: 8px 2px;
        }
        .kpis {
          grid-template-columns: 1fr;
          gap: 8px;
        }
        .chart-box {
          min-height: 120px;
          padding: 8px 2px 2px 2px;
        }
        .chart-box canvas {
          height: 60px !important;
          max-height: 60px !important;
        }
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
        <li><a href="entregas.php" class="mini-menu-item"><span class="mini-menu-icon">📦</span>Entregas</a></li>
        <li><a href="relatorios.php" class="mini-menu-item active"><span class="mini-menu-icon">📊</span>Relatórios</a></li>
      </ul>
    </nav>
  </div>

  <form method="post" action="logout.php" style="margin-top: 20px;">
    <button type="submit" style="position: fixed; top: 860px; left: 80px; padding: 10px 20px; background-color: #d11a1a; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Sair</button>
  </form>

  <div class="main-content">
    <h2>Dashboard – Relatórios e Gráficos</h2>

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
        <h4>Distribuição por Status</h4><canvas id="chartStatus"></canvas>
      </div>
      <div class="chart-box">
        <h4>Concluídas ao longo do tempo</h4><canvas id="chartLinha"></canvas>
      </div>
      <div class="chart-box" style="grid-column: 1 / -1;">
        <h4>Entregas por Entregador</h4><canvas id="chartEntregador"></canvas>
      </div>
    </div>
  </div>

  <script>
    const $ = s => document.querySelector(s);

    let chartStatus, chartLinha, chartEntregador;

    // Global Chart Styling
    Chart.defaults.font.family = "'Sofia Sans', sans-serif";
    Chart.defaults.font.size = 14;
    Chart.defaults.plugins.legend.labels.color = "#333";
    Chart.defaults.plugins.legend.position = "bottom";
    Chart.defaults.plugins.tooltip.backgroundColor = "#000000ff";
    Chart.defaults.plugins.tooltip.titleColor = "#fff";
    Chart.defaults.plugins.tooltip.bodyColor = "#fff";

    function initCharts() {
      const ctxStatus = $('#chartStatus').getContext('2d');
      const ctxLinha = $('#chartLinha').getContext('2d');
      const ctxEnt = $('#chartEntregador').getContext('2d');

      // Pie Chart - Status
      chartStatus = new Chart(ctxStatus, {
        type: 'pie',
        data: {
          labels: [],
          datasets: [{
            data: [],
            backgroundColor: ['#979797ff', '#c9b611ff', '#16c940ff', '#d11a1a'],
            borderColor: '#fff',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          }
        }
      });

      // Line Chart - Concluídas ao longo do tempo
      chartLinha = new Chart(ctxLinha, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Concluídas',
            data: [],
            borderColor: '#d4edda ',
            backgroundColor: 'rgba(26,122,26,0.2)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#1a7a1a'
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: {
              ticks: {
                color: '#333'
              }
            },
            y: {
              ticks: {
                color: '#333'
              },
              beginAtZero: true
            }
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          }
        }
      });

      // Bar Chart - Entregas por Entregador
      chartEntregador = new Chart(ctxEnt, {
        type: 'bar',
        data: {
          labels: [],
          datasets: [{
            label: 'Entregas',
            data: [],
            backgroundColor: '#00aaff',
            borderRadius: 8,
            barThickness: 30
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: {
              ticks: {
                color: '#333'
              }
            },
            y: {
              ticks: {
                color: '#333'
              },
              beginAtZero: true
            }
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          }
        }
      });
    }

    function applyToCharts(data) {
      // Status Pie
      chartStatus.data.labels = ['Agendada', 'Em andamento', 'Concluído', 'Cancelada'];
      chartStatus.data.datasets[0].data = [
        data.status_counts.Agendada || 0,
        data.status_counts['Em andamento'] || 0,
        data.status_counts['Concluído'] || 0,
        data.status_counts['Cancelada'] || 0
      ];
      chartStatus.update();

      // Concluídas Line
      chartLinha.data.labels = data.concluidas_por_dia.labels;
      chartLinha.data.datasets[0].data = data.concluidas_por_dia.values;
      chartLinha.update();

      // Entregador Bar
      const nomes = Object.keys(data.por_entregador || {});
      chartEntregador.data.labels = nomes;
      chartEntregador.data.datasets[0].data = nomes.map(n => data.por_entregador[n]);
      chartEntregador.update();

      // KPIs
      $('#kpiHoje').textContent = data.kpis.concluidas_hoje || 0;
      $('#kpiAtrasoMedio').textContent = (data.kpis.media_atraso_dias || 0).toFixed(2);
      $('#kpiCancelamentos').textContent = data.kpis.cancelamentos || 0;
      $('#kpiPercAtrasadas').textContent = ((data.kpis.perc_atrasadas || 0) * 100).toFixed(1) + '%';
    }

    async function fetchData() {
      try {
        const res = await fetch('dashboard_data.php', {
          cache: 'no-store'
        });
        const data = await res.json();
        applyToCharts(data);
      } catch (err) {
        console.error('Erro ao carregar dados:', err);
      }
    }

    initCharts();
    fetchData();
    setInterval(fetchData, 60000);
    // Pie Chart - Status
    chartStatus = new Chart(ctxStatus, {
      type: 'pie',
      data: {
        labels: [],
        datasets: [{
          data: [],
          backgroundColor: ['#f0c419', '#00aaff', '#1a7a1a', '#d11a1a'],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.parsed || 0;
                const sum = context.chart._metasets[context.datasetIndex].total;
                const perc = ((value / sum) * 100).toFixed(1);
                return `${label}: ${value} (${perc}%)`;
              }
            }
          }
        }
      }
    });

    // Bar Chart - Entregas por Entregador
    chartEntregador = new Chart(ctxEnt, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [{
          label: 'Entregas',
          data: [],
          backgroundColor: '#00aaff',
          borderRadius: 8,
          barThickness: 30
        }]
      },
      options: {
        responsive: true,
        scales: {
          x: {
            ticks: {
              color: '#333'
            }
          },
          y: {
            ticks: {
              color: '#333'
            },
            beginAtZero: true
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.parsed.y || 0;
                return `Entregas: ${value}`;
              }
            }
          }
        }
      }
    });
  </script>
</body>

</html>