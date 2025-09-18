<?php
// Script para forçar refresh do cache de notícias e gravar logs.
// Pode ser chamado via web ou CLI. Retorna mensagem simples.

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/traffic_fetch.log';

$target = (isset($argv) && php_sapi_name() === 'cli' && isset($argv[1])) ? $argv[1] : null;
// por padrão, chama o endpoint local
$endpoint = 'http://localhost/smartroute/backend/traffic_news.php?refresh=1';
if ($target) $endpoint = $target;

date_default_timezone_set('America/Sao_Paulo');
$ts = date('Y-m-d H:i:s');
$logEntry = "[$ts] Iniciando refresh para: $endpoint\n";

// tenta cURL
$success = false;
$responseSummary = '';
if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($resp !== false && $code >= 200 && $code < 300) {
        $success = true;
        $responseSummary = "HTTP $code, bytes=" . strlen($resp);
    } else {
        $responseSummary = "Erro curl: code=$code err=$err";
    }
}

if (!$success && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $resp = @file_get_contents($endpoint, false, $ctx);
    if ($resp !== false) {
        $success = true;
        $responseSummary = "fopen OK, bytes=" . strlen($resp);
    } else {
        $responseSummary = "fopen falhou";
    }
}

$logEntry .= "Resultado: " . ($success ? 'SUCESSO' : 'FALHA') . ", $responseSummary\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// saída para web/CLI
if (php_sapi_name() === 'cli') {
    echo $logEntry;
} else {
    echo nl2br(htmlentities($logEntry));
}
