<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ButterPlan - Relatórios</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <div class="app-container">
        <aside class="sidebar">
            <div class="logo">
                <img src="assets/img/logo.png" alt="Butterlogo" class="logo-img">
            </div>
            <nav>
                <a href="index.php?page=home"><i class="fa-solid fa-house"></i> Visão Geral</a>
                <a href="index.php?page=financas"><i class="fa-solid fa-wallet"></i> Finanças</a>
                <a href="index.php?page=tarefas"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                <a href="index.php?page=relatorios" class="active"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
            </nav>
            <div class="logout">
                <a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <div class="header-title">
                    <h1>Relatórios Mensais</h1>
                    <p>Analise sua performance e gastos categorizados.</p>
                </div>
            </header>

            <form method="GET" action="index.php" class="filter-bar" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <input type="hidden" name="page" value="relatorios">
                <i class="fa-solid fa-filter"></i>
                
                <select name="month" class="filter-select" onchange="this.form.submit()">
                    <?php 
                    for($m=1; $m<=12; $m++): 
                        $selected = $m == $mes ? 'selected' : '';
                        $nomeMes = date('F', mktime(0, 0, 0, $m, 10));
                    ?>
                        <option value="<?= $m ?>" <?= $selected ?>><?= $m ?> - <?= $nomeMes ?></option>
                    <?php endfor; ?>
                </select>

                <select name="year" class="filter-select" onchange="this.form.submit()">
                    <?php 
                    $anoAtualSistema = date('Y');
                    for($y = 2024; $y <= $anoAtualSistema + 1; $y++): 
                        $selected = $y == $ano ? 'selected' : '';
                    ?>
                        <option value="<?= $y ?>" <?= $selected ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <div class="recent-box">
                    <h3><i class="fa-solid fa-sack-dollar"></i> Saúde Financeira</h3>
                    <div class="chart-container">
                        <canvas id="donutFinanceiro"></canvas>
                        <div class="chart-center-value"><?= number_format($margemFinanceira, 1) ?>%</div>
                    </div>
                    <div style="text-align:center; margin: 10px 0 20px;"><p style="font-size:0.9rem; color:#888;">Margem de Lucro</p></div>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.9rem;">
                        <span>Ganhou:</span>
                        <span class="text-success">R$ <?= number_format($fin->entradas, 2, ',', '.') ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.9rem;">
                        <span>Gastou:</span>
                        <span class="text-danger">R$ <?= number_format($fin->saidas, 2, ',', '.') ?></span>
                    </div>

                    <div class="verdict-box border-<?= $msgFinanceira[2] ?>">
                        <strong class="text-<?= $msgFinanceira[2] ?>"><?= $msgFinanceira[0] ?></strong>
                        <p><?= $msgFinanceira[1] ?></p>
                    </div>
                </div>

                <div class="recent-box">
                    <h3><i class="fa-solid fa-list-check"></i> Produtividade</h3>
                    <div class="chart-container">
                        <canvas id="donutProdutividade"></canvas>
                        <div class="chart-center-value"><?= number_format($taxaProdutividade, 1) ?>%</div>
                    </div>
                    <div style="text-align:center; margin: 10px 0 20px;"><p style="font-size:0.9rem; color:#888;">Taxa de Conclusão</p></div>

                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.9rem;">
                        <span>Tarefas Totais:</span>
                        <span><?= $tasks->total ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.9rem;">
                        <span>Concluídas:</span>
                        <span class="text-accent"><?= $tasks->concluidas ?? 0 ?></span>
                    </div>

                    <div class="verdict-box border-<?= $msgProdutividade[2] ?>">
                        <strong class="text-<?= $msgProdutividade[2] ?>"><?= $msgProdutividade[0] ?></strong>
                        <p><?= $msgProdutividade[1] ?></p>
                    </div>
                </div>

                <div class="recent-box" style="grid-column: span 2;">
                    <h3><i class="fa-solid fa-chart-bar"></i> Gastos por Categoria</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="barChartCategorias"></canvas>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // Cores do Tema ButterPlan
    const colorSuccess = '#2ecc71';
    const colorDanger = '#e74c3c';
    const colorAccent = '#f1c40f';
    const colorEmpty = '#333';

    // --- GRÁFICO 1: SAÚDE FINANCEIRA (ROSCA) ---
    new Chart(document.getElementById('donutFinanceiro').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Saldo Livre', 'Gastos'],
            datasets: [{
                data: [<?= max(0, $margemFinanceira) ?>, <?= 100 - max(0, $margemFinanceira) ?>],
                backgroundColor: [colorSuccess, colorEmpty],
                borderWidth: 0
            }]
        },
        options: { cutout: '80%', plugins: { legend: { display: false } } }
    });

    // --- GRÁFICO 2: PRODUTIVIDADE (ROSCA) ---
    new Chart(document.getElementById('donutProdutividade').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Concluídas', 'Pendentes'],
            datasets: [{
                data: [<?= $taxaProdutividade ?>, <?= 100 - $taxaProdutividade ?>],
                backgroundColor: ['#9b59b6', colorEmpty],
                borderWidth: 0
            }]
        },
        options: { cutout: '80%', plugins: { legend: { display: false } } }
    });

    // --- GRÁFICO 3: CATEGORIAS (BARRAS) ---
    new Chart(document.getElementById('barChartCategorias').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($dadosGrafico)) ?>,
            datasets: [{
                label: 'Total Gasto (R$)',
                data: <?= json_encode(array_values($dadosGrafico)) ?>,
                backgroundColor: '#4e73df',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
    </script>

    <style>
    .chart-container { position: relative; height: 200px; width: 200px; margin: auto; }
    .chart-center-value {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.4rem; font-weight: 600; color: #fff;
    }
    .filter-select {
        background: #252525; color: white; border: 1px solid #444;
        padding: 5px 10px; border-radius: 5px; cursor: pointer;
    }
    .verdict-box { margin-top: 15px; padding: 10px; border-left: 4px solid; background: rgba(255,255,255,0.03); }
    .text-success { color: #2ecc71; }
    .text-danger { color: #e74c3c; }
    .text-warning { color: #f1c40f; }
    .text-accent { color: #9b59b6; }
    </style>

</body>
</html>