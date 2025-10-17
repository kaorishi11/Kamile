<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];

// Nova postagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conteudo'])) {
    $conteudo = trim($_POST['conteudo']);
    $imagem = null;

    if (!empty($_FILES['imagem']['name'])) {
        $pasta = 'uploads/';
        if (!is_dir($pasta)) mkdir($pasta, 0777, true);
        $imagem = $pasta . basename($_FILES['imagem']['name']);
        move_uploaded_file($_FILES['imagem']['tmp_name'], $imagem);
    }

    $stmt = $conn->prepare("INSERT INTO postagens (usuario_id, conteudo, imagem) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $uid, $conteudo, $imagem);
    $stmt->execute();
}

$postagens = $conn->query("
    SELECT p.*, u.nome, u.foto 
    FROM postagens p 
    JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.data_publicacao DESC
");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Feed - Kamile</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #0f172a;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        header {
            background: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        header h1 { color: #ff4d4d; }
        nav a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
        }
        main { max-width: 700px; margin: 30px auto; padding: 0 15px; }
        .nova {
            background: #1e293b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .nova textarea {
            width: 100%;
            min-height: 70px;
            padding: 10px;
            border-radius: 8px;
            border: none;
            margin-bottom: 10px;
        }
        .nova button {
            background: #ff4d4d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .post {
            background: #1e293b;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .post-header .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff4d4d;
        }
        .post-header strong { font-size: 16px; color: #ff4d4d; }
        .post-header .data { font-size: 12px; color: #aaa; }
        .imgpost {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 10px;
        }
        .acoes {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .acoes button {
            background: none;
            border: none;
            color: #ff4d4d;
            cursor: pointer;
            font-size: 15px;
        }
        .acoes button:hover { text-decoration: underline; }

        /* ====== Comentários ====== */
        .comentarios {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #334155;
        }

        .comentario {
            display: flex;
            gap: 10px;
            background: #0f172a;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .comentario .avatar-mini {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid #ff4d4d;
            object-fit: cover;
        }

        .comentario strong {
            color: #ff4d4d;
            font-size: 14px;
        }

        .comentario span {
            color: #e2e8f0;
            font-size: 13px;
        }

        .comentario .data {
            color: #94a3b8;
            font-size: 11px;
        }
        .sem-comentario {
            color: #94a3b8;
            font-size: 13px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Kamile</h1>
        <nav>
            <a href="chat.php">Chat</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>

    <main>
        <section class="nova">
            <h2>Nova Publicação</h2>
            <form method="POST" enctype="multipart/form-data">
                <textarea name="conteudo" placeholder="O que você está pensando?" required></textarea>
                <input type="file" name="imagem">
                <button type="submit">Publicar</button>
            </form>
        </section>

        <section class="feed">
            <?php while($p = $postagens->fetch_assoc()): ?>
                <?php
                $post_id = $p['id'];

                // Contar curtidas
                $qLikes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = $post_id");
                $likes = $qLikes->fetch_assoc()['total'];

                // Verificar se o usuário curtiu
                $qUserLike = $conn->query("SELECT id FROM likes WHERE post_id = $post_id AND usuario_id = $uid");
                $curtiu = $qUserLike->num_rows > 0;
                ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?= htmlspecialchars($p['foto'] ?: 'default.jpg') ?>" class="avatar" alt="Foto de perfil">
                        <div>
                            <strong><?= htmlspecialchars($p['nome']) ?></strong><br>
                            <span class="data"><?= htmlspecialchars($p['data_publicacao']) ?></span>
                        </div>
                    </div>

                    <p><?= nl2br(htmlspecialchars($p['conteudo'])) ?></p>

                    <?php if($p['imagem']): ?>
                        <img src="<?= htmlspecialchars($p['imagem']) ?>" class="imgpost" alt="Imagem da postagem">
                    <?php endif; ?>

                    <div class="acoes">
                        <form method="POST" action="curtir.php" class="acao-form" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                            <button type="submit" style="color:<?= $curtiu ? '#ff4d4d' : '#ccc' ?>">
                                <?= $likes ?> Curtidas
                            </button>
                        </form>

                        <button onclick="comentarPost(<?= $p['id'] ?>)">Comentar</button>
                    </div>

                    <?php
                    // Buscar comentários do post atual
                    $qComentarios = $conn->prepare("
                        SELECT c.*, u.nome, u.foto 
                        FROM comentarios c
                        JOIN usuarios u ON c.usuario_id = u.id
                        WHERE c.postagem_id = ?
                        ORDER BY c.data_comentario ASC
                    ");
                    $qComentarios->bind_param('i', $post_id);
                    $qComentarios->execute();
                    $comentarios = $qComentarios->get_result();
                    ?>

                    <div class="comentarios">
                        <?php if ($comentarios->num_rows > 0): ?>
                            <?php while($c = $comentarios->fetch_assoc()): ?>
                                <div class="comentario">
                                    <img src="<?= htmlspecialchars($c['foto'] ?: 'default.jpg') ?>" class="avatar-mini" alt="foto do usuário">
                                    <div>
                                        <strong><?= htmlspecialchars($c['nome']) ?></strong><br>
                                        <span><?= nl2br(htmlspecialchars($c['conteudo'])) ?></span><br>
                                        <small class="data"><?= htmlspecialchars($c['data_comentario']) ?></small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="sem-comentario">Nenhum comentário ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </section>
    </main>

    <script>
    function comentarPost(postId) {
        Swal.fire({
            title: 'Novo comentário',
            input: 'textarea',
            inputLabel: 'Escreva algo...',
            inputPlaceholder: 'Digite seu comentário aqui...',
            inputAttributes: { 'aria-label': 'Digite seu comentário aqui' },
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            cancelButtonText: 'Cancelar',
            preConfirm: (comentario) => {
                if (!comentario.trim()) {
                    Swal.showValidationMessage('O comentário não pode estar vazio.');
                    return false;
                }

                return fetch('comentar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `post_id=${postId}&comentario=${encodeURIComponent(comentario)}`
                }).then(response => {
                    if (!response.ok) throw new Error(response.statusText);
                    return response.text();
                }).catch(() => {
                    Swal.showValidationMessage('Erro ao enviar comentário!');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Comentário enviado!',
                    text: 'Seu comentário foi adicionado.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        });
    }
    </script>
</body>
</html>