<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];
$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM postagens WHERE id = ? AND usuario_id = ?");
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

header('Location: perfil.php');
exit;
?>