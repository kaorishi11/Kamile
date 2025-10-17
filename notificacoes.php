<?php
session_start();
require_once 'conexao.php';
if (!isset($_SESSION['user_id'])) exit;

$uid = $_SESSION['user_id'];

// Conta quantas mensagens não lidas há por remetente
$res = $conn->query("
    SELECT remetente_id, COUNT(*) AS total 
    FROM mensagens 
    WHERE destinatario_id = $uid AND lida = 0 
    GROUP BY remetente_id
");

$dados = [];
while($r = $res->fetch_assoc()) {
    $dados[$r['remetente_id']] = (int)$r['total'];
}

header('Content-Type: application/json');
echo json_encode($dados);
?>
