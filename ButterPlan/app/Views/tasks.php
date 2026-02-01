<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ButterPlan - Rotina Avançada</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/tasks.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo"><img src="assets/img/logo.png" alt="Butterlogo" class="logo-img"></div>
            <nav>
                <a href="index.php?page=home"><i class="fa-solid fa-house"></i> Visão Geral</a>
                <a href="index.php?page=financas"><i class="fa-solid fa-wallet"></i> Finanças</a>
                <a href="index.php?page=tarefas" class="active"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                <a href="index.php?page=relatorios"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
            </nav>
            <div class="logout"><a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <div class="header-title">
                    <h1>Projetos & Rotina</h1>
                    <p>Gerencie tarefas complexas e subtarefas.</p>
                </div>
            </header>

            <section style="margin-bottom: 2rem;">
                <form method="POST" action="index.php?page=tarefas" class="task-form-advanced">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="input-row">
                        <input type="text" name="title" class="input-task" placeholder="Nova Tarefa Principal (ex: Estudar Inglês)" required autocomplete="off">
                        
                        <input list="categorias-list" name="category" class="input-date" placeholder="Categoria" style="width:150px;">
                        <datalist id="categorias-list">
                            <?php foreach($categoriasDB as $cat): ?>
                                <option value="<?= $cat ?>">
                            <?php endforeach; ?>
                            <option value="Trabalho"><option value="Estudos"><option value="Financeiro">
                        </datalist>

                        <select name="priority" class="select-style">
                            <option value="medium">Normal</option>
                            <option value="high">Urgente</option>
                            <option value="low">Baixa</option>
                        </select>
                    </div>

                    <div class="input-row" style="margin-top:10px;">
                        <textarea name="description" class="input-desc" placeholder="Adicionar detalhes, observações ou links (opcional)..." rows="1"></textarea>
                    </div>


                        <div class="input-row-bottom" style="flex-wrap: wrap;">
                        <div class="date-group">
                            <input type="date" name="due_date" class="input-date" value="<?= date('Y-m-d') ?>">
                            <input type="number" name="duration" class="input-date" placeholder="Min" style="width:70px;">
                        </div>

                        <div class="recurrence-wrapper">

                            <label class="checkbox-container">
                                <input type="checkbox" name="is_recurring" id="checkRecur" onchange="toggleDays()">
                                <span class="checkmark"></span>
                                <span class="label-text"><i class="fa-solid fa-repeat"></i> Recorrente</span>
                            </label>

                            <div id="daysSelector" class="days-selector hidden-days">
                                <span style="font-size:0.7rem; margin-right:5px; color:#888;">Dias:</span>
                                <label><input type="checkbox" name="days[]" value="1" checked> S</label>
                                <label><input type="checkbox" name="days[]" value="2" checked> T</label>
                                <label><input type="checkbox" name="days[]" value="3" checked> Q</label>
                                <label><input type="checkbox" name="days[]" value="4" checked> Q</label>
                                <label><input type="checkbox" name="days[]" value="5" checked> S</label>
                                <label><input type="checkbox" name="days[]" value="6"> S</label>
                                <label><input type="checkbox" name="days[]" value="0"> D</label>
                            </div>
                        </div>

                        <button type="submit" class="btn-add">Criar Tarefa</button>
                    </div>

                    <script>
                        function toggleDays() {
                            const check = document.getElementById('checkRecur');
                            const selector = document.getElementById('daysSelector');
                            if (check.checked) {
                                selector.classList.remove('hidden-days');
                            } else {
                                selector.classList.add('hidden-days');
                            }
                        }
                    </script>
                </form>
            </section>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <div class="recent-box">
                    <h3><i class="fa-solid fa-layer-group"></i> Em Andamento</h3>

