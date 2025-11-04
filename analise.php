<?php
require_once __DIR__ . "/conexao.php";

$inicio = $_GET['inicio'] ?? '';
$fim    = $_GET['fim'] ?? '';
$limit  = (int)($_GET['limit'] ?? 500);
if ($limit <= 0 || $limit > 5000) $limit = 500;

function dtLocalToMysql(?string $v): ?string {
    if (!$v) return null;
    $v = str_replace('T', ' ', $v);
    return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v) ? ($v . ':00') : null;
}
$inicioMysql = dtLocalToMysql($inicio);
$fimMysql    = dtLocalToMysql($fim);

$sql = "SELECT data_registro, umidade, status FROM dados_umidade WHERE 1=1";
$params = []; $types = "";
if ($inicioMysql) { $sql .= " AND data_registro >= ?"; $params[] = $inicioMysql; $types .= "s"; }
if ($fimMysql)    { $sql .= " AND data_registro <= ?"; $params[] = $fimMysql;    $types .= "s"; }
$sql .= " ORDER BY data_registro DESC LIMIT ?";
$params[] = $limit; $types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$dadosUmidade = [];
while ($row = $result->fetch_assoc()) $dadosUmidade[] = $row;
$stmt->close(); $conn->close();

$dadosUmidade = array_reverse($dadosUmidade);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=dados_umidade.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Data Registro','Umidade (%)','Status']);
    foreach ($dadosUmidade as $r) fputcsv($out, [$r['data_registro'],$r['umidade'],$r['status']]);
    fclose($out); exit;
}
$dadosUmidadeJson = json_encode($dadosUmidade);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Análise de Dados — Irrigação Inteligente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Temas -->
  <link id="theme-bootstrap" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link id="theme-material" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" disabled>
  <link rel="stylesheet" href="css/custom-theme.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header class="topbar">
    <h1>🔎 Análise de Dados</h1>
    <div class="theme-switch">
      <button class="btn btn-sm btn-primary" onclick="setTheme('bootstrap')">Bootstrap</button>
      <button class="btn btn-sm btn-secondary" onclick="setTheme('material')">Material</button>
      <button class="btn btn-sm btn-dark" onclick="toggleDarkMode()">🌙 Dark</button>
      <a href="index.php" class="btn btn-outline-secondary">⬅ Voltar</a>
    </div>
  </header>

  <main class="container my-4">
    <div class="card p-3 mb-4">
      <form method="get" action="analise.php" class="row g-3">
        <div class="col-md-3">
          <label for="inicio" class="form-label">Início:</label>
          <input type="datetime-local" id="inicio" name="inicio" value="<?=htmlspecialchars($inicio)?>" class="form-control">
        </div>
        <div class="col-md-3">
          <label for="fim" class="form-label">Fim:</label>
          <input type="datetime-local" id="fim" name="fim" value="<?=htmlspecialchars($fim)?>" class="form-control">
        </div>
        <div class="col-md-2">
          <label for="limit" class="form-label">Limite:</label>
                    <input type="number" id="limit" name="limit" value="<?=$limit?>" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary">Aplicar</button>
          <a href="analise.php" class="btn btn-outline-secondary">Limpar</a>
          <button type="submit" name="export" value="csv" class="btn btn-success">⬇ CSV</button>
        </div>
      </form>
    </div>

    <div class="card p-3 mb-4">
      <h2 class="h6">Umidade do Solo + Status</h2>
      <?php if (empty($dadosUmidade)): ?>
        <p class="text-danger">Nenhum dado encontrado.</p>
      <?php else: ?>
        <canvas id="graficoUmidadeStatus"></canvas>
        <div class="d-flex gap-3 mt-3 flex-wrap">
          <span><span class="legend-box bg-danger"></span> Seco</span>
          <span><span class="legend-box bg-warning"></span> Moderado</span>
          <span><span class="legend-box bg-success"></span> Úmido</span>
          <span><span class="legend-box bg-primary"></span> Encharcado</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-3">
      <h2 class="h6">Resumo</h2>
      <p>Média: <span id="media">—</span></p>
      <p>Mínima: <span id="min">—</span></p>
      <p>Máxima: <span id="max">—</span></p>
    </div>
  </main>

  <script>
    // Alternância de temas
    function setTheme(theme) {
      const bootstrap = document.getElementById('theme-bootstrap');
      const material  = document.getElementById('theme-material');
      if (theme === 'bootstrap') {
        bootstrap.removeAttribute('disabled');
        material.setAttribute('disabled', 'true');
      } else {
        material.removeAttribute('disabled');
        bootstrap.setAttribute('disabled', 'true');
      }
    }
    function toggleDarkMode(){
      document.body.classList.toggle("dark-mode");
      if(document.body.classList.contains("dark-mode")){
        localStorage.setItem("darkMode","true");
      } else {
        localStorage.removeItem("darkMode");
      }
    }
    window.onload = () => {
      if(localStorage.getItem("darkMode")==="true"){
        document.body.classList.add("dark-mode");
      }
    };

    // Dados PHP -> JS
    const dadosUmidade = <?=$dadosUmidadeJson ?: '[]'?>;
    const labels = dadosUmidade.map(r => r.data_registro);
    const umidades = dadosUmidade.map(r => parseFloat(r.umidade));
    const status = dadosUmidade.map(r => {
      switch(r.status) {
        case "Solo seco": return 0;
        case "Umidade moderada": return 1;
        case "Solo úmido": return 2;
        case "Solo encharcado": return 3;
        default: return null;
      }
    });

    if (umidades.length) {
      document.getElementById("media").textContent = (umidades.reduce((a,b)=>a+b,0)/umidades.length).toFixed(1)+"%";
      document.getElementById("min").textContent = Math.min(...umidades).toFixed(1)+"%";
      document.getElementById("max").textContent = Math.max(...umidades).toFixed(1)+"%";
    }

    const backgroundPlugin = {
      id: 'backgroundPlugin',
      beforeDatasetsDraw: (chart) => {
        const ctx = chart.ctx, area = chart.chartArea, y1 = chart.scales.y1;
        if (!y1) return;
        ctx.save();
        const ranges = [
          {min:-0.5,max:0.5,color:'rgba(239,68,68,0.2)'},
          {min:0.5,max:1.5,color:'rgba(245,158,11,0.2)'},
          {min:1.5,max:2.5,color:'rgba(16,185,129,0.2)'},
          {min:2.5,max:3.5,color:'rgba(59,130,246,0.2)'}
        ];
        ranges.forEach(r=>{
          ctx.fillStyle=r.color;
          ctx.fillRect(area.left,y1.getPixelForValue(r.max),area.right-area.left,y1.getPixelForValue(r.min)-y1.getPixelForValue(r.max));
        });
        ctx.restore();
      }
    };

    if (dadosUmidade.length) {
      new Chart(document.getElementById('graficoUmidadeStatus'), {
        type: 'line',
        data: {
          labels,
          datasets: [
            {label:'Umidade (%)',data:umidades,borderColor:'#0b66ff',borderWidth:2,backgroundColor:'rgba(11,102,255,0.2)',fill:true,tension:0.3,yAxisID:'y'},
            {label:'Status',data:status,borderColor:'#10b981',borderWidth:2,stepped:true,fill:false,yAxisID:'y1'}
          ]
        },
        options: {
          responsive:true,
          interaction:{mode:'index',intersect:false},
          scales:{
            y:{type:'linear',position:'left',min:0,max:100,title:{display:true,text:'Umidade (%)'}},
            y1:{type:'linear',position:'right',min:0,max:3,
              ticks:{stepSize:1,callback:v=>["Seco","Moderado","Úmido","Encharcado"][v]||v},
              title:{display:true,text:'Status'}}
          }
        },
        plugins:[backgroundPlugin]
      });
    }
  </script>
</body>
</html>

