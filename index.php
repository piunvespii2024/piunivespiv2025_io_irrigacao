<?php
header('Content-Type: text/html; charset=utf-8');
include 'conexao.php';

// Estado inicial do controle
$controle_inicial = ['estado' => 'OFF', 'modo' => 'MANUAL'];
try {
    $res = $conn->query("SELECT estado, modo FROM controle ORDER BY updated_at DESC LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $controle_inicial = $row;
    }
} catch (Exception $e) { /* mantém defaults */ }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <title>Sistema de Irrigação</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Temas -->
  <link id="theme-bootstrap" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link id="theme-material" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" disabled>
  <link rel="stylesheet" href="css/custom-theme.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
  <header class="topbar">
    <h1>🌱 Monitoramento de Irrigação</h1>
    <div class="theme-switch">
      <button class="btn btn-sm btn-primary" onclick="setTheme('bootstrap')">Bootstrap</button>
      <button class="btn btn-sm btn-secondary" onclick="setTheme('material')">Material</button>
      <button class="btn btn-sm btn-dark" onclick="toggleDarkMode()">🌙 Dark</button>
    </div>
  </header>

  <nav class="d-flex gap-2 justify-content-center mb-4 flex-wrap">
    <a href="painel_controle.php" class="btn btn-primary">🔧 Página de Controle</a>
    <a href="grafico.html" class="btn btn-primary">📈 Gráfico em tempo real</a>
    <a href="analise.php" class="btn btn-success">🔎 Análise de dados</a>
  </nav>

  <main class="container">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card p-3 text-center">
          <h2 class="h6">Status</h2>
          <p>Modo: <strong id="modo"><?= htmlspecialchars($controle_inicial['modo']); ?></strong></p>
          <p>Estado: <strong id="estado"><?= htmlspecialchars($controle_inicial['estado']); ?></strong></p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3">
          <h2 class="h6">Filtro por Data</h2>
          <input type="date" id="dateFilter" class="form-control mb-2" />
          <div class="d-flex gap-2">
            <button id="btnHoje" type="button" class="btn btn-outline-primary btn-sm">Hoje</button>
            <button id="btnLimpar" type="button" class="btn btn-outline-secondary btn-sm">Limpar</button>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3">
          <h2 class="h6">Máx Pontos</h2>
          <select id="limit" class="form-select mb-2">
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100" selected>100</option>
            <option value="500">500</option>
          </select>
          <button id="btnAtualizar" type="button" class="btn btn-outline-primary btn-sm">Atualizar</button>
        </div>
      </div>
    </div>

    <div class="card p-3 mt-4">
      <h2 class="h5">Últimas Leituras</h2>
      <canvas id="grafico" height="350"></canvas>
      <div class="legend-fixed d-flex justify-content-center gap-3 mt-3 flex-wrap">
        <div class="legend-item"><div class="legend-box bg-success"></div> Umidade Solo</div>
        <div class="legend-item"><div class="legend-box bg-warning"></div> Status Solo</div>
      </div>
      <div id="info" class="mt-2 text-muted small"></div>
      <div id="error" class="error text-danger fw-bold" style="display:none;"></div>
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

    // Chart.js inicial
    const ctx = document.getElementById('grafico').getContext('2d');
    const grafico = new Chart(ctx, {
      type: 'line',
      data: { labels: [], datasets: [
        { label: 'Umidade Solo (%)', borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.06)', data: [], tension:0.2, fill:true, yAxisID:'y' },
        { label: 'Status do Solo', borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.2)', data: [], stepped:true, fill:false, yAxisID:'y1' }
      ]},
      options: {
        responsive:true, animation:false, interaction:{ mode:'index', intersect:false },
        scales:{
          y:{ beginAtZero:true, max:100, title:{display:true,text:'Umidade (%)'} },
          y1:{ type:'linear', position:'right', min:0, max:3,
            ticks:{ stepSize:1, callback:v=>(['Seco','Moderado','Úmido','Encharcado'][v] ?? v) },
            title:{display:true,text:'Nível do Solo'}, grid:{ drawOnChartArea:false }
          }
        }
      }
    });

        // ===========================
    // Funções auxiliares
    // ===========================
    function normalizarLeitura(row){
      return {
        created_at: row.created_at ?? row.data_registro ?? null,
        umidade_solo: row.umidade_solo !== undefined 
                        ? (row.umidade_solo===null?null:parseFloat(row.umidade_solo))
                        : (row.umidade !== undefined 
                            ? (row.umidade===null?null:parseFloat(row.umidade)) 
                            : null),
        status: row.status ?? ''
      };
    }

    function statusToLevel(status){
      const s = String(status || '').toLowerCase();
      if (s.includes('seco')) return 0;
      if (s.includes('moderad')) return 1;
      if (s.includes('úmido') || s.includes('umido')) return 2;
      if (s.includes('encharcado')) return 3;
      return null;
    }

    const infoEl = document.getElementById('info');
    const errorEl = document.getElementById('error');
    let leiturasBrutas = [];

    function clearError(){ errorEl.style.display='none'; errorEl.textContent=''; }
    function showError(msg){ errorEl.style.display='block'; errorEl.textContent=msg; }
    function toDateInputValue(d){ const z=n=>n<10?'0'+n:n; return `${d.getFullYear()}-${z(d.getMonth()+1)}-${z(d.getDate())}`; }
    function isFutureDate(dateStr){ 
      if(!dateStr) return false; 
      const selected=new Date(dateStr+'T00:00:00'); 
      const today=new Date(); 
      const tz=new Date(today.getFullYear(),today.getMonth(),today.getDate(),0,0,0,0); 
      return selected>tz; 
    }

    // ===========================
    // Buscar dados do servidor
    // ===========================
    async function buscarDadosServidor(limit = 100, dateStr = ''){
      clearError();
      infoEl.textContent = 'Carregando...';
      if (dateStr && isFutureDate(dateStr)) {
        showError('A data selecionada é posterior à data atual.');
        infoEl.textContent = '';
        return false;
      }
      const params = new URLSearchParams();
      params.set('limit', String(limit));
      if (dateStr) params.set('date', dateStr);

      try {
        const res = await fetch('api/get_dados.php?' + params.toString(), { cache: 'no-store' });
        if (!res.ok) {
          const txt = await res.text();
          showError('Erro servidor: ' + res.status + ' ' + res.statusText);
          console.error('Resposta não OK:', res.status, txt);
          infoEl.textContent = '';
          return false;
        }
        const data = await res.json();
        if (!data || !data.sucesso) {
          showError('Erro obtendo dados do servidor.');
          console.error('get_dados retorno:', data);
          infoEl.textContent = '';
          return false;
        }

        // Atualiza Modo/Estado se vier no payload
        if (data.controle) {
          document.getElementById('modo').innerText = data.controle.modo ?? '';
          document.getElementById('estado').innerText = data.controle.estado ?? '';
        }

        // Normaliza array leituras
        leiturasBrutas = (Array.isArray(data.leituras) ? data.leituras : []).map(normalizarLeitura);

        aplicarFiltroEAtualizarGrafico();
        infoEl.textContent = `Dados carregados: ${leiturasBrutas.length} pontos (servidor).`;
        return true;
      } catch (err) {
        console.error(err);
        showError('Falha ao comunicar com o servidor.');
        infoEl.textContent = '';
        return false;
      }
    }

    // ===========================
    // Atualizar gráfico
    // ===========================
    function aplicarFiltroEAtualizarGrafico(){
      clearError();
      const dateInput = document.getElementById('dateFilter').value;
      let dados = leiturasBrutas.slice();

      if (dateInput) {
        if (isFutureDate(dateInput)) {
          showError('A data selecionada é posterior à data atual.');
          return;
        }
        dados = dados.filter(r => r.created_at && r.created_at.startsWith(dateInput));
      }

      dados.sort((a,b) => {
        const ta = a.created_at ? new Date(a.created_at) : new Date(0);
        const tb = b.created_at ? new Date(b.created_at) : new Date(0);
        return ta - tb;
      });

      const labels = dados.map(r => r.created_at ? new Date(r.created_at).toLocaleString() : '');
      const us = dados.map(r => r.umidade_solo);
      const statusVals = dados.map(r => statusToLevel(r.status));

      grafico.data.labels = labels;
      grafico.data.datasets[0].data = us;
      grafico.data.datasets[1].data = statusVals;
      grafico.update();

      infoEl.textContent = `Exibindo ${dados.length} pontos${dateInput ? ' para ' + dateInput : ''}.`;
    }

    // ===========================
    // Eventos
    // ===========================
    document.getElementById('btnAtualizar').addEventListener('click', async () => {
      const limit = parseInt(document.getElementById('limit').value, 10) || 100;
      const dateStr = document.getElementById('dateFilter').value || '';
      await buscarDadosServidor(limit, dateStr);
    });

    document.getElementById('dateFilter').addEventListener('change', async () => {
      const dateStr = document.getElementById('dateFilter').value || '';
      const limit = parseInt(document.getElementById('limit').value, 10) || 500;
      if (dateStr) {
        if (isFutureDate(dateStr)) { showError('A data selecionada é posterior à data atual.'); return; }
        await buscarDadosServidor(limit, dateStr);
      } else {
        await buscarDadosServidor(limit, '');
      }
    });

    document.getElementById('btnLimpar').addEventListener('click', async () => {
      document.getElementById('dateFilter').value = '';
      clearError();
      await buscarDadosServidor(parseInt(document.getElementById('limit').value, 10), '');
    });

    document.getElementById('btnHoje').addEventListener('click', async () => {
      const today = toDateInputValue(new Date());
      document.getElementById('dateFilter').value = today;
      clearError();
      await buscarDadosServidor(parseInt(document.getElementById('limit').value, 10), today);
    });

    // ===========================
    // Carregamento inicial
    // ===========================
    (async () => {
      await buscarDadosServidor(parseInt(document.getElementById('limit').value, 10), '');
    })();
  </script>
</body>
</html>


