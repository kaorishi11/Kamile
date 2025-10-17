<?php
require 'conexao.php';
session_start();

$uid = $_SESSION['usuario_id'];

// Considera online quem teve atividade nos últimos 2 minutos
$sql = "SELECT u.id, u.nome,
        (SELECT COUNT(*) FROM mensagens m 
         WHERE m.destinatario_id = ? AND m.remetente_id = u.id AND m.lida = 0) AS nao_lidas
        FROM usuarios u
        WHERE u.id != ? AND u.ultimo_ativo >= NOW() - INTERVAL 2 MINUTE
        ORDER BY u.nome";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $uid, $uid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<p>Nenhum usuário online.</p>";
} else {
    while ($u = $res->fetch_assoc()) {
        $badge = $u['nao_lidas'] > 0 ? "<span class='badge'>{$u['nao_lidas']}</span>" : "";
        echo "<div class='user' data-id='{$u['id']}'>
                {$u['nome']} $badge
              </div>";
    }
}
?>