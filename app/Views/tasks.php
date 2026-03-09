<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ButterPlan - Tarefas & Rotina</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/tasks.css">
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
                <a href="index.php?page=tarefas" class="active"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                <a href="index.php?page=relatorios"><i class="fa-solid fa-chart-pie"></i> Relatórios</a>
            </nav>
            <div class="logout">
                <a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <div class="header-title">
                    <h1>Projetos & Rotina</h1>
                    <p>Gerencie tarefas, recorrências e subtarefas.</p>
                </div>
            </header>

            <section style="margin-bottom: 2rem;">
                <form method="POST" action="index.php?page=tarefas" class="task-form-advanced">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="input-row">
                        <input type="text" name="title" class="input-task" placeholder="Nova Tarefa Principal (ex: Beber Água)" required autocomplete="off">
                        
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

                    <div class="input-row-bottom" style="flex-wrap: wrap; gap: 15px; align-items: center;">
                        <div class="date-group" style="display: flex; gap: 10px; align-items: center;">
                            <input type="date" name="due_date" class="input-date" value="<?= date('Y-m-d') ?>">
                            <input type="number" name="duration" class="input-date" placeholder="MIN" style="width:70px;">
                        </div>

                        <div class="time-group" style="display: flex; align-items: center; gap: 10px;">
                            <div class="form-check form-switch" style="display: flex; align-items: center; gap: 5px;">
                                <input class="form-check-input" type="checkbox" id="enable_time" style="cursor: pointer;">
                                <label class="form-check-label" for="enable_time" style="font-size: 0.85rem; color: #888; cursor: pointer;">Definir Horário</label>
                            </div>
                            <div id="time_field_wrapper" style="display: none;">
                                <input type="time" name="start_time" id="start_time" class="input-date" style="width: 110px;">
                            </div>
                        </div>

                        <div class="recurrence-wrapper">
                            <label class="checkbox-container">
                                <input type="checkbox" name="is_recurring" id="checkRecur" onchange="toggleDays()">
                                <span class="checkmark"></span>
                                <span class="label-text"><i class="fa-solid fa-repeat"></i> Repetir:</span>
                            </label>
                            <div id="daysSelector" class="days-selector hidden-days">
                                <label><input type="checkbox" name="days[]" value="1" checked> S</label>
                                <label><input type="checkbox" name="days[]" value="2" checked> T</label>
                                <label><input type="checkbox" name="days[]" value="3" checked> Q</label>
                                <label><input type="checkbox" name="days[]" value="4" checked> Q</label>
                                <label><input type="checkbox" name="days[]" value="5" checked> S</label>
                                <label><input type="checkbox" name="days[]" value="6"> S</label>
                                <label><input type="checkbox" name="days[]" value="0"> D</label>
                            </div>
                        </div>

                        <button type="submit" class="btn-add" style="margin-left: auto;">Criar Tarefa</button>
                    </div>
                </form>
            </section>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="recent-box">
                    <h3><i class="fa-solid fa-layer-group"></i> Em Andamento</h3>
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
                                            <span class="task-title" style="font-weight:600;"><?= htmlspecialchars($task->title) ?></span>
                                            <div class="task-meta">
                                                <span class="tag tag-default"><?= htmlspecialchars($task->category) ?></span>
                                                <?php if($task->duration): ?>
                                                    <span class="meta-date"><i class="fa-regular fa-clock"></i> <?= $task->duration ?>m</span>
                                                <?php endif; ?>
                                                <?php if($task->is_recurring): ?>
                                                    <span class="meta-date countdown-timer" data-due="<?= $task->due_date ?>" style="color: var(--accent-color);">
                                                        <i class="fa-solid fa-hourglass-half"></i> <span class="time-display">...</span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button onclick="openSubtaskModal(<?= $task->id ?>, '<?= addslashes($task->title) ?>')" class="btn-subtask"><i class="fa-solid fa-diagram-project"></i></button>
                                        <a href="index.php?page=tarefas&action=delete_task&id=<?= $task->id ?>" class="delete-btn"><i class="fa-solid fa-trash"></i></a>
                                    </div>
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
                <h3>Adicionar Item</h3>
                <button class="close-modal" onclick="closeModal('subtaskModal')">
    <i class="fa-solid fa-xmark"></i>
</button>
            </div>
            <form method="POST" action="index.php?page=tarefas">
                <input type="hidden" name="action" value="add_task">
                <input type="hidden" name="parent_id" id="parentIdInput"> 
                <div class="form-group"><label>Título</label><input type="text" name="title" required></div>
                <button type="submit" class="btn-save">Adicionar</button>
            </form>
        </div>
    </div>

    <script>
        // CONTROLE DO CAMPO DE HORÁRIO
        document.getElementById('enable_time').addEventListener('change', function() {
            const wrapper = document.getElementById('time_field_wrapper');
            const input = document.getElementById('start_time');
            if (this.checked) {
                wrapper.style.display = 'block';
                input.required = true;
            } else {
                wrapper.style.display = 'none';
                input.value = '';
                input.required = false;
            }
        });

        // MODAIS E RECORRÊNCIA
        function openSubtaskModal(id, title) {
            document.getElementById('parentIdInput').value = id;
            document.getElementById('subtaskModal').classList.add('active');
        }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        function toggleDays() {
            const selector = document.getElementById('daysSelector');
            selector.classList.toggle('hidden-days', !document.getElementById('checkRecur').checked);
        }

        // CRONÔMETRO
        function updateTimers() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const parts = timer.getAttribute('data-due').split('-');
                const deadline = new Date(parts[0], parts[1]-1, parts[2], 23, 59, 59).getTime();
                const now = new Date().getTime();
                const diff = deadline - now;
                const display = timer.querySelector('.time-display');

                if (diff <= 0) {
                    display.innerText = "Expirado";
                } else {
                    const h = Math.floor((diff % 86400000) / 3600000);
                    const m = Math.floor((diff % 3600000) / 60000);
                    const s = Math.floor((diff % 60000) / 1000);
                    display.innerText = `${h}h ${m}m ${s}s`;
                }
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();
    </script>
</body>
</html>