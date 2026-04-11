<?php
// /public/index.php

// Define o caminho base
define('BASE_PATH', __DIR__ . '/../');
require BASE_PATH . 'app/Config/Database.php';

// 👇 CARREGA A BIBLIOTECA DO GOOGLE E DO COMPOSER 👇
require BASE_PATH . 'vendor/autoload.php';
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use App\Config\Database;

// Pega a página atual (se não tiver, vai pra home)
$page = $_GET['page'] ?? 'home';

try {
    $pdo = Database::getConnection();

    // =========================================================
    // GLOBAL: SISTEMA DE ROTINA (CICLO POR DIA DA SEMANA)
    // =========================================================
    date_default_timezone_set('America/Sao_Paulo');
    $hoje = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE is_recurring = 1 AND due_date < ?");
    $stmt->execute([$hoje]);
    $tarefasVencidas = $stmt->fetchAll();

    foreach ($tarefasVencidas as $t) {
        $novaDueDate = date('Y-m-d'); 

        $check = $pdo->prepare("SELECT count(*) as total FROM tasks WHERE title = ? AND category = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $check->execute([$t->title, $t->category]);
        
        if ($check->fetch()->total == 0) {
            $ins = $pdo->prepare("INSERT INTO tasks (title, description, priority, category, is_recurring, recurrence_days, due_date, duration, parent_id, status) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, 'pending')");
            $ins->execute([
                $t->title, 
                $t->description, 
                $t->priority, 
                $t->category, 
                $t->recurrence_days,
                $novaDueDate,
                $t->duration,
                $t->parent_id
            ]);

            $statusFinal = ($t->status == 'pending') ? 'expired' : 'done';
            $upd = $pdo->prepare("UPDATE tasks SET is_recurring = 0, status = ? WHERE id = ?");
            $upd->execute([$statusFinal, $t->id]);
        }
    }

    // =========================================================
    // =========================================================
    // ROTA 1: FINANÇAS
    // =========================================================
    if ($page == 'financas') {
        
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'delete_transaction' && isset($_GET['id'])) {
                $pdo->prepare("DELETE FROM transactions WHERE id = ?")->execute([$_GET['id']]);
                header('Location: index.php?page=financas'); exit;
            }
            if ($_GET['action'] == 'delete_fixed' && isset($_GET['id'])) {
                $pdo->prepare("DELETE FROM fixed_expenses WHERE id = ?")->execute([$_GET['id']]);
                header('Location: index.php?page=financas'); exit;
            }
            if ($_GET['action'] == 'delete_installment' && isset($_GET['id'])) {
                $pdo->prepare("UPDATE transactions SET installment_id = NULL WHERE installment_id = ?")->execute([$_GET['id']]);
                $pdo->prepare("DELETE FROM installments WHERE id = ?")->execute([$_GET['id']]);
                header('Location: index.php?page=financas'); exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] == 'add_transaction') {
                $category = !empty($_POST['category']) ? $_POST['category'] : 'Geral';
                $pdo->prepare("INSERT INTO transactions (type, description, amount, category) VALUES (?, ?, ?, ?)")
                    ->execute([$_POST['type'], $_POST['description'], (float)$_POST['amount'], $category]);
            }
            
            if (isset($_POST['action']) && $_POST['action'] == 'add_fixed') {
                $pdo->prepare("INSERT INTO fixed_expenses (title, amount, day_of_month) VALUES (?, ?, ?)")
                    ->execute([$_POST['title'], (float)$_POST['amount'], (int)$_POST['day_of_month']]);
            }

            if (isset($_POST['action']) && $_POST['action'] == 'add_installment') {
                $title = $_POST['title'];
                $total_amount = (float)$_POST['total_amount'];
                $total_inst = (int)$_POST['total_installments'];
                $due_day = (int)$_POST['due_day'];
                $inst_amount = $total_amount / $total_inst;

                $pdo->prepare("INSERT INTO installments (title, total_amount, total_installments, installment_amount, due_day) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$title, $total_amount, $total_inst, $inst_amount, $due_day]);
            }

            if (isset($_POST['action']) && $_POST['action'] == 'pay_installment') {
                $inst_id = (int)$_POST['installment_id'];
                $qtd_parcelas = (int)$_POST['qtd_parcelas'];
                $title = $_POST['title'];
                $inst_amount = (float)$_POST['installment_amount'];
                $total_pago = $inst_amount * $qtd_parcelas;
                $desc = "Parcela: $title ($qtd_parcelas" . "x)";

                $pdo->prepare("INSERT INTO transactions (type, description, amount, installment_id) VALUES ('expense', ?, ?, ?)")
                    ->execute([$desc, $total_pago, $inst_id]);
            }
            header('Location: index.php?page=financas'); exit;
        }

        // --- 👇 AS CONSULTAS QUE FALTAVAM VOLTARAM AQUI 👇 ---
        $allTransactions = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC")->fetchAll();
        $resumo = $pdo->query("SELECT (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income') as entradas, (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense') as saidas")->fetch();
        $saldoReal = $resumo->entradas - $resumo->saidas;
        
        $totalPendencias = 0;

        // 1. Processa Fixas (Traz do Banco)
        $fixas = $pdo->query("SELECT * FROM fixed_expenses ORDER BY day_of_month ASC")->fetchAll();
        foreach ($fixas as $key => $conta) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as pagou FROM transactions WHERE type = 'expense' AND description = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $stmt->execute([$conta->title]);
            $jaPagou = $stmt->fetch()->pagou > 0;
            $fixas[$key]->is_paid = $jaPagou; 
            if (!$jaPagou) $totalPendencias += $conta->amount;
        }

        // 2. Processa Parcelamentos (Traz do Banco e calcula progresso)
        $parcelamentos = $pdo->query("SELECT * FROM installments ORDER BY created_at DESC")->fetchAll();
        foreach ($parcelamentos as $key => $parc) {
            $stmt = $pdo->prepare("SELECT SUM(amount) as pago FROM transactions WHERE installment_id = ?");
            $stmt->execute([$parc->id]);
            $valorPago = $stmt->fetch()->pago ?? 0;
            
            $parcelasPagas = floor($valorPago / $parc->installment_amount);
            
            $parcelamentos[$key]->paid_amount = $valorPago;
            $parcelamentos[$key]->paid_installments = $parcelasPagas;
            $parcelamentos[$key]->is_finished = ($valorPago >= $parc->total_amount);

            $stmtHist = $pdo->prepare("SELECT * FROM transactions WHERE installment_id = ? ORDER BY created_at DESC");
            $stmtHist->execute([$parc->id]);
            $parcelamentos[$key]->history = $stmtHist->fetchAll();

            if (!$parcelamentos[$key]->is_finished) {
                $stmtMes = $pdo->prepare("SELECT COUNT(*) as pagou_mes FROM transactions WHERE installment_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
                $stmtMes->execute([$parc->id]);
                if ($stmtMes->fetch()->pagou_mes == 0) {
                    $totalPendencias += $parc->installment_amount;
                }
            }
        }

        $saldoLivre = $saldoReal - $totalPendencias;

        require BASE_PATH . 'app/Views/finance.php';
    }

    // =========================================================
    // =========================================================
    // ROTA 2: TAREFAS
    // =========================================================
    elseif ($page == 'tarefas') {
        // --- PROCESSO DE ADIÇÃO (POST) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_task') {
            $title = $_POST['title'];
            $priority = $_POST['priority'];
            $category = $_POST['category'];
            $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
            $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
            $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : NULL;
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');

            if (!empty($title)) {
                $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, category, is_recurring, recurrence_days, due_date, start_time, duration, parent_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$title, $description, $priority, $category, $is_recurring, null, $due_date, $start_time, $duration, $parent_id]);

                try {
                    $client = new Client();
                    $client->setApplicationName('ButterPlan');
                    $client->setScopes(Calendar::CALENDAR_EVENTS);
                    $client->setAuthConfig(BASE_PATH . 'keys/credentials.json'); 
                    $client->setAccessType('offline');
                    $tokenPath = BASE_PATH . 'token.json';
                    
                    if (file_exists($tokenPath)) {
                        $accessToken = json_decode(file_get_contents($tokenPath), true);
                        $client->setAccessToken($accessToken);
                        if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
                            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                        }
                        $service = new Calendar($client);
                        $horaParaGoogle = $start_time ? $start_time . ":00" : "09:00:00";
                        $dataInicioISO = date('Y-m-d\TH:i:sP', strtotime("$due_date $horaParaGoogle"));
                        $minutosDuracao = $duration ? $duration : 60; 
                        $dataFimISO = date('Y-m-d\TH:i:sP', strtotime("$dataInicioISO +$minutosDuracao minutes"));

                       // 1. Mapeamento de dias (PHP 0-6 para Google SU-SA)
$mapDias = [0 => 'SU', 1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA'];
$diasSelecionados = [];

if ($is_recurring && !empty($_POST['days'])) {
    foreach ($_POST['days'] as $diaNumero) {
        if (isset($mapDias[$diaNumero])) {
            $diasSelecionados[] = $mapDias[$diaNumero];
        }
    }
}

// 2. Criar a string RRULE
$recurrence = [];
if (!empty($diasSelecionados)) {
    $recurrence = ["RRULE:FREQ=WEEKLY;BYDAY=" . implode(',', $diasSelecionados)];
}

// 3. Montar o Evento para o Google com a recorrência
$event = new Event([
    'summary' => 'ButterPlan: ' . $title,
    'description' => ($description ?? '') . "\n\n🚨 Prioridade: " . ucfirst($priority),
    'start' => ['dateTime' => $dataInicioISO, 'timeZone' => 'America/Sao_Paulo'],
    'end' => ['dateTime' => $dataFimISO, 'timeZone' => 'America/Sao_Paulo'],
    'recurrence' => $recurrence // 👈 A mágica acontece aqui!
]);

                        // 1. Envia para o Google e guarda a resposta
                        $createdEvent = $service->events->insert('primary', $event);
                        
                        // 2. Pega o ID gerado lá na nuvem
                        $googleEventId = $createdEvent->getId();
                        
                        // 3. Pega o ID da tarefa que acabamos de salvar no MySQL
                        $localTaskId = $pdo->lastInsertId();
                        
                        // 4. Atualiza o MySQL salvando a conexão entre os dois
                        $stmtUpdate = $pdo->prepare("UPDATE tasks SET google_event_id = ? WHERE id = ?");
                        $stmtUpdate->execute([$googleEventId, $localTaskId]);
                    }
                } catch (Exception $e) {
                    error_log("Erro no Google Calendar: " . $e->getMessage());
                }
            }
            header('Location: index.php?page=tarefas'); exit;
        }
