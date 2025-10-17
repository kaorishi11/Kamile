<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];
$post_id = intval($_POST['post_id'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($post_id > 0 && $comentario !== '') {
    $stmt = $conn->prepare("INSERT INTO comentarios (postagem_id, usuario_id, conteudo) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $post_id, $uid, $comentario);
    $stmt->execute();
}

exit;
?>