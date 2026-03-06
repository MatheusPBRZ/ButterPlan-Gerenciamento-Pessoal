<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ButterPlan - Finanças</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        .progress-bar-bg { width: 100%; background: rgba(255,255,255,0.1); border-radius: 4px; height: 6px; margin-top: 5px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: var(--accent-color); transition: 0.4s; }
        .history-box { background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 0.8rem; border-left: 2px solid var(--accent-color); }
    </style>
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
            <header style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap:10px;">
                <div class="header-title"><h1>Gestão Financeira</h1><p>Controle absoluto do meu dinheiro.</p></div>
                <div style="display:flex; gap:10px;">
                    <button onclick="openModal('installmentModal')" class="btn-save" style="width:auto; margin:0; padding:10px 15px; background:#2c3e50;"><i class="fa-solid fa-credit-card"></i> Parcelamento</button>
                    <button onclick="openModal('fixedModal')" class="btn-save" style="width:auto; margin:0; padding:10px 15px; background:#444;"><i class="fa-solid fa-repeat"></i> Conta Fixa</button>
                    <button onclick="openModal('financeModal')" class="btn-save" style="width:auto; margin:0; padding:10px 15px;"><i class="fa-solid fa-plus"></i> Transação</button>
                </div>
            </header>

            <section class="stats-grid" style="margin-bottom: 2rem;">
                <div class="card"><span style="font-size:0.9rem; opacity:0.7">Saldo no Banco</span><div style="font-size:1.5rem; font-weight:bold;">R$ <?= number_format($saldoReal, 2, ',', '.') ?></div></div>
                <div class="card" style="border:1px solid var(--danger-color); background:rgba(247, 90, 104, 0.05);"><span style="font-size:0.9rem; color:var(--danger-color);">Falta Pagar (Este mês)</span><div style="font-size:1.5rem; font-weight:bold; color:var(--danger-color);">- R$ <?= number_format($totalPendencias, 2, ',', '.') ?></div></div>
                <div class="card" style="background:var(--accent-color); color:white;"><span style="font-size:0.9rem;">Saldo Livre</span><div style="font-size:1.5rem; font-weight:bold;">R$ <?= number_format($saldoLivre, 2, ',', '.') ?></div></div>
            </section>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <div>
                    <div class="recent-box" style="margin-bottom: 20px;">
                        <h3><i class="fa-solid fa-credit-card"></i> Meus Parcelamentos</h3>
                        <ul class="task-list">
                            <?php if(empty($parcelamentos)): ?><p style="color:#777;">Nenhum parcelamento ativo.</p><?php else: ?>
                                <?php foreach($parcelamentos as $parc): 
                                    $perc = ($parc->paid_amount / $parc->total_amount) * 100;
                                ?>
                                    <li style="flex-direction: column; align-items: flex-start; opacity: <?= $parc->is_finished ? '0.5' : '1' ?>">
                                        <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                                            <div>
                                                <strong style="display:block"><?= htmlspecialchars($parc->title) ?></strong>
                                                <small style="color:#aaa">Vence dia <?= $parc->due_day ?> | R$ <?= number_format($parc->installment_amount, 2, ',', '.') ?> /mês</small>
                                            </div>
                                            
                                            <div style="display:flex; gap:10px; align-items:center;">
                                                <span style="font-size: 0.9rem; font-weight:bold; color: var(--accent-color);">
                                                    <?= $parc->paid_installments ?>/<?= $parc->total_installments ?> pagas
                                                </span>
                                                
                                                <?php if(!$parc->is_finished): ?>
                                                    <button onclick="openPayInstModal(<?= $parc->id ?>, '<?= addslashes($parc->title) ?>', <?= $parc->installment_amount ?>)" style="background:none; border:1px solid var(--accent-color); color:var(--accent-color); cursor:pointer; padding: 4px 8px; border-radius: 4px; font-size:0.8rem;" title="Adiantar/Pagar Parcela">
                                                        <i class="fa-solid fa-check"></i> Pagar
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="toggleHist('hist-<?= $parc->id ?>')" style="background:none; border:none; color:#888; cursor:pointer;" title="Ver Histórico"><i class="fa-solid fa-clock-rotate-left"></i></button>
                                                <a href="index.php?page=financas&action=delete_installment&id=<?= $parc->id ?>" onclick="return confirm('Apagar parcelamento? As transações pagas continuarão no extrato.')" style="color:#666;"><i class="fa-solid fa-trash"></i></a>
                                            </div>
                                        </div>
                                        
                                        <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $perc ?>%;"></div></div>
                                        <div style="font-size: 0.7rem; color:#777; text-align:right; width:100%; margin-top:3px;">Falta R$ <?= number_format($parc->total_amount - $parc->paid_amount, 2, ',', '.') ?></div>

                                        <div id="hist-<?= $parc->id ?>" class="history-box hidden">
                                            <strong style="color:#ccc;">Histórico de Pagamentos:</strong>
                                            <?php if(empty($parc->history)): ?><div style="color:#777;">Nenhum pagamento registrado.</div><?php endif; ?>
                                            <?php foreach($parc->history as $h): ?>
                                                <div style="display:flex; justify-content:space-between; margin-top:5px; border-bottom: 1px solid #444; padding-bottom:3px;">
                                                    <span style="color:#aaa;"><?= date('d/m/y', strtotime($h->created_at)) ?> - <?= $h->description ?></span>
                                                    <span style="color:var(--success-color)">R$ <?= number_format($h->amount, 2, ',', '.') ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="recent-box">
                        <h3><i class="fa-solid fa-calendar-check"></i> Contas Fixas</h3>
                        <ul class="task-list">
                            <?php if(empty($fixas)): ?><p style="color:#777;">Nenhuma conta fixa.</p><?php else: ?>
                                <?php foreach($fixas as $conta): ?>
                                    <li style="display:flex; justify-content:space-between; align-items:center; opacity: <?= $conta->is_paid ? '0.5' : '1' ?>">
                                        <div><strong style="display:block"><?= htmlspecialchars($conta->title) ?></strong><small style="color:#777">Vence dia <?= $conta->day_of_month ?></small></div>
                                        <div style="display:flex; gap:10px; align-items:center;">
                                            <span style="font-weight:bold;">R$ <?= number_format($conta->amount, 2, ',', '.') ?></span>
                                            <?php if($conta->is_paid): ?><span style="color:var(--success-color);"><i class="fa-solid fa-circle-check"></i></span><?php else: ?>
                                                <form method="POST" action="index.php?page=financas" style="display:inline;">
                                                    <input type="hidden" name="action" value="add_transaction"><input type="hidden" name="type" value="expense">
                                                    <input type="hidden" name="description" value="<?= $conta->title ?>"><input type="hidden" name="amount" value="<?= $conta->amount ?>">
                                                    <button type="submit" style="background:none; border:1px solid #555; color:var(--text-primary); cursor:pointer;"><i class="fa-solid fa-check"></i></button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="index.php?page=financas&action=delete_fixed&id=<?= $conta->id ?>" onclick="return confirm('Apagar fixa?')" style="color:#666;"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="recent-box">
                    <h3><i class="fa-solid fa-receipt"></i> Extrato</h3>
                    <table class="simple-table" style="width:100%;">
                        <?php foreach($allTransactions as $t): ?>
                            <tr style="border-bottom:1px solid #333;">
                                <td style="padding:10px; color:#aaa; font-size:0.85rem;"><?= date('d/m', strtotime($t->created_at)) ?></td>
                                <td style="font-size:0.9rem;"><?= htmlspecialchars($t->description) ?></td>
                                <td style="color:<?= $t->type == 'income' ? 'var(--success-color)' : 'var(--danger-color)' ?>; text-align:right; font-weight:bold;">
                                    <?= $t->type == 'income' ? '+' : '-' ?> R$ <?= number_format($t->amount, 2, ',', '.') ?>
                                </td>
                                <td style="text-align:right;"><a href="index.php?page=financas&action=delete_transaction&id=<?= $t->id ?>" onclick="return confirm('Apagar?')" style="color:#555;"><i class="fa-solid fa-trash"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </main> 
    </div>
    
    <div id="financeModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><h3>Nova Transação</h3><button class="close-modal" onclick="closeModal('financeModal')">x</button></div><form method="POST"><input type="hidden" name="action" value="add_transaction"><div class="form-group"><label>Tipo</label><select name="type"><option value="income">Entrada</option><option value="expense">Saída</option></select></div><div class="form-group"><input type="text" name="description" placeholder="Ex: Salário, Mercado..." required></div><div class="form-group"><input type="number" step="0.01" name="amount" placeholder="Valor (R$)" required></div><button class="btn-save">Salvar</button></form></div></div>
    
    <div id="fixedModal" class="modal-overlay"><div class="modal-content"><div class="modal-header"><h3>Nova Conta Fixa</h3><button class="close-modal" onclick="closeModal('fixedModal')">x</button></div><form method="POST"><input type="hidden" name="action" value="add_fixed"><div class="form-group"><input type="text" name="title" placeholder="Ex: Aluguel, Internet..." required></div><div class="form-group"><input type="number" step="0.01" name="amount" placeholder="Valor (R$)" required></div><div class="form-group"><input type="number" name="day_of_month" placeholder="Dia do Vencimento (1-31)" required min="1" max="31"></div><button class="btn-save">Salvar</button></form></div></div>

    <div id="installmentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h3>Novo Parcelamento</h3><button class="close-modal" onclick="closeModal('installmentModal')">x</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_installment">
                <div class="form-group"><label>O que você comprou?</label><input type="text" name="title" placeholder="Ex: Monitor Dell, Celular..." required></div>
                <div class="form-group"><label>Valor Total (R$)</label><input type="number" step="0.01" name="total_amount" placeholder="Ex: 1500.00" required></div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;"><label>Quantas Vezes?</label><input type="number" name="total_installments" placeholder="Ex: 10" required min="2"></div>
                    <div class="form-group" style="flex:1;"><label>Dia Fatura</label><input type="number" name="due_day" placeholder="Ex: 5" required min="1" max="31"></div>
                </div>
                <button class="btn-save">Criar Parcelamento</button>
            </form>
        </div>
    </div>

    <div id="payInstModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h3>Pagar Parcela(s)</h3><button class="close-modal" onclick="closeModal('payInstModal')">x</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="pay_installment">
                <input type="hidden" name="installment_id" id="payInstId">
                <input type="hidden" name="title" id="payInstTitle">
                <input type="hidden" name="installment_amount" id="payInstAmount">
                
                <p style="color:#aaa; font-size:0.9rem; margin-bottom:15px;">Item: <strong id="payInstLabel" style="color:white;"></strong></p>
                
                <div class="form-group">
                    <label>Quantas parcelas quer pagar agora?</label>
                    <input type="number" name="qtd_parcelas" value="1" min="1" required>
                    <small style="color:#777;">Coloque 2 ou mais se quiser adiantar faturas.</small>
                </div>
                <button class="btn-save">Confirmar Pagamento</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        // Passa os dados do parcelamento pro Modal de pagamento
        function openPayInstModal(id, title, amount) {
            document.getElementById('payInstId').value = id;
            document.getElementById('payInstTitle').value = title;
            document.getElementById('payInstAmount').value = amount;
            document.getElementById('payInstLabel').innerText = title;
            openModal('payInstModal');
        }

        // Mostra/Esconde o histórico
        function toggleHist(id) {
            let el = document.getElementById(id);
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                el.style.display = 'block';
            } else {
                el.classList.add('hidden');
                el.style.display = 'none';
            }
        }
    </script>
</body>
</html>