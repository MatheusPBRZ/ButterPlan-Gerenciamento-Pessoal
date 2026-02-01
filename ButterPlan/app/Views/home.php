<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> ButterPlan - Dashboard</title>
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
    <a href="index.php?page=relatorios"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
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
                    <h1>Olá, Matheus </h1>
                    <p>Resumo financeiro e produtivo.</p>
                </div>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=Matheus+Passos&background=8257e5&color=fff" alt="Perfil">
                </div>
            </header>

            <section class="stats-grid">
                <div class="card finance-card">
                    <div class="card-header">
                        <span>Saldo Atual</span>
                        <i class="fa-solid fa-sack-dollar icon-bg"></i>
                    </div>
                    <div class="card-value">R$ <?= number_format($saldo, 2, ',', '.') ?></div>
                    <div class="card-footer <?= $saldo >= 0 ? 'positive' : 'warning' ?>">
                        <i class="fa-solid fa-wallet"></i> Total acumulado
                    </div>
                </div>

                <div class="card tasks-card">
                    <div class="card-header">
                        <span>Tarefas</span>
                        <i class="fa-solid fa-clipboard-list icon-bg"></i>
                    </div>
                    <div class="card-value">0</div>
                    <div class="card-footer warning">Em breve</div>
                </div>
            </section>

            <section class="recent-activity">
                <div class="recent-box">
    <h3><i class="fa-solid fa-check-double"></i> Foco Principal</h3>
    
    <ul class="task-list">
        <?php if(empty($tarefas)): ?>
            <p style="color:#777; padding:10px;">Sem tarefas pendentes.</p>
        <?php else: ?>
            <?php foreach($tarefas as $task): ?>
                <li class="task-item pending" style="margin-bottom:5px; padding:8px;">
                    
                    <a href="index.php?page=tarefas&action=toggle_task&id=<?= $task->id ?>" class="check-btn" style="margin-right:10px; font-size:1rem;">
                        <i class="fa-regular fa-square"></i>
                    </a>
                    
                    <span class="task-title" style="font-size:0.9rem;">
                        <?= htmlspecialchars($task->title) ?>
                    </span>
                    
                    <?php 
                        $pColor = match($task->priority) {
                            'high' => 'var(--danger-color)',
                            'medium' => 'var(--warning-color)',
                            'low' => 'var(--success-color)',
                        };
                    ?>
                    <span style="font-size:0.6rem; padding:1px 6px; border-radius:3px; border:1px solid <?= $pColor ?>; color:<?= $pColor ?>">
                        <?= strtoupper($task->priority) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
    
    <div style="margin-top:10px; text-align:center;">
        <a href="index.php?page=tarefas" style="font-size:0.85rem; color:var(--accent-color); text-decoration:none;">
            Ver todas as tarefas &rarr;
        </a>
    </div>
</div>

    
  
    </script>
</body>
</html>