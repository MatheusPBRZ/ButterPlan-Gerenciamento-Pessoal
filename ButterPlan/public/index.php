<?php
// /public/index.php
define('BASE_PATH', __DIR__ . '/../');
require BASE_PATH . 'app/Config/Database.php';

use App\Config\Database;



$page = $_GET['page'] ?? 'home';

try {
    $pdo = Database::getConnection();

   // =========================================================
    // GLOBAL: SISTEMA DE ROTINA (CORRIGIDO PARA PENDENTES)
    // =========================================================
    // =========================================================
    // GLOBAL: SISTEMA DE ROTINA (DIAS ESPECÍFICOS)
    // =========================================================
    $hoje = date('Y-m-d');
    
    // 1. Busca tarefas recorrentes antigas
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE is_recurring = 1 AND due_date < ?");
    $stmt->execute([$hoje]);
    $tarefasParaRenovar = $stmt->fetchAll();

    foreach ($tarefasParaRenovar as $t) {
        
        // --- LÓGICA DE CALCULAR A PRÓXIMA DATA ---
        $proximaData = $hoje; // Começamos tentando hoje
        $diasPermitidos = explode(',', $t->recurrence_days ?? '0,1,2,3,4,5,6'); // Se vazio, assume todo dia
        
        // Se hoje não é um dia permitido, procuramos o próximo
        // date('w') retorna 0 (Dom) a 6 (Sab)
        $diaSemanaHoje = date('w', strtotime($hoje));
        
        if (!in_array($diaSemanaHoje, $diasPermitidos)) {
            // Loop para achar o próximo dia válido (max 7 dias)
            for ($i = 1; $i <= 7; $i++) {
                $futuro = date('Y-m-d', strtotime("+$i days"));
                $diaSemanaFuturo = date('w', strtotime($futuro));
                
                if (in_array($diaSemanaFuturo, $diasPermitidos)) {
                    $proximaData = $futuro;
                    break;
                }
            }
        }
        // ----------------------------------------

        // 2. Evita duplicidade (Verifica se já existe tarefa com a data calculada)
        $check = $pdo->prepare("SELECT count(*) as total FROM tasks WHERE title = ? AND category = ? AND due_date = ?");
        $check->execute([$t->title, $t->category, $proximaData]);
        
        if ($check->fetch()->total == 0) {
            
            // 3. CRIA A NOVA TAREFA (Na data calculada: hoje ou futuro)
            $ins = $pdo->prepare("INSERT INTO tasks 
                (title, description, priority, category, is_recurring, recurrence_days, due_date, duration, parent_id, status) 
                VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, 'pending')");
            
            $ins->execute([
                $t->title, 
                $t->description, 
                $t->priority, 
                $t->category, 
                $t->recurrence_days, // Mantém o padrão de dias para a filha
                $proximaData,        // <--- Data calculada
                $t->duration,
                $t->parent_id
            ]);

            // 4. APOSENTA A VELHA
            $statusFinal = ($t->status == 'pending') ? 'expired' : 'done';
            $upd = $pdo->prepare("UPDATE tasks SET is_recurring = 0, status = ? WHERE id = ?");
            $upd->execute([$statusFinal, $t->id]);
        }
    }
    
    // =========================================================
    // ROTA 1: FINANÇAS
    // =========================================================
    if ($page == 'financas') {
        
        // 1. Excluir Transação Comum
        if (isset($_GET['action']) && $_GET['action'] == 'delete_transaction' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            header('Location: index.php?page=financas'); exit;
        }

        // 1.1. NOVA: Excluir Conta Fixa (Adicione isso aqui!)
        if (isset($_GET['action']) && $_GET['action'] == 'delete_fixed' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM fixed_expenses WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            header('Location: index.php?page=financas'); exit;
        }

        // 2. Salvar Dados (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] == 'add_transaction') {
                $stmt = $pdo->prepare("INSERT INTO transactions (type, description, amount) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['type'], $_POST['description'], (float)$_POST['amount']]);
            }
            if (isset($_POST['action']) && $_POST['action'] == 'add_fixed') {
                $stmt = $pdo->prepare("INSERT INTO fixed_expenses (title, amount, day_of_month) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['title'], (float)$_POST['amount'], (int)$_POST['day_of_month']]);
            }
            header('Location: index.php?page=financas'); exit;
        }

        // 3. Cálculos e Consultas
        $stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC");
        $allTransactions = $stmt->fetchAll();

        $resumo = $pdo->query("SELECT 
            (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income') as entradas,
            (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense') as saidas
        ")->fetch();
        $saldoReal = $resumo->entradas - $resumo->saidas;

        // Cálculo Saldo Livre (Fixas)
        $fixas = $pdo->query("SELECT * FROM fixed_expenses ORDER BY day_of_month ASC")->fetchAll();
        $totalPendencias = 0;

        foreach ($fixas as $key => $conta) {
            $sqlCheck = "SELECT COUNT(*) as pagou FROM transactions 
                         WHERE type = 'expense' 
                         AND description = ? 
                         AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                         AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute([$conta->title]);
            $jaPagou = $stmt->fetch()->pagou > 0;
            $fixas[$key]->is_paid = $jaPagou; 

            if (!$jaPagou) {
                $totalPendencias += $conta->amount;
            }
        }
        $saldoLivre = $saldoReal - $totalPendencias;

        require BASE_PATH . 'app/Views/finance.php';
    } 


   // =========================================================
    // ROTA 2: TAREFAS (COM SUBTAREFAS E CUSTOMIZAÇÃO)
    // =========================================================
    elseif ($page == 'tarefas') {
        
        

        // ... (Logo após capturar as variáveis do POST) ...
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');
            
            // --- NOVO: AJUSTE AUTOMÁTICO DE DATA ---
            // Se escolheu dias específicos, mas a data inicial não cai num deles,
            // a gente procura o próximo dia válido automaticamente.
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_task') {
            
            // 1º Passo: Capturar os dados básicos
            $title = $_POST['title'];
            $priority = $_POST['priority'];
            $category = $_POST['category'];
            $duration = !empty($_POST['duration']) ? (int)$_POST['duration'] : NULL;
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
            $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
            
            // 2º Passo: Definir se é recorrente (PARA O ERRO NÃO ACONTECER)
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;

            // 3º Passo: Capturar e Ajustar a Data
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d');

            // --- TRAVA 2: AJUSTE AUTOMÁTICO DE DATA ---
            // Agora funciona, pois $is_recurring já existe na linha de cima
            if ($is_recurring && isset($_POST['days'])) {
                $diasEscolhidos = $_POST['days']; 
                $diaDaData = date('w', strtotime($due_date)); 

                if (!in_array($diaDaData, $diasEscolhidos)) {
                    for ($i = 1; $i <= 7; $i++) {
                        $novaData = date('Y-m-d', strtotime("$due_date +$i days"));
                        $novoDia = date('w', strtotime($novaData));
                        
                        if (in_array($novoDia, $diasEscolhidos)) {
                            $due_date = $novaData; // Data corrigida!
                            break;
                        }
                    }
                }
            }
            // ----------------------------------------

            // 4º Passo: Capturar os Dias da Recorrência (String)
            $recurrence_days = null;
            if ($is_recurring && isset($_POST['days'])) {
                $recurrence_days = implode(',', $_POST['days']);
            }

            // 5º Passo: Salvar no Banco
            if (!empty($title)) {
                $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, category, is_recurring, recurrence_days, due_date, duration, parent_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$title, $description, $priority, $category, $is_recurring, $recurrence_days, $due_date, $duration, $parent_id]);
            }
            
            header('Location: index.php?page=tarefas'); exit;
        }

        // 3. Ações GET (Toggle / Delete)
        if (isset($_GET['action'])) {
            $id = $_GET['id'];
            
            if ($_GET['action'] == 'toggle_task') {
                $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
                $stmt->execute([$id]);
                $curr = $stmt->fetch()->status;
                $new = ($curr == 'pending') ? 'done' : 'pending';
                $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$new, $id]);
            }
            
            if ($_GET['action'] == 'delete_task') {
                // Ao deletar um pai, deletamos os filhos também (Cascade manual)
                $pdo->prepare("DELETE FROM tasks WHERE parent_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
            }
            header('Location: index.php?page=tarefas'); exit;
        }

        // 4. BUSCAR DADOS HIERÁRQUICOS (FILTRADOS)
        // Adicionamos: AND due_date <= ? (Menor ou igual a hoje)
        
        $hoje = date('Y-m-d');

        $stmt = $pdo->prepare("
            SELECT * FROM tasks 
            WHERE status = 'pending' 
            AND parent_id IS NULL
            AND due_date <= ? 
            ORDER BY due_date ASC, FIELD(priority, 'high', 'medium', 'low')
        ");
        $stmt->execute([$hoje]);
        $pendentes = $stmt->fetchAll();

        // Para cada pai, buscamos os filhos (Isso é rápido para projetos pessoais)
        foreach ($pendentes as $key => $pai) {
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE parent_id = ? ORDER BY id ASC");
            $stmt->execute([$pai->id]);
            $pendentes[$key]->subtasks = $stmt->fetchAll();
        }
        // B. FUTURAS (Amanhã em diante) <--- NOVO BLOCO
        $stmt = $pdo->prepare("
            SELECT * FROM tasks 
            WHERE status = 'pending' AND parent_id IS NULL AND due_date > ? 
            ORDER BY due_date ASC
        ");
        $stmt->execute([$hoje]);
        $futuras = $stmt->fetchAll();

        // C. ANEXAR SUBTAREFAS (Para ambas as listas)
        // Criamos uma função anônima pra não repetir código
        $anexarSubtarefas = function(&$lista) use ($pdo) {
            foreach ($lista as $key => $pai) {
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE parent_id = ? ORDER BY id ASC");
                $stmt->execute([$pai->id]);
                $lista[$key]->subtasks = $stmt->fetchAll();
            }
        };

        $anexarSubtarefas($pendentes);
        $anexarSubtarefas($futuras); // Agora as futuras também mostram seus filhos

        // D. CONCLUÍDAS
        $concluidas = $pdo->query("SELECT * FROM tasks WHERE status = 'done' ORDER BY due_date DESC LIMIT 15")->fetchAll();
        // Buscar lista de categorias existentes para o autocomplete
        $categoriasDB = $pdo->query("SELECT DISTINCT category FROM tasks")->fetchAll(PDO::FETCH_COLUMN);

        require BASE_PATH . 'app/Views/tasks.php';
    }

    // =========================================================
    // ROTA 3: RELATÓRIOS
    // =========================================================
    elseif ($page == 'relatorios') {
        $mes = $_GET['month'] ?? date('m');
        $ano = $_GET['year'] ?? date('Y');

        // Finanças
        $stmt = $pdo->prepare("SELECT 
            (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income' AND MONTH(created_at) = ? AND YEAR(created_at) = ?) as entradas,
            (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense' AND MONTH(created_at) = ? AND YEAR(created_at) = ?) as saidas
        ");
        $stmt->execute([$mes, $ano, $mes, $ano]);
        $fin = $stmt->fetch();

        $saldoMes = $fin->entradas - $fin->saidas;
        $margemFinanceira = ($fin->entradas = 0) ? ($saldoMes / $fin->entradas) * 100 : 0;
        
        $msgFinanceira = match(true) {
            $margemFinanceira < 0 => ['Ruim', 'Você gastou mais do que ganhou.', 'danger'],
            $margemFinanceira < 10 => ['Alerta', 'Margem baixa.', 'warning'],
            $margemFinanceira < 30 => ['Bom', 'No caminho certo.', 'success'],
            default => ['Excelente', 'Parabéns!', 'accent']
        };

        // Tarefas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as concluidas FROM tasks WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?");
        $stmt->execute([$mes, $ano]);
        $tasks = $stmt->fetch();

        $taxaProdutividade = ($tasks->total > 0) ? ($tasks->concluidas / $tasks->total) * 100 : 0;
        
        $msgProdutividade = match(true) {
            $taxaProdutividade < 30 => ['Procrastinando', 'Muitas tarefas iniciadas e poucas terminadas.', 'danger'],
            $taxaProdutividade < 70 => ['Na Média', 'Bom ritmo.', 'warning'],
            default => ['Produtivo', 'Foco total!', 'success']
        };

        require BASE_PATH . 'app/Views/reports.php';
    }

    // =========================================================
    // ROTA: HOME (Padrão)
    // =========================================================
    else {
        // Saldo
        $querySaldo = "SELECT 
            (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'income') - 
            (SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'expense') as total";
        $saldo = $pdo->query($querySaldo)->fetch()->total ?? 0;

        // Resumos
        $transacoes = $pdo->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 5")->fetchAll();
        $pendentes = $pdo->query("SELECT count(*) as total FROM tasks WHERE status = 'pending'")->fetch()->total;
        $tarefas = $pdo->query("SELECT * FROM tasks WHERE status = 'pending' ORDER BY FIELD(priority, 'high', 'medium', 'low'), id DESC LIMIT 5")->fetchAll();

        require BASE_PATH . 'app/Views/home.php';
    }

} catch (Exception $e) {
    die("Erro Crítico no LifeOS: " . $e->getMessage());
}