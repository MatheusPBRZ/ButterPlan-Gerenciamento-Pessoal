//1. Pegar o Saldo Real (Você já tem essa query)
// $saldo = ...

// 2. Calcular quanto falta pagar de contas fixas este mês
// A lógica é: Soma todas as contas fixas ativas, MENOS as que já lancei nas transações deste mês com o mesmo nome.

$mesAtual = date('m');
$anoAtual = date('Y');

// Busca contas fixas
$fixas = $pdo->query("SELECT * FROM fixed_expenses WHERE active = 1")->fetchAll();

$totalFixas = 0;
$jaPagas = 0;

foreach ($fixas as $conta) {
    $totalFixas += $conta->amount;
    
    // Verifica se já existe uma transação de SAÍDA com esse NOME neste MÊS
    $sqlCheck = "SELECT COUNT(*) as pagou FROM transactions 
                 WHERE type = 'expense' 
                 AND description = ? 
                 AND strftime('%m', created_at) = ? 
                 AND strftime('%Y', created_at) = ?";
                 // Obs: Se for MySQL use MONTH(created_at) e YEAR(created_at)
    
    // Ajuste para MySQL se estiver usando WAMP:
    $sqlCheckMySQL = "SELECT COUNT(*) as pagou FROM transactions 
                      WHERE type = 'expense' 
                      AND description LIKE ? 
                      AND MONTH(created_at) = ? 
                      AND YEAR(created_at) = ?";

    $stmt = $pdo->prepare($sqlCheckMySQL);
    $stmt->execute([$conta->title, $mesAtual, $anoAtual]);
    
    if ($stmt->fetch()->pagou > 0) {
        $jaPagas += $conta->amount;
    }
}

$aPagar = $totalFixas - $jaPagas;
$saldoPrevisto = $saldo - $aPagar;