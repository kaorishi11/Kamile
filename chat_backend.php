<?php
require 'conexao.php';
session_start();
$uid = $_SESSION['usuario_id'];

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dest = intval($_POST['destinatario']);
    $msg = trim($_POST['mensagem']);
    if ($msg !== '') {
        $stmt = $conn->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, conteudo, data_envio)
                                VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $uid, $dest, $msg);
        $stmt->execute();
    }
    exit;
}

// Carregar mensagens
if (isset($_GET['dest'])) {
    $dest = intval($_GET['dest']);
    $sql = "SELECT * FROM mensagens
            WHERE (remetente_id=? AND destinatario_id=?) 
               OR (remetente_id=? AND destinatario_id=?)
            ORDER BY data_envio";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $uid, $dest, $dest, $uid);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($m = $res->fetch_assoc()) {
        $classe = $m['remetente_id'] == $uid ? 'enviada' : 'recebida';
        echo "<div class='msg $classe'>{$m['conteudo']}</div>";
    }
}
?>