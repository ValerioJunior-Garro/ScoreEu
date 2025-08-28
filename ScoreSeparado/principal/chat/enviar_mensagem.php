<?php
session_start();
require "conexao.php";

if (!isset($_SESSION['rm'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$rmAutor   = $_SESSION['rm'];
$rmDestino = $_POST['rmDestino'] ?? null;
$mensagem  = $_POST['mensagem'] ?? null;

if (!$rmDestino || !$mensagem) {
    http_response_code(400);
    echo json_encode([
        "error" => "Parâmetros inválidos",
        "rmDestino" => $rmDestino,
        "mensagem" => $mensagem
    ]);
    exit;
}

try {
    // Inserir mensagem com DataEnvio automática (NOW())
    $sql = "INSERT INTO mensagem (Mensagem, RM, RMDestinatario, DataEnvio) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mensagem, $rmAutor, $rmDestino]);

    // Pegar o horário exato do envio
    $id = $pdo->lastInsertId();
    $sqlData = "SELECT DataEnvio FROM mensagem WHERE ID = ?";
    $stmtData = $pdo->prepare($sqlData);
    $stmtData->execute([$id]);
    $dataEnvio = $stmtData->fetchColumn();

    echo json_encode([
        "status" => "ok",
        "data" => $dataEnvio
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao salvar mensagem: " . $e->getMessage()]);
}
