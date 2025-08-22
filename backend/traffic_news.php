<?php
header('Content-Type: application/json; charset=utf-8');

// Endpoint simples para fornecer notícias de trânsito.
// - Por padrão retorna o conteúdo de assets/traffic_news.json
// - Se chamado com ?refresh=1 e se existir uma URL externa configurada em news_config.php,
//   tenta buscar e atualizar o cache local.

$baseDir = __DIR__ . '\\..';
$cacheFile = realpath(__DIR__ . '/../assets/traffic_news.json') ?: (__DIR__ . '/../assets/traffic_news.json');

$news = [];
if (file_exists($cacheFile)) {
    $raw = file_get_contents($cacheFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $news = $decoded;
    }
}

// Se solicitado, tente atualizar a partir de fonte externa (se configurada)
if (isset($_GET['refresh']) && $_GET['refresh'] == '1') {
    $configFile = __DIR__ . '/news_config.php';
    if (file_exists($configFile)) {
        include $configFile; // define $EXTERNAL_NEWS_URL, $EXTERNAL_TIMEOUT, $LOCAL_CACHE
    }

    if (!empty($EXTERNAL_NEWS_URL)) {
        // tentar cURL primeiro
        $fetched = false;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $EXTERNAL_NEWS_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, isset($EXTERNAL_TIMEOUT) ? (int)$EXTERNAL_TIMEOUT : 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SmartRoute/1.0 (+https://example.local)');
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($result !== false && $httpCode >= 200 && $httpCode < 300) {
                $parsed = json_decode($result, true);
                if (is_array($parsed)) {
                    $news = $parsed;
                    $fetched = true;
                    // atualizar cache local
                    @file_put_contents($cacheFile, json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
        }
        // fallback para file_get_contents
        if (!$fetched && ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => ['timeout' => isset($EXTERNAL_TIMEOUT) ? (int)$EXTERNAL_TIMEOUT : 5]]);
            $result = @file_get_contents($EXTERNAL_NEWS_URL, false, $ctx);
            if ($result !== false) {
                $parsed = json_decode($result, true);
                if (is_array($parsed)) {
                    $news = $parsed;
                    @file_put_contents($cacheFile, json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
        }
    }
}

// Saída final
echo json_encode($news, JSON_UNESCAPED_UNICODE);
