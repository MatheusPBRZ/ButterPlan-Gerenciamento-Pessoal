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
<header style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap:10px;">
<div class="header-title">
<h1>Gestão Financeira</h1>
<p>Controle absoluto do meu dinheiro.</p>
</div>
<div style="display:flex; gap:10px;">
<button onclick="openModal('installmentModal')
" class="btn-save" style="width:auto; margin:0; padding:15px 15px; background:#2c3e50;">
<i class="fa-solid fa-credit-card"></i> Parcelamento</button>
<button onclick="openModal('fixedModal')" class="btn-save" style="width:auto; margin:0; padding:15px 15px; background:#444;">
    <i class="fa-solid fa-repeat"></i> Conta Fixa</button>
<button onclick="openModal('financeModal')" class="btn-save" style="width:auto; margin:0; padding:15px 15px;">
    <i class="fa-solid fa-plus"></i> Transação</button>
</div>
</header>

<section class="stats-grid" style="margin-bottom: 2rem;">
<div class="card">
<span style="font-size:0.9rem; opacity:0.7">Saldo no Banco</span>
<div style="font-size:1.5rem; font-weight:bold;">R$ <?= number_format($saldoReal ?? 0, 2, ',', '.') ?></div>
</div>
<div class="card" style="border:1px solid var(--danger-color); background:rgba(247, 90, 104, 0.05);">
<span style="font-size:0.9rem; color:var(--danger-color);">Falta Pagar (Este mês)</span>
<div style="font-size:1.5rem; font-weight:bold; color:var(--danger-color);">- R$ <?= number_format($totalPendencias ?? 0, 2, ',', '.') ?></div>
</div>
                <div class="card" style="background:var(--accent-color); color:white;">
                    <span style="font-size:0.9rem;">Saldo Livre</span>
                    <div style="font-size:1.5rem; font-weight:bold;">R$ <?= number_format($saldoLivre ?? 0, 2, ',', '.') ?></div>
                </div>
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
<li style="flex-direction: column; align-items: flex-start; opacity: 
<?= $parc->is_finished ? '0.5' : '1' ?>">
<div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
<div>
<strong style="display:block"><?= htmlspecialchars($parc->title) ?></strong>
<small style="color:#aaa">Vence dia <?= $parc->due_day ?>
 | R$ <?= number_format($parc->installment_amount, 2, ',', '.') ?>/mês</small>
</div>    
<div style="display:flex; gap:10px; align-items:center;">
<span style="font-size: 0.9rem; font-weight:bold; color: var(--accent-color);">
<?= $parc->paid_installments ?>/<?= $parc->total_installments ?> pagas</span>


<?php if(!$parc->is_finished): ?>
<button onclick="openPayInstModal(<?= $parc->id ?>, '<?= addslashes($parc->title) ?>',
<?= $parc->installment_amount ?>)" 
style="background:none; border:1px solid var(--accent-color); color:var(--accent-color); cursor:pointer; padding: 4px 8px; border-radius: 4px; font-size:0.8rem;">
<i class="fa-solid fa-check"></i> Pagar</button>
<?php endif; ?>
<button onclick="toggleHist('hist-<?= $parc->id ?>')" style="background:none; border:none; color:#888; cursor:pointer;"><i class="fa-solid fa-clock-rotate-left"></i></button>
<a href="index.php?page=financas&action=delete_installment&id=<?= $parc->id ?>"
 onclick="return confirm('Apagar parcelamento?')" 

 style="color:#666;"><i class="fa-solid fa-trash"></i></a>

</div>
</div>

<div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $perc ?>%;">

</div></div>
                                    
<div style="font-size: 0.75rem; color:#888; text-align:right; width:100%; margin-top:5px;">

Falta R$ <?= number_format($parc->total_amount - $parc->paid_amount, 2, ',', '.') ?>

</div>
          
<div id="hist-<?= $parc->id ?>" 

style="display:none;
width:100%;
margin-top:10px;
padding:10px;
background:rgba(0,0,0,0.2);
border-radius:5px;">

    <?php foreach($parc->history as $h): ?>
<div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:5px;">
<span><?= date('d/m/y', strtotime($h->created_at)) ?></span>
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
<h3>
<i class="fa-solid fa-calendar-check">
</i> Contas Fixas</h3>

<ul class="task-list">
    <?php foreach($fixas as $conta): ?>
        <li class="task-item <?= $conta->is_paid ? 'done' : '' ?>" style="justify-content: space-between;">
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if($conta->is_paid): ?>
                    <i class="fa-solid fa-circle-check" style="color: var(--success-color); font-size: 1.2rem;"></i>
                <?php else: ?>
    <i class="fa-regular fa-circle" style="color: #666; font-size: 1.2rem;"></i>
<?php endif; ?>

    <div class="task-info">
<span class="task-title" <?= $conta->is_paid ? 
'style="text-decoration: line-through; color: #888;"' : '' ?>>
                        <?= htmlspecialchars($conta->title) ?>
   


                    <span class="meta-date">
                    Vence dia <?= $conta->day_of_month ?>
                </span>
                </div>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <span style="font-weight: 600; <?= $conta->is_paid ? 'color: #888;' : '' ?>">
                    R$ <?= number_format($conta->amount, 2, ',', '.') ?>
                </span>

                <?php if(!$conta->is_paid): ?>
                    <form method="POST" action="index.php?page=financas" style="margin:0;">
        <input type="hidden" name="action" value="add_transaction">
        <input type="hidden" name="type" value="expense">
        <input type="hidden" name="description" value="<?= htmlspecialchars($conta->title) ?>">
        <input type="hidden" name="amount" value="<?= $conta->amount ?>">
        <input type="hidden" name="category" value="Geral"> 
    <button type="submit" class="btn-subtask" style="color: var(--success-color);" title="Marcar como Pago">
            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                <?php endif; ?>

