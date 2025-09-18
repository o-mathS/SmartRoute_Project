<?php
function calcularDistancia($a, $b)
{
    $dx = $a['lat'] - $b['lat'];
    $dy = $a['lng'] - $b['lng'];
    return sqrt($dx * $dx + $dy * $dy); // Distância euclidiana simples
}

function vizinhoMaisProximo($pontos)
{
    if (!is_array($pontos) || count($pontos) === 0) {
        return [];
    }

    $rota = [];
    $visitados = [];
    $pontoAtual = 0;

    $rota[] = $pontoAtual;
    $visitados[$pontoAtual] = true;

    for ($i = 1; $i < count($pontos); $i++) {
        $maisProximo = null;
        $distanciaMenor = PHP_INT_MAX;

        foreach ($pontos as $index => $ponto) {
            if (!isset($visitados[$index])) {
                $dist = calcularDistancia($pontos[$pontoAtual], $ponto);
                if ($dist < $distanciaMenor) {
                    $distanciaMenor = $dist;
                    $maisProximo = $index;
                }
            }
        }

        if ($maisProximo === null) {
            break; // Nenhum vizinho encontrado (evita erro)
        }

        $pontoAtual = $maisProximo;
        $rota[] = $pontoAtual;
        $visitados[$pontoAtual] = true;
    }

    return $rota;
}

// ======================
// Receber os dados do frontend
// ======================
$dadosJson = file_get_contents("php://input");

if (!$dadosJson) {
    http_response_code(400);
    echo json_encode(["erro" => "Nenhum dado recebido."]);
    exit;
}

$pontos = json_decode($dadosJson, true);

if ($pontos === null) {
    http_response_code(400);
    echo json_encode(["erro" => "JSON inválido recebido.", "conteudo" => $dadosJson]);
    exit;
}

// ======================
// Executar algoritmo
// ======================
$ordem = vizinhoMaisProximo($pontos);

if (empty($ordem)) {
    http_response_code(400);
    echo json_encode(["erro" => "Não foi possível calcular a rota. Verifique os pontos enviados."]);
    exit;
}

// Cria array com pontos numerados na ordem de visita
$pontosNumerados = [];
foreach ($ordem as $i => $indicePonto) {
    $pontosNumerados[] = [
        'numero_visita' => $i + 1,
        'indice' => $indicePonto,
        'lat' => $pontos[$indicePonto]['lat'],
        'lng' => $pontos[$indicePonto]['lng']
    ];
}

// Retorna JSON da rota
echo json_encode($pontosNumerados);
