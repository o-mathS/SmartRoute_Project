<?php
function calcularDistancia($a, $b)
{
    $dx = $a['lat'] - $b['lat'];
    $dy = $a['lng'] - $b['lng'];
    return sqrt($dx * $dx + $dy * $dy); // Distância euclidiana (simples)
}

function vizinhoMaisProximo($pontos)
{
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

        $pontoAtual = $maisProximo;
        $rota[] = $pontoAtual;
        $visitados[$pontoAtual] = true;
    }

    return $rota;
}

// Receber os dados do frontend
$dadosJson = file_get_contents("php://input");
$pontos = json_decode($dadosJson, true);

$ordem = vizinhoMaisProximo($pontos);

// Cria um array com os pontos numerados na ordem de visita
$pontosNumerados = [];
foreach ($ordem as $i => $indicePonto) {
    $pontosNumerados[] = [
        'numero_visita' => $i + 1,
        'indice' => $indicePonto,
        'lat' => $pontos[$indicePonto]['lat'],
        'lng' => $pontos[$indicePonto]['lng']
    ];
}

// Retorna a ordem otimizada com numeração
echo json_encode($pontosNumerados);

