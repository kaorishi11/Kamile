<?php
session_start();
require 'conexao.php';

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];
$erro = "";

// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'perfil') {
    $nome = trim($_POST['nome']);
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        $pasta = 'uploads/';
        if (!is_dir($pasta)) mkdir($pasta, 0777, true);

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $foto = $pasta . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $foto);
            $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, foto = ? WHERE id = ?");
            $stmt->bind_param('ssi', $nome, $foto, $uid);
        } else {
            $erro = 'Tipo de arquivo inválido (use JPG, PNG ou GIF).';
        }
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ? WHERE id = ?");
        $stmt->bind_param('si', $nome, $uid);
    }

    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }
}

// Atualizar postagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'postagem') {
    $pid = intval($_POST['post_id']);
    $conteudo = trim($_POST['conteudo']);
    $imagem = null;

    if (!empty($_FILES['foto_post']['name'])) {
        $pasta = 'uploads/';
        if (!is_dir($pasta)) mkdir($pasta, 0777, true);

        $ext = strtolower(pathinfo($_FILES['foto_post']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imagem = $pasta . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto_post']['tmp_name'], $imagem);

            $stmt = $conn->prepare("UPDATE postagens SET conteudo = ?, imagem = ? WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param('ssii', $conteudo, $imagem, $pid, $uid);
        } else {
            $erro = 'Tipo de arquivo inválido para a postagem.';
        }
    } else {
        $stmt = $conn->prepare("UPDATE postagens SET conteudo = ? WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param('sii', $conteudo, $pid, $uid);
    }

    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }
}

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar postagens do usuário
$postagens = $conn->prepare("SELECT * FROM postagens WHERE usuario_id = ? ORDER BY data_publicacao DESC");
$postagens->bind_param('i', $uid);
$postagens->execute();
$result_posts = $postagens->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Kamile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        main {
    display: flex;
    flex-direction: column;
    align-items: center;
}

h3 {
    text-align: center;
    margin: 30px 0 10px;
}

/* Container das postagens */
.posts-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px; /* espaçamento entre os cards */
    width: 100%;
    max-width: 1000px; /* limita a largura total */
    margin: 0 auto;
}

/* Cada postagem individual */
.post {
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 0 8px rgba(0,0,0,0.1);
    width: 500px; /* largura fixa dos cards */
    display: flex;
    flex-direction: column;
    align-items: center;
}

.post textarea {
    width: 100%;
    resize: none;
    border-radius: 5px;
    padding: 8px;
    border: 1px solid #ccc;
}

.post img {
    width: 100%;
    border-radius: 8px;
    margin: 10px 0;
    object-fit: cover;
}


    </style>
</head>
<body>
<header>
    <h1>Kamile</h1>
    <nav>
        <a href="chat.php">Chat</a>
        <a href="feed.php">Feed</a>
        <a href="logout.php">Sair</a>
    </nav>
</header>

<main>
    <div class="card">
        <h2>Meu Perfil</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="perfil">
            <label>Nome:</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
            <label>Foto de Perfil:</label>
            <input type="file" name="foto">
            <?php if (!empty($usuario['foto'])): ?>
                <img src="<?= htmlspecialchars($usuario['foto']) ?>" width="120" style="border-radius:50%;margin-top:10px;">
            <?php endif; ?>
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>

    <h3>Minhas Publicações</h3>

    <div class="posts-container">
        <?php while($p = $result_posts->fetch_assoc()): ?>
            <div class="post">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="postagem">
                    <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                    <small>Publicado em: <?= htmlspecialchars($p['data_publicacao']) ?></small>
                    <textarea name="conteudo" rows="3"><?= htmlspecialchars($p['conteudo']) ?></textarea>
                    
                    <?php if (!empty($p['imagem'])): ?>
                        <img src="<?= htmlspecialchars($p['imagem']) ?>" alt="Imagem da postagem">
                    <?php endif; ?>

                    <input type="file" name="foto_post">
                    <button type="submit" style="margin-left: 20px;">Salvar Postagem</button>
                    <a href="excluir_post.php?id=<?= $p['id']?>" style="margin-left: 200px;">Excluir</a>
                </form>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (!empty($erro)) echo "<p class='erro'>$erro</p>"; ?>
</main>
</body>
</html>
