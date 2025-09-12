<?php
// backend/consulta_cep.php
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_GET['cep'])) {
    echo json_encode(['error' => 'CEP não informado']);
    exit;
}

$cep = preg_replace('/[^0-9]/', '', $_GET['cep']);
if (strlen($cep) !== 8) {
    echo json_encode(['error' => 'CEP inválido']);
    exit;
}

// URL da API Serpro (gateway)
$url = "https://h-apigateway.conectagov.estaleiro.serpro.gov.br/api-cep/v1/consulta/cep/$cep";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Se a API exigir token Bearer, descomente e preencha:
// $token = "SEU_TOKEN_AQUI";
// curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);

// Em ambiente local, se houver problema com certificado (apenas para testes), descomente:
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['error' => 'Erro cURL: ' . $curlErr]);
    exit;
}

if ($httpcode !== 200) {
    echo json_encode(['error' => 'HTTP '.$httpcode, 'body' => $response]);
    exit;
}

// A API deve retornar JSON; repassamos para o frontend
echo $response;
