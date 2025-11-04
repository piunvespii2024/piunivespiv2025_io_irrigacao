<?php
// Força exibição de erros (útil em desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Captura parâmetros possíveis
$umidade = $_POST['umidade'] ?? $_GET['umidade'] ?? null;
$status  = $_POST['status']  ?? $_GET['status']  ?? null;

$temp = $_POST['temp'] ?? $_GET['temp'] ?? null;
$umid = $_POST['umid'] ?? $_GET['umid'] ?? null;
$solo = $_POST['solo'] ?? $_GET['solo'] ?? null;

// Monta payload
$payload = null;

// Caso venha umidade + status
if ($umidade !== null && $status !== null) {
    $payload = [
        "umidade" => $umidade,
        "status"  => $status
    ];
}
// Caso venha temp/umid/solo → converte para umidade/status
elseif ($temp !== null && $umid !== null && $solo !== null) {
    $soloVal = floatval($solo);
    $statusCalc = ($soloVal < 30 ? "Solo seco" :
                  ($soloVal < 60 ? "Umidade moderada" :
                  ($soloVal < 85 ? "Solo úmido" : "Solo encharcado")));

    $payload = [
        "umidade" => $soloVal,
        "status"  => $statusCalc
    ];
}

if ($payload) {
    // URL do servidor remoto
    $url = "https://softdesignersolutionsltda.com.br/api/salvar_dados.php";

    // Inicializa cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Executa requisição
    $resposta = curl_exec($ch);
    $erro = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if ($erro) {
        echo json_encode([
            "success" => false,
            "mensagem" => "Erro ao enviar para servidor externo",
            "erro" => $erro,
            "info" => $info
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "mensagem" => "Dados enviados com sucesso",
            "payload_enviado" => $payload,
            "resposta_servidor" => $resposta,
            "info" => $info
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "mensagem" => "Nenhum dado recebido localmente. Informe (umidade,status) ou (temp,umid,solo)."
    ]);
}