// 👇 ADICIONE O BLOCO DE EDIÇÃO AQUI 👇
        // --- PROCESSO DE EDIÇÃO DE TAREFA (POST) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_task') {
            $id = (int)$_POST['task_id'];
            $title = $_POST['title'];
            $priority = $_POST['priority'];
            $category = $_POST['category'];
            $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
            $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
            $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : NULL;
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');

            $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, priority=?, category=?, due_date=?, start_time=?, duration=? WHERE id=?");
            $stmt->execute([$title, $description, $priority, $category, $due_date, $start_time, $duration, $id]);
            
            header('Location: index.php?page=tarefas'); exit;
        }
        // 👆 FIM DO BLOCO DE EDIÇÃO 👆
        
        // --- AÇÕES VIA GET (TOGGLE E DELETE) ---
        // --- PROCESSO DE DELETAR TAREFA ---
        if (isset($_GET['action']) && $_GET['action'] == 'delete_task' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            // 1. Busca a tarefa para ver se ela tem um ID do Google
            $stmt = $pdo->prepare("SELECT google_event_id FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $taskToDelete = $stmt->fetch(PDO::FETCH_OBJ);

            // 2. Se tiver o ID, conecta no Google e apaga lá primeiro
            if ($taskToDelete && !empty($taskToDelete->google_event_id)) {
                try {
                    $client = new Google\Client();
                    $client->setAuthConfig(BASE_PATH . 'keys/credentials.json');
                    $tokenPath = BASE_PATH . 'token.json';
                    if (file_exists($tokenPath)) {
                        $accessToken = json_decode(file_get_contents($tokenPath), true);
                        $client->setAccessToken($accessToken);
                        $service = new Google\Service\Calendar($client);
                        
                        // O comando que deleta o evento principal (e suas repetições)
                        $service->events->delete('primary', $taskToDelete->google_event_id);
                    }
                } catch (Exception $e) {
                    error_log("Erro ao excluir do Google Calendar: " . $e->getMessage());
                    // Segue o jogo mesmo se der erro no Google, para não travar o sistema local
                }
            }

            // 3. Depois de avisar a nuvem, apaga do banco de dados local
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: index.php?page=tarefas'); exit;
        }

        // --- 👇 CONSULTAS PARA ALIMENTAR A VIEW (ESSENCIAL) 👇 ---
        $hoje = date('Y-m-d');
        
        // Pendentes de hoje ou atrasadas
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'pending' AND parent_id IS NULL AND due_date <= ? ORDER BY due_date ASC, FIELD(priority, 'high', 'medium', 'low')");
        $stmt->execute([$hoje]);
        $pendentes = $stmt->fetchAll();

        // Tarefas agendadas para o futuro
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'pending' AND parent_id IS NULL AND due_date > ? ORDER BY due_date ASC");
        $stmt->execute([$hoje]);
        $futuras = $stmt->fetchAll();

        // Busca Subtarefas para anexar aos pais
        $anexarSubtarefas = function(&$lista) use ($pdo) {
            foreach ($lista as $key => $pai) {
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE parent_id = ? ORDER BY id ASC");
                $stmt->execute([$pai->id]);
                $lista[$key]->subtasks = $stmt->fetchAll();
            }
        };
        $anexarSubtarefas($pendentes);
        $anexarSubtarefas($futuras);

        // Alimentando a variável de concluídas que estava dando erro
        $concluidas = $pdo->query("SELECT * FROM tasks WHERE status = 'done' ORDER BY due_date DESC LIMIT 15")->fetchAll();
        
        // Categorias para o formulário
        $categoriasDB = $pdo->query("SELECT DISTINCT category FROM tasks")->fetchAll(PDO::FETCH_COLUMN);

        require BASE_PATH . 'app/Views/tasks.php';
    }

    // =========================================================
    // ROTA 3: RELATÓRIOS
    // =========================================================
    elseif ($page == 'relatorios') {
        $mes = $_GET['month'] ?? date('m');
        $ano = $_GET['year'] ?? date('Y');

        // AJUSTADO: Nome da variável para bater com o reports.php
        $stmtCat = $pdo->prepare("SELECT category, SUM(amount) as total FROM transactions WHERE type = 'expense' AND MONTH(created_at) = ? AND YEAR(created_at) = ? GROUP BY category");
        $stmtCat->execute([$mes, $ano]);
        $dadosGrafico = $stmtCat->fetchAll(PDO::FETCH_KEY_PAIR); 

        $stmt = $pdo->prepare("SELECT (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income' AND MONTH(created_at) = ? AND YEAR(created_at) = ?) as entradas, (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense' AND MONTH(created_at) = ? AND YEAR(created_at) = ?) as saidas");
        $stmt->execute([$mes, $ano, $mes, $ano]);
        $fin = $stmt->fetch();
        
        $margemFinanceira = ($fin->entradas > 0) ? (($fin->entradas - $fin->saidas) / $fin->entradas) * 100 : 0;
        
        $msgFinanceira = match(true) {
            $margemFinanceira < 0 => ['Ruim', 'Você gastou mais do que ganhou.', 'danger'],
            $margemFinanceira < 10 => ['Alerta', 'Margem baixa.', 'warning'],
            $margemFinanceira < 30 => ['Bom', 'No caminho certo.', 'success'],
            default => ['Excelente', 'Parabéns!', 'accent']
        };

        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as concluidas FROM tasks WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?");
        $stmt->execute([$mes, $ano]);
        $tasks = $stmt->fetch();
        $taxaProdutividade = ($tasks->total > 0) ? ($tasks->concluidas / $tasks->total) * 100 : 0;
        
        $msgProdutividade = match(true) {
            $taxaProdutividade < 30 => ['Procrastinando', 'Muitas tarefas iniciadas.', 'danger'],
            $taxaProdutividade < 70 => ['Na Média', 'Bom ritmo.', 'warning'],
            default => ['Produtivo', 'Foco total!', 'success']
        };

        require BASE_PATH . 'app/Views/reports.php';
    }

// =========================================================
   // =========================================================
    // ROTA: HOME
    // =========================================================
    else {
        $querySaldo = "SELECT (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income') - (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense') as total";
        
        // CORREÇÃO: Definindo a variável $saldo para a home.php encontrar
        $saldo = $pdo->query($querySaldo)->fetch()->total ?? 0;
        
        $transacoes = $pdo->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 5")->fetchAll();
        $pendentes = $pdo->query("SELECT count(*) as total FROM tasks WHERE status = 'pending'")->fetch()->total;
        $tarefas = $pdo->query("SELECT * FROM tasks WHERE status = 'pending' ORDER BY FIELD(priority, 'high', 'medium', 'low'), id DESC LIMIT 5")->fetchAll();

        require BASE_PATH . 'app/Views/home.php';
    }

} catch (Exception $e) {
    // Captura erros de conexão ou lógica e evita tela branca
    die("Erro Crítico no ButterPlan: " . $e->getMessage());
}