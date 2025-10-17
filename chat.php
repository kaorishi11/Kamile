<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];

// Atualiza status online
$conn->query("UPDATE usuarios SET ultimo_ativo = NOW() WHERE id = $uid");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat - Kamile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            display: flex;
            flex: 1;
            width: 100%;
            overflow: hidden;
        }
        .sidebar {
            width: 280px;
            background: var(--card);
            border-right: 1px solid var(--border);
            padding: 15px;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-size: 18px;
            color: var(--accent);
            margin-bottom: 10px;
        }
        .user {
            padding: 10px;
            border-radius: 6px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s;
        }
        .user:hover { background: rgba(255,255,255,0.05); }
        .online-dot {
            width: 10px;
            height: 10px;
            background: #00ff5c;
            border-radius: 50%;
            margin-right: 8px;
        }
        .user span {
            flex: 1;
        }

        .badge {
            background: #ff5c5c;
            color: white;
            font-size: 12px;
            padding: 2px 3px;
            border-radius: 10px;
            margin-left: 8px;
            width: 20px;
            text-align: center;
            font-weight: bold;
        }
        .chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #111827;
        }

        #mensagens {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .msg {
            padding: 10px 14px;
            border-radius: 10px;
            margin: 4px 0;
            max-width: 70%;
            word-wrap: break-word;
        }

        .msg.enviada {
            background: var(--accent);
            align-self: flex-end;
            color: #fff;
        }

        .msg.recebida {
            background: var(--card);
            align-self: flex-start;
        }

        form {
            display: flex;
            background: var(--card);
            padding: 10px;
            border-top: 1px solid var(--border);
        }

        form input[type=text] {
            flex: 1;
            border: none;
            padding: 10px;
            border-radius: 8px;
            background: #1e293b;
            color: white;
        }

        form button {
            margin-left: 10px;
            padding: 10px 15px;
            border: none;
            background: var(--accent);
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }

        form button:hover { background: #ff4d4d; }

        /* Scroll customizado */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #666; }
    </style>
</head>
<body>
    <header>
        <h1>Kamile</h1>
        <nav>
            <a href="feed.php">Feed</a>
            <a href="perfil.php">Perfil</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>

    <div class="container">
        <div class="sidebar">
            <h2>Usuários</h2>
            <div id="usuarios">Carregando...</div>
        </div>

        <div class="chat">
            <div id="mensagens"><p>Selecione alguém para conversar.</p></div>
            <form id="formMsg" style="display:none;">
                <input type="hidden" name="destinatario" id="destinatario">
                <input type="text" name="mensagem" id="mensagem" placeholder="Digite sua mensagem..." autocomplete="off">
                <button>Enviar</button>
            </form>
        </div>
    </div>

    <script>
        async function carregarUsuarios() {
            const res = await fetch('usuarios_chat.php');
            document.getElementById('usuarios').innerHTML = await res.text();
        }
        setInterval(carregarUsuarios, 5000);
        carregarUsuarios();

        let atual = null;

        async function carregarMensagens() {
            if (!atual) return;
            const res = await fetch('chat_backend.php?dest=' + atual);
            document.getElementById('mensagens').innerHTML = await res.text();
            const box = document.getElementById('mensagens');
            box.scrollTop = box.scrollHeight;
        }
        setInterval(carregarMensagens, 2000);

        document.addEventListener('click', e => {
            if (e.target.classList.contains('user') || e.target.closest('.user')) {
                const user = e.target.closest('.user');
                atual = user.dataset.id;
                document.getElementById('destinatario').value = atual;
                document.getElementById('formMsg').style.display = 'flex';
                carregarMensagens();
            }
        });

        document.getElementById('formMsg').onsubmit = async e => {
            e.preventDefault();
            const dados = new FormData(e.target);
            await fetch('chat_backend.php', { method: 'POST', body: dados });
            e.target.mensagem.value = '';
            carregarMensagens();
        };
    </script>
    
</body>
</html>