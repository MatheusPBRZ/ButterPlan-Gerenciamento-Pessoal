<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ButterPlan - Finanças</title>
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
                <a href="index.php?page=financas" class="active"><i class="fa-solid fa-wallet"></i> Finanças</a>
                <a href="index.php?page=tarefas"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                <a href="index.php?page=relatorios"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
            </nav>
            <div class="logout">
                <a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>   
            </div>
        </aside>

        <main class="main-content">
            <header style="display:flex; justify-content:space-between; align-items:center;">
                <div class="header-title">
                    <h1>Gestão Financeira</h1>
                    <p>Meu ButterPlan te ajuda a viver e trabalhar, ao invés de trabalhar pra viver</p>
                </div>
                <div style="display:flex; gap:10px;">
                    <button onclick="openModal('fixedModal')" class="btn-save" style="width:auto; margin:0; padding:10px 20px; background:#444;">
                        <i class="fa-solid fa-repeat"></i> Nova Fixa
                    </button>
                    <button onclick="openModal('financeModal')" class="btn-save" style="width:auto; margin:0; padding:10px 20px;">
                        <i class="fa-solid fa-plus"></i> Transação
                    </button>
                </div>
            </header>

            <section class="stats-grid" style="margin-bottom: 2rem;">
                <div class="card">
                    <span style="font-size:0.9rem; opacity:0.7">Saldo no Banco</span>
                    <div style="font-size:1.5rem; font-weight:bold;">R$ <?= number_format($saldoReal, 2, ',', '.') ?></div>
                </div>
                <div class="card" style="border:1px solid var(--danger-color); background:rgba(247, 90, 104, 0.05);">
                    <span style="font-size:0.9rem; color:var(--danger-color);">Falta Pagar (Fixas)</span>
                    <div style="font-size:1.5rem; font-weight:bold; color:var(--danger-color);">- R$ <?= number_format($totalPendencias, 2, ',', '.') ?></div>
                </div>
                <div class="card" style="background:var(--accent-color); color:white;">
                    <span style="font-size:0.9rem;">Saldo Livre Real</span>
                    <div style="font-size:1.5rem; font-weight:bold;">R$ <?= number_format($saldoLivre, 2, ',', '.') ?></div>
                    <div style="font-size:0.8rem; opacity:0.8;">O que sobra pro lazer</div>
                </div>
            </section>

            <div style="display:flex; gap:20px; margin-bottom:2rem; opacity:0.8;">
                <div style="background:var(--card-bg); padding:10px 20px; border-radius:8px; border-left: 4px solid var(--success-color);">
                    <small>Entradas Mês</small>
                    <div style="font-weight:bold; color:var(--success-color)">R$ <?= number_format($resumo->entradas, 2, ',', '.') ?></div>
                </div>
                <div style="background:var(--card-bg); padding:10px 20px; border-radius:8px; border-left: 4px solid var(--danger-color);">
                    <small>Saídas Mês</small>
                    <div style="font-weight:bold; color:var(--danger-color)">R$ <?= number_format($resumo->saidas, 2, ',', '.') ?></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem;">
                
                <div class="recent-box">
                    <h3><i class="fa-solid fa-calendar-check"></i> Contas Fixas</h3>
                    <ul class="task-list">
                        <?php if(empty($fixas)): ?>
                            <p style="color:#777; font-size:0.9rem; padding:10px;">Nenhuma conta fixa cadastrada.</p>
                        <?php else: ?>
                            <?php foreach($fixas as $conta): ?>
                                <li style="display:flex; justify-content:space-between; align-items:center; opacity: <?= $conta->is_paid ? '0.5' : '1' ?>">
                                    <div>
                                        <strong style="display:block"><?= htmlspecialchars($conta->title) ?></strong>
                                        <small style="color:#777">Vence dia <?= $conta->day_of_month ?></small>
                                    </div>
                                    
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <span style="font-weight:bold;">R$ <?= number_format($conta->amount, 2, ',', '.') ?></span>
                                        
                                        <?php if($conta->is_paid): ?>
                                            <span style="color:var(--success-color); font-size:1.2rem;" title="Pago"><i class="fa-solid fa-circle-check"></i></span>
                                        <?php else: ?>
                                            <form method="POST" action="index.php?page=financas" style="display:inline;">
                                                <input type="hidden" name="action" value="add_transaction">
                                                <input type="hidden" name="type" value="expense">
                                                <input type="hidden" name="description" value="<?= $conta->title ?>">
                                                <input type="hidden" name="amount" value="<?= $conta->amount ?>">
                                                
                                                <button type="submit" style="background:none; border:1px solid #555; color:var(--text-primary); padding:5px 10px; border-radius:5px; cursor:pointer;" title="Pagar agora">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="index.php?page=financas&action=delete_fixed&id=<?= $conta->id ?>" 
                                           onclick="return confirm('Tem certeza que deseja apagar essa conta fixa?')"
                                           title="Excluir Recorrência"
                                           style="color: #666; transition: 0.2s; font-size: 0.9rem; margin-left:5px;"
                                           onmouseover="this.style.color='#f75a68'" 
                                           onmouseout="this.style.color='#666'">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="recent-box">
                    <h3>Extrato Completo</h3>
                    <table class="simple-table" style="width:100%; text-align:left;">
                        <thead>
                            <tr style="color:var(--text-secondary); border-bottom:1px solid #333;">
                                <th style="padding:10px;">Data</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th style="text-align:right;">Valor</th>
                                <th style="width: 40px;"></th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allTransactions as $t): ?>
                            <tr style="border-bottom:1px solid #333;">
                                <td style="padding:15px 10px; color:var(--text-secondary); font-size:0.9rem;">
                                    <?= date('d/m/Y', strtotime($t->created_at)) ?>
                                </td>
                                <td><?= htmlspecialchars($t->description) ?></td>
                                <td>
                                    <span class="badge" style="background:<?= $t->type == 'income' ? 'rgba(4,211,97,0.2)' : 'rgba(247,90,104,0.2)' ?>; color:<?= $t->type == 'income' ? 'var(--success-color)' : 'var(--danger-color)' ?>">
                                        <?= $t->type == 'income' ? 'Entrada' : 'Saída' ?>
                                    </span>
                                </td>
                                <td style="text-align:right; font-weight:bold;" class="<?= $t->type ?>">
                                    <?= $t->type == 'income' ? '+' : '-' ?> 
                                    R$ <?= number_format($t->amount, 2, ',', '.') ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="index.php?page=financas&action=delete_transaction&id=<?= $t->id ?>" 
                                       onclick="return confirm('Tem certeza que deseja apagar esse lançamento?')"
                                       title="Excluir"
                                       style="color: #666; transition: 0.2s; font-size: 0.9rem;"
                                       onmouseover="this.style.color='#f75a68'" 
                                       onmouseout="this.style.color='#666'">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main> 
    </div> 

    <div id="financeModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nova Movimentação</h3>
                <button class="close-modal" onclick="closeModal('financeModal')">&times;</button>
            </div>
            <form method="POST" action="index.php?page=financas">
                <input type="hidden" name="action" value="add_transaction">
                <div class="form-group">
                    <label>Tipo</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="type" value="income" checked>
                            <span class="pill income">Entrada</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="type" value="expense">
                            <span class="pill expense">Saída</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <input type="text" name="description" placeholder="Ex: Conta de Luz" required>
                </div>
                <div class="form-group">
                    <label>Valor (R$)</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                </div>
                <button type="submit" class="btn-save">Salvar</button>
            </form>
        </div>
    </div>

    <div id="fixedModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cadastrar Conta Fixa</h3>
                <button class="close-modal" onclick="closeModal('fixedModal')">&times;</button>
            </div>
            <form method="POST" action="index.php?page=financas">
                <input type="hidden" name="action" value="add_fixed">
                <div class="form-group">
                    <label>Nome da Conta</label>
                    <input type="text" name="title" placeholder="Ex: Netflix, Aluguel..." required>
                </div>
                <div class="form-group">
                    <label>Valor Mensal (R$)</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Dia do Vencimento</label>
                    <input type="number" name="day_of_month" placeholder="Dia (1-31)" min="1" max="31" required>
                </div>
                <button type="submit" class="btn-save">Criar Recorrência</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        window.onclick = function(e) { if(e.target.classList.contains('modal-overlay')) e.target.classList.remove('active'); }
    </script>
</body>
</html>