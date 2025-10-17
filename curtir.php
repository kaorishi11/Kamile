<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];
$post_id = intval($_POST['post_id'] ?? 0);

if ($post_id > 0) {
    // Verifica se já curtiu
    $stmt = $conn->prepare("SELECT id FROM likes WHERE usuario_id = ? AND post_id = ?");
    $stmt->bind_param('ii', $uid, $post_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Se já curtiu, remove a curtida (toggle)
        $conn->query("DELETE FROM likes WHERE usuario_id = $uid AND post_id = $post_id");
    } else {
        // Se ainda não curtiu, adiciona
        $stmt = $conn->prepare("INSERT INTO likes (usuario_id, post_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $uid, $post_id);
        $stmt->execute();
    }
}

// Retorna para o feed
header('Location: feed.php');
exit;
?>
