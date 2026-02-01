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
                    <div class="logout">

        </nav>
        </nav>
            <div class="logout">
    <a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
</div>
        </aside>

        <main class="main-content">
            <header>
                <div class="header-title">
                    <h1>Relatórios Mensais</h1>
                    <p>Analise sua performance.</p>
                </div>
            </header>

            <form method="GET" action="index.php" class="filter-bar">
                <input type="hidden" name="page" value="relatorios">
                <i class="fa-solid fa-filter"></i>
                <span>Visualizando:</span>
                
                <select name="month" class="filter-select" onchange="this.form.submit()">
                    <?php 
                    for($m=1; $m<=12; $m++): 
                        $selected = $m == $mes ? 'selected' : '';
                        $nomeMes = date('F', mktime(0, 0, 0, $m, 10)); // Pega nome em inglês
                    ?>
                        <option value="<?= $m ?>" <?= $selected ?>><?= $m ?> - <?= $nomeMes ?></option>
                    <?php endfor; ?>
                </select>

               <select name="year" class="filter-select" onchange="this.form.submit()">
                    <?php 
                    // Pega o ano atual do sistema (ex: 2026)
                    $anoAtualSistema = date('Y');
                    
                    // Loop: Começa em 2024 e vai até o ano atual + 1 (pra vc poder planejar o futuro)
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
                        <div class="circular-chart" 
                             style="background: conic-gradient(
                                var(--<?= $msgFinanceira[2] ?>-color) <?= $margemFinanceira < 0 ? 0 : $margemFinanceira ?>%, 
                                #333 0
                             );">
                            <div class="chart-value"><?= number_format($margemFinanceira, 1) ?>%</div>
                        </div>
                    </div>

                    <div style="text-align:center; margin-bottom:20px;">
                        <p style="font-size:0.9rem; color:#888;">Margem de Lucro</p>
                    </div>

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
                        <div class="circular-chart" 
                             style="background: conic-gradient(
                                var(--<?= $msgProdutividade[2] ?>-color) <?= $taxaProdutividade ?>%, 
                                #333 0
                             );">
                            <div class="chart-value"><?= number_format($taxaProdutividade, 0) ?>%</div>
                        </div>
                    </div>

                    <div style="text-align:center; margin-bottom:20px;">
                        <p style="font-size:0.9rem; color:#888;">Taxa de Conclusão</p>
                    </div>

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

            </div>
        </main>
    </div>
</body>
</html>