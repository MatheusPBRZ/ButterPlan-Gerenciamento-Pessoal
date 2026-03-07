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
// 👆 ============================================== 👆

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
    
    // ATUALIZAÇÃO: Busca tarefas recorrentes cuja DATA DE VENCIMENTO é menor que hoje
    // Ou seja, se virou meia-noite, a de ontem está vencida e deve renovar.
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE is_recurring = 1 AND due_date < ?");
    $stmt->execute([$hoje]);
    $tarefasVencidas = $stmt->fetchAll();

    foreach ($tarefasVencidas as $t) {
        
        // 1. Define a nova data de vencimento (Hoje + 24h de ciclo visual)
        // Como o ciclo é contínuo, a nova tarefa nasce AGORA.
        $novaDueDate = date('Y-m-d'); 

        // 2. CRIA A NOVA TAREFA (Renovada)
        // Verificamos se já não foi criada nos últimos segundos para evitar duplicidade de F5
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

            // 3. EXPIRA A TAREFA VELHA (Timer Zerou)
            // Se estava pendente, vira 'expired' (falhou). Se estava done, fica done.
            $statusFinal = ($t->status == 'pending') ? 'expired' : 'done';
            
            // Removemos a recorrência da velha e atualizamos o status
            $upd = $pdo->prepare("UPDATE tasks SET is_recurring = 0, status = ? WHERE id = ?");
            $upd->execute([$statusFinal, $t->id]);
        }
    }

    // =========================================================
    // ROTA 1: FINANÇAS (COM PARCELAMENTOS)
    // =========================================================
    if ($page == 'financas') {
        
        // --- EXCLUSÕES ---
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
                // Desvincula as transações para não apagar o histórico do extrato
                $pdo->prepare("UPDATE transactions SET installment_id = NULL WHERE installment_id = ?")->execute([$_GET['id']]);
                $pdo->prepare("DELETE FROM installments WHERE id = ?")->execute([$_GET['id']]);
                header('Location: index.php?page=financas'); exit;
            }
        }

        // --- ADIÇÕES E PAGAMENTOS ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] == 'add_transaction') {
                $pdo->prepare("INSERT INTO transactions (type, description, amount) VALUES (?, ?, ?)")
                    ->execute([$_POST['type'], $_POST['description'], (float)$_POST['amount']]);
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
                $inst_amount = $total_amount / $total_inst; // Calcula o valor de cada parcela

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

        // --- CONSULTAS E CÁLCULOS ---
        $allTransactions = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC")->fetchAll();

        $resumo = $pdo->query("SELECT (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income') as entradas, (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense') as saidas")->fetch();
        $saldoReal = $resumo->entradas - $resumo->saidas;

        $totalPendencias = 0;

        // 1. Processa Fixas
        $fixas = $pdo->query("SELECT * FROM fixed_expenses ORDER BY day_of_month ASC")->fetchAll();
        foreach ($fixas as $key => $conta) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as pagou FROM transactions WHERE type = 'expense' AND description = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $stmt->execute([$conta->title]);
            $jaPagou = $stmt->fetch()->pagou > 0;
            $fixas[$key]->is_paid = $jaPagou; 
            if (!$jaPagou) $totalPendencias += $conta->amount;
        }

        // 2. Processa Parcelamentos
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
    // ROTA 2: TAREFAS
    // =========================================================
    elseif ($page == 'tarefas') {
        
        // POST: Adicionar Tarefa
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_task') {
            $title = $_POST['title'];
            $priority = $_POST['priority'];
            $category = $_POST['category'];
            $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
            $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
            
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');

            // Ajuste de Data
            if ($is_recurring && isset($_POST['days'])) {
                $diasEscolhidos = $_POST['days']; 
                $diaDaData = date('w', strtotime($due_date)); 
                if (!in_array($diaDaData, $diasEscolhidos)) {
                    for ($i = 1; $i <= 7; $i++) {
                        $novaData = date('Y-m-d', strtotime("$due_date +$i days"));
                        $novoDia = date('w', strtotime($novaData));
                        if (in_array($novoDia, $diasEscolhidos)) {
                            $due_date = $novaData;
                            break;
                        }
                    }
                }
            }
            
            $recurrence_days = ($is_recurring && isset($_POST['days'])) ? implode(',', $_POST['days']) : null;

            if (!empty($title)) {
                // 1. SALVA NO BANCO DE DADOS LOCAL (Seu MySQL)
                $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, category, is_recurring, recurrence_days, due_date, duration, parent_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$title, $description, $priority, $category, $is_recurring, $recurrence_days, $due_date, $duration, $parent_id]);

                // =========================================================
                // 2. MAGIA NEGRA: CRIA O EVENTO NO GOOGLE CALENDAR
                // =========================================================
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

                        $dataInicio = date('Y-m-d\T09:00:00-03:00', strtotime($due_date));
                        $minutosDuracao = $duration ? $duration : 60; 
                        $dataFim = date('Y-m-d\TH:i:sP', strtotime("$due_date 09:00:00 +$minutosDuracao minutes"));

                        $descGoogle = $description ? $description . "\n\n" : "Tarefa gerada automaticamente.\n\n";
                        $descGoogle .= "📊 Categoria: " . ucfirst($category) . "\n";
                        $descGoogle .= "🚨 Prioridade: " . ucfirst($priority);

                        $event = new Event([
                          'summary' => 'ButterPlan: ' . $title,
                          'description' => $descGoogle,
                          'start' => [
                            'dateTime' => $dataInicio,
                            'timeZone' => 'America/Sao_Paulo',
                          ],
                          'end' => [
                            'dateTime' => $dataFim,
                            'timeZone' => 'America/Sao_Paulo',
                          ],
                        ]);

                        $service->events->insert('primary', $event);
                    }
                } catch (Exception $e) {
                    error_log("Erro no Google Calendar: " . $e->getMessage());
                }
                // =========================================================
            }
            header('Location: index.php?page=tarefas'); 
            exit;
        }

        // GET: Ações da Página de Tarefas
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'toggle_task' && isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $newStatus = ($stmt->fetch()->status == 'pending') ? 'done' : 'pending';
                $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$newStatus, $_GET['id']]);
            }
            if ($_GET['action'] == 'delete_task' && isset($_GET['id'])) {
                $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$_GET['id']]);
            }
            header('Location: index.php?page=tarefas'); exit;
        }

        // Consultas (Pendentes Hoje / Futuras / Concluídas)
        $hoje = date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'pending' AND parent_id IS NULL AND due_date <= ? ORDER BY due_date ASC, FIELD(priority, 'high', 'medium', 'low')");
        $stmt->execute([$hoje]);
        $pendentes = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'pending' AND parent_id IS NULL AND due_date > ? ORDER BY due_date ASC");
        $stmt->execute([$hoje]);
        $futuras = $stmt->fetchAll();

        $anexarSubtarefas = function(&$lista) use ($pdo) {
            foreach ($lista as $key => $pai) {
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE parent_id = ? ORDER BY id ASC");
                $stmt->execute([$pai->id]);
                $lista[$key]->subtasks = $stmt->fetchAll();
            }
        };
        $anexarSubtarefas($pendentes);
        $anexarSubtarefas($futuras);

        $concluidas = $pdo->query("SELECT * FROM tasks WHERE status = 'done' ORDER BY due_date DESC LIMIT 15")->fetchAll();
        $categoriasDB = $pdo->query("SELECT DISTINCT category FROM tasks")->fetchAll(PDO::FETCH_COLUMN);

        require BASE_PATH . 'app/Views/tasks.php';
    }

    // =========================================================
    // ROTA 3: RELATÓRIOS
    // =========================================================
    elseif ($page == 'relatorios') {
        $mes = $_GET['month'] ?? date('m');
        $ano = $_GET['year'] ?? date('Y');

        $stmt = $pdo->prepare("SELECT (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income' AND MONTH(created_at) = ? AND YEAR(created_at) = ?) as entradas, (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense' AND MONTH(created_at) = ? AND YEAR(created_at) = ?) as saidas");
        $stmt->execute([$mes, $ano, $mes, $ano]);
        $fin = $stmt->fetch();
        $saldoMes = $fin->entradas - $fin->saidas;
        $margemFinanceira = ($fin->entradas > 0) ? ($saldoMes / $fin->entradas) * 100 : 0;
        
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
    // ROTA: HOME
    // =========================================================
    else {
        $querySaldo = "SELECT (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income') - (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense') as total";
        $saldo = $pdo->query($querySaldo)->fetch()->total ?? 0;
        $transacoes = $pdo->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 5")->fetchAll();
        $pendentes = $pdo->query("SELECT count(*) as total FROM tasks WHERE status = 'pending'")->fetch()->total;
        $tarefas = $pdo->query("SELECT * FROM tasks WHERE status = 'pending' ORDER BY FIELD(priority, 'high', 'medium', 'low'), id DESC LIMIT 5")->fetchAll();

        require BASE_PATH . 'app/Views/home.php';
    }

} catch (Exception $e) {
    die("Erro Crítico no ButterPlan: " . $e->getMessage());
}