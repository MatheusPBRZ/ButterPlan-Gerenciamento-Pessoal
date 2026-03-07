<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;

$client = new Client();
$client->setApplicationName('ButterPlan');
$client->setScopes(Calendar::CALENDAR_EVENTS);
$client->setAuthConfig(__DIR__ . '/keys/credentials.json'); 
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// 👇 AQUI ESTÁ A TRAVA DE SEGURANÇA 👇
$client->setRedirectUri('http://127.0.0.1/ButterPlan/google_test.php');

$tokenPath = __DIR__ . '/token.json';

if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
}

if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
        if (isset($_GET['code'])) {
            $authCode = $_GET['code'];
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);
            
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            echo "<h2>✅ Token salvo com sucesso!</h2>";
            echo "<a href='google_test.php'>Clique aqui para criar um evento teste</a>";
            exit;
        } else {
            $authUrl = $client->createAuthUrl();
            echo "<h2>Integração Google Agenda - ButterPlan</h2>";
            echo "<a href='$authUrl' style='padding:10px 20px; background:#4285F4; color:white; text-decoration:none; border-radius:5px;'>Autorizar Aplicativo</a>";
            exit;
        }
    }
}

// CRIANDO O EVENTO
$service = new Calendar($client);

$event = new Event([
  'summary' => '🚀 Tarefa Teste - ButterPlan',
  'description' => 'Integração de API funcionando com sucesso!',
  'start' => [
    'dateTime' => date('Y-m-d\TH:i:sP', strtotime('+1 hour')),
    'timeZone' => 'America/Sao_Paulo',
  ],
  'end' => [
    'dateTime' => date('Y-m-d\TH:i:sP', strtotime('+2 hours')),
    'timeZone' => 'America/Sao_Paulo',
  ],
]);

$calendarId = 'primary';
$event = $service->events->insert($calendarId, $event);

echo "<h2>🎉 Sucesso Total!</h2>";
echo "<p>Abra o Google Agenda no seu celular agora.</p>";
echo "<p>Link do evento: <a href='" . $event->htmlLink . "' target='_blank'>Ver no Calendário</a></p>";