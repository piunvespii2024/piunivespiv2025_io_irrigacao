<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexao.php';

try {
    // Caso venha temperatura, umidade do ar e solo → grava em leituras
    if (isset($_POST['temp'], $_POST['umid'], $_POST['solo'])) {
        $temp = floatval($_POST['temp']);
        $umid = floatval($_POST['umid']);
        $solo = floatval($_POST['solo']);

        $stmt = $conn->prepare("INSERT INTO leituras (temperatura, umidade_ar, umidade_solo, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ddd", $temp, $umid, $solo);
        $stmt->execute();

        echo json_encode(["sucesso" => true, "msg" => "Leitura completa salva"]);
        exit;
    }

    // Caso venha apenas umidade do solo + status → grava em dados_umidade
    if (isset($_POST['umidade'], $_POST['status'])) {
        $umidade = floatval($_POST['umidade']);
        $status  = $_POST['status'];

        $stmt = $conn->prepare("INSERT INTO dados_umidade (umidade, status, data_registro) VALUES (?, ?, NOW())");
        $stmt->bind_param("ds", $umidade, $status);
        $stmt->execute();

        echo json_encode(["sucesso" => true, "msg" => "Umidade do solo salva"]);
        exit;
    }

    // Se não veio nada válido
    echo json_encode(["erro" => "Campos obrigatórios ausentes (temp, umid, solo) ou (umidade, status)"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => $e->getMessage()]);
}
