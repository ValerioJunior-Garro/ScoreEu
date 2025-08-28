<?php
session_start();
require "conexao.php";

if (!isset($_SESSION['rm'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$rmLogado = $_SESSION['rm'];
$rmDestino = $_GET['rmDestino'] ?? null;

if (!$rmDestino) {
    http_response_code(400);
    echo json_encode(["error" => "rmDestino não informado"]);
    exit;
}

try {
    $sql = "SELECT Mensagem, RM, RMDestinatario, DataEnvio
            FROM mensagem
            WHERE (RM = :logado AND RMDestinatario = :destino)
               OR (RM = :destino AND RMDestinatario = :logado)
            ORDER BY DataEnvio ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":logado" => $rmLogado,
        ":destino" => $rmDestino
    ]);

    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($mensagens, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao buscar mensagens: " . $e->getMessage()]);
}
