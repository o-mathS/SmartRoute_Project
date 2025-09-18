<?php
// Retorna as últimas linhas do log em JSON
header('Content-Type: application/json; charset=utf-8');
$logFile = __DIR__ . '/logs/traffic_fetch.log';
$maxLines = 200;
$result = ['ok' => false, 'lines' => [], 'lastModified' => null];
if (!file_exists($logFile)) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// lê o arquivo e retorna as últimas $maxLines
$lines = [];
$fp = fopen($logFile, 'r');
if ($fp) {
    // seek to end and step backwards
    $buffer = '';
    $pos = -1;
    $currentLine = '';
    fseek($fp, 0, SEEK_END);
    $size = ftell($fp);
    $chunk = '';
    while (count($lines) < $maxLines && ftell($fp) > 0) {
        $pos = max(-$size, $pos - 4096);
        fseek($fp, $pos, SEEK_END);
        $chunk = fread($fp, 4096);
        $buffer = $chunk . $buffer;
        $parts = preg_split("/\r?\n/", $buffer);
        while (count($parts) > 1) {
            $line = array_pop($parts);
            if (strlen($line) === 0) continue;
            array_unshift($lines, $line);
            if (count($lines) >= $maxLines) break;
        }
        $buffer = implode("\n", $parts);
        if (ftell($fp) <= 4096) break;
    }
    // se ainda tiver buffer
    if (!empty($buffer)) {
        array_unshift($lines, $buffer);
    }
    fclose($fp);
}
$result['ok'] = true;
$result['lines'] = array_slice($lines, -$maxLines);
$result['lastModified'] = date('c', filemtime($logFile));

echo json_encode($result, JSON_UNESCAPED_UNICODE);