<a href="index.php?page=financas&action=delete_fixed&id=<?= $conta->id ?>
" class="delete-btn" title="Excluir Conta Fixa">
                    <i class="fa-solid fa-trash"></i>
                </a>
            </div>
            
        </li>
    <?php endforeach; ?>
</ul>
                    </div>
                </div>

    <div class="recent-box">
    <h3><i class="fa-solid fa-receipt"></i> Extrato Recente</h3>
    <table class="simple-table" style="width:100%;">
    <?php foreach($allTransactions as $t): ?>
    <tr style="border-bottom:1px solid #333;">
    <td style="padding:10px; color:#aaa; font-size:0.8rem;"><?= date('d/m', strtotime($t->created_at)) ?>
</td>
    <td>
<span style="display:block; font-size:0.9rem;"><?= htmlspecialchars($t->description) ?></span>
<small style="color:#777; font-size:0.7rem;"><?= htmlspecialchars($t->category ?? 'Geral') ?></small>
    </td>
   <td style="color:<?= $t->type == 'income' ? 'var(--success-color)' : 'var(--danger-color)' ?>; 
   text-align:right; font-weight:bold;">
    <?= $t->type == 'income' ? '+' : '-' ?> R$ <?= number_format($t->amount, 2, ',', '.') ?>
                                </td>
<td style="text-align:right;"><a href="index.php?page=financas&action=delete_transaction&id=<?=
 $t->id ?>" style="color:#555;"><i class="fa-solid fa-trash">
</i></a>
</td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="financeModal" class="modal-overlay">
        <div class="modal-content">
<div class="modal-header"><h3>Nova Transação</h3><button class="close-modal"onclick="closeModal('financeModal')">
    <i class="fa-solid fa-xmark"></i>
</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_transaction">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="type" class="form-select">
                        <option value="income">Entrada (Ganhei)</option>
                        <option value="expense" selected>Saída (Gastei)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category" class="form-select">
                        <option value="Geral">Geral</option>
                        <option value="Alimentação">Alimentação</option>
                        <option value="Tecnologia">Tecnologia</option>
                        <option value="Transporte">Transporte</option>
                        <option value="Lazer">Lazer</option>
                        <option value="Saúde">Saúde</option>
                    </select>
                </div>
<div class="form-group"><input type="text" name="description" placeholder="Ex: Mercado, Salário..." required>
</div>
<div class="form-group"><input type="number" step="0.01" name="amount" placeholder="Valor (R$)" required>
</div>
<button class="btn-save">Salvar Transação</button>
            </form>
        </div>
    </div>

    <div id="fixedModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h3>Nova Conta Fixa</h3><button class="close-modal" onclick="closeModal('fixedModal')">
    <i class="fa-solid fa-xmark"></i>
</button></div>
            <form method="POST">
<input type="hidden" name="action" value="add_fixed">
<div class="form-group"><input type="text" name="title" placeholder="Ex: Aluguel, Internet..." required>
</div>
<div class="form-group"><input type="number" step="0.01" name="amount" placeholder="Valor (R$)" required>
</div>
<div class="form-group">
<input type="number" name="day_of_month" placeholder="Dia do Vencimento (1-31)" required min="1" max="31">
</div>
<button class="btn-save">Salvar Conta Fixa</button>
            </form>
        </div>
    </div>

    <div id="installmentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h3>Novo Parcelamento</h3><button class="close-modal" onclick="closeModal('installmentModal')">
    <i class="fa-solid fa-xmark"></i>
</button></div>
            <form method="POST">
        <input type="hidden" name="action" value="add_installment">
<div class="form-group"><label>Item Comprado</label>
<input type="text" name="title" placeholder="Ex: Monitor, Celular..." required></div>
<div class="form-group"><label>Valor Total (R$)</label>
<input type="number" step="0.01" name="total_amount" required></div>
<div style="display:flex; gap:10px;">
<div class="form-group" style="flex:1;"><label>Vezes</label>
<input type="number" name="total_installments" required min="2"></div>
<div class="form-group" style="flex:1;"><label>Dia Venc.</label>
<input type="number" name="due_day" required min="1" max="31"></div>
</div>
<button class="btn-save">Criar Parcelamento</button>
</form>
        </div>
    </div>

    <div id="payInstModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h3>Pagar Parcela</h3>
            <button class="close-modal" onclick="closeModal('payInstModal')">
    <i class="fa-solid fa-xmark"></i>
</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="pay_installment">
                <input type="hidden" name="installment_id" id="payInstId">
                <input type="hidden" name="title" id="payInstTitle">
                <input type="hidden" name="installment_amount" id="payInstAmount">
                <p>Item: <strong id="payInstLabel"></strong></p>
                <div class="form-group">
                    <label>Quantas parcelas pagar agora?</label>
                    <input type="number" name="qtd_parcelas" value="1" min="1" required>
                </div>
                <button class="btn-save">Confirmar Pagamento</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        function openPayInstModal(id, title, amount) {
            document.getElementById('payInstId').value = id;
            document.getElementById('payInstTitle').value = title;
            document.getElementById('payInstAmount').value = amount;
            document.getElementById('payInstLabel').innerText = title;
            openModal('payInstModal');
        }
        function toggleHist(id) {
            let el = document.getElementById(id);
            el.style.display = (el.style.display === 'none') ? 'block' : 'none';
        }
    </script>
</body>
</html>