<?php if(!empty($futuras)): ?>
                <div class="recent-box" style="border: 1px dashed #444; background: rgba(0,0,0,0.2);">
                    <h3 style="color: #888; font-size: 1rem;">
                        <i class="fa-regular fa-calendar"></i> Agendado para o Futuro
                    </h3>
                    
                    <ul class="task-list">
                        <?php foreach($futuras as $task): ?>
                            <li class="task-item" style="flex-direction: column; align-items: flex-start; opacity: 0.7;">
                                <div style="display:flex; width:100%; align-items:center;">
                                    <span style="margin-right:15px; color:#555;"><i class="fa-solid fa-clock"></i></span>
                                    
                                    <div class="task-info">
                                        <span class="task-title" style="color:#aaa;"><?= htmlspecialchars($task->title) ?></span>
                                        <div class="task-meta">
                                            <span class="tag" style="background:#333; color:#fff;">
                                                <?= date('d/m', strtotime($task->due_date)) ?> - <?= date('l', strtotime($task->due_date)) ?>
                                            </span>
                                            
                                            <span class="tag tag-default"><?= $task->category ?></span>
                                            
                                            <?php if($task->is_recurring): ?>
                                                <span class="meta-icon"><i class="fa-solid fa-repeat"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <a href="index.php?page=tarefas&action=delete_task&id=<?= $task->id ?>" class="delete-btn" style="opacity:0.5;">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                                
                                <?php if(!empty($task->subtasks)): ?>
                                    <div class="subtask-container" style="border-left-color: #444;">
                                        <?php foreach($task->subtasks as $sub): ?>
                                            <div class="subtask-item" style="color: #666;">
                                                <i class="fa-solid fa-minus" style="margin-right:10px; font-size:0.7rem;"></i>
                                                <?= htmlspecialchars($sub->title) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>








                    
                    <?php if(empty($pendentes)): ?>
                        <p style="color:#777; padding:20px;">Tudo feito por hoje!</p>
                    <?php else: ?>
                        <ul class="task-list">
                            <?php foreach($pendentes as $task): ?>
                                <li class="task-item pending" style="flex-direction: column; align-items: flex-start;">
                                    
                                    <div style="display:flex; width:100%; align-items:center;">
                                        <a href="index.php?page=tarefas&action=toggle_task&id=<?= $task->id ?>" class="check-btn">
                                            <i class="fa-regular fa-square"></i>
                                        </a>
                                        
                                        <div class="task-info">
                                            <span class="task-title" style="font-weight:600; font-size:1rem;"><?= htmlspecialchars($task->title) ?></span>
                                            <div class="task-meta">
                                                <span class="tag tag-default"><?= $task->category ?></span>
                                                <?php if($task->duration): ?>
                                                    <span class="meta-date"><i class="fa-regular fa-clock"></i> <?= $task->duration ?>m</span>
                                                <?php endif; ?>
                                                
                                                <?php if(!empty($task->description)): ?>
                                                    <span class="meta-icon btn-toggle-desc" onclick="toggleDesc('desc-<?= $task->id ?>')" title="Ver Descrição" style="cursor:pointer; color:var(--accent-color);">
                                                        <i class="fa-solid fa-align-left"></i>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if($task->is_recurring): ?>
                                                    <span class="meta-icon"><i class="fa-solid fa-repeat"></i></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <button onclick="openSubtaskModal(<?= $task->id ?>, '<?= addslashes($task->title) ?>')" class="btn-subtask" title="Adicionar Subtarefa">
                                            <i class="fa-solid fa-diagram-project"></i>
                                        </button>

                                        <a href="index.php?page=tarefas&action=delete_task&id=<?=  $task->id ?>" class="delete-btn">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <?php if(!empty($task->description)): ?>
                                        <div id="desc-<?= $task->id ?>" class="task-description hidden">
                                            <?= nl2br(htmlspecialchars($task->description)) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($task->subtasks)): ?>
                                        <div class="subtask-container">
                                            <?php foreach($task->subtasks as $sub): ?>
                                                <div class="subtask-wrapper">
                                                    <div class="subtask-item <?= $sub->status == 'done' ? 'done' : '' ?>">
                                                        <a href="index.php?page=tarefas&action=toggle_task&id=<?= $sub->id ?>" class="check-mini">
                                                            <i class="fa-solid <?= $sub->status == 'done' ? 'fa-check' : 'fa-minus' ?>"></i>
                                                        </a>
                                                        <div style="flex:1;">
                                                            <span style="font-size:0.9rem;"><?= htmlspecialchars($sub->title) ?></span>
                                                            
                                                            <?php if(!empty($sub->description)): ?>
                                                                <i class="fa-solid fa-align-left" onclick="toggleDesc('desc-<?= $sub->id ?>')" style="font-size:0.7rem; color:var(--text-secondary); cursor:pointer; margin-left:5px;" title="Ver Detalhes"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <?php if($sub->duration): ?>
                                                            <span style="font-size:0.75rem; color:#666; margin-right:10px;"><?= $sub->duration ?>m</span>
                                                        <?php endif; ?>
                                                        
                                                        <a href="index.php?page=tarefas&action=delete_task&id=<?= $sub->id ?>" class="delete-mini"><i class="fa-solid fa-times"></i></a>
                                                    </div>
                                                    
                                                    <?php if(!empty($sub->description)): ?>
                                                        <div id="desc-<?= $sub->id ?>" class="subtask-description hidden">
                                                            <?= nl2br(htmlspecialchars($sub->description)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php 
                                                $totalSub = count($task->subtasks);
                                                $doneSub = count(array_filter($task->subtasks, fn($s) => $s->status == 'done'));
                                                $perc = ($doneSub / $totalSub) * 100;
                                            ?>
                                            <div class="progress-line"><div class="fill" style="width:<?= $perc ?>%"></div></div>
                                        </div>
                                    <?php endif; ?>

                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="recent-box" style="opacity: 0.8;">
                    <h3><i class="fa-solid fa-check-double"></i> Concluídas</h3>
                    <ul class="task-list">
                        <?php foreach($concluidas as $task): ?>
                            <li class="task-item done">
                                <a href="index.php?page=tarefas&action=toggle_task&id=<?= $task->id ?>" class="check-btn">
                                    <i class="fa-solid fa-square-check" style="color:var(--success-color)"></i>
                                </a>
                                <span class="task-title"><?= htmlspecialchars($task->title) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <div id="subtaskModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Adicionar Subtarefa</h3>
                <button class="close-modal" onclick="closeModal('subtaskModal')">&times;</button>
            </div>
            <p style="color:#888; margin-bottom:15px; font-size:0.9rem;">Para: <strong id="parentTaskTitle" style="color:white;"></strong></p>
            
            <form method="POST" action="index.php?page=tarefas">
                <input type="hidden" name="action" value="add_task">
                <input type="hidden" name="parent_id" id="parentIdInput"> 
                <input type="hidden" name="priority" value="medium">
                <input type="hidden" name="category" value="Subtarefa">
                
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="title" placeholder="Ex: Ler capítulo 1..." required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Descrição (Opcional)</label>
                    <textarea name="description" placeholder="Detalhes extras..." style="width:100%; padding:10px; background:var(--bg-color); border:1px solid #444; color:white; border-radius:6px; resize:vertical;" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label>Duração (min)</label>
                    <input type="number" name="duration" placeholder="Ex: 30">
                </div>

                <button type="submit" class="btn-save">Adicionar Item</button>
            </form>
        </div>
    </div>

    <script>
        function openSubtaskModal(parentId, parentTitle) {
            document.getElementById('parentIdInput').value = parentId;
            document.getElementById('parentTaskTitle').innerText = parentTitle;
            document.getElementById('subtaskModal').classList.add('active');
        }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        window.onclick = function(e) { if(e.target.classList.contains('modal-overlay')) e.target.classList.remove('active'); }
        
        // Função para mostrar/esconder descrição
        function toggleDesc(id) {
            var el = document.getElementById(id);
            if(el.classList.contains('hidden')) {
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