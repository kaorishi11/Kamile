<?php
session_start();
require 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $foto = null;


    $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Este e-mail já está cadastrado!'); window.history.back();</script>";
        exit;
    }

    if (!empty($_FILES['foto']['name'])) {
        $pasta = 'uploads/';
        if (!is_dir($pasta)) mkdir($pasta, 0777, true);

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            $foto = $pasta . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $foto);
        } else {
            $erro = 'Tipo de arquivo inválido. Apenas JPG, PNG ou GIF.';
        }
    }

    if (!$erro) {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, foto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $nome, $email, $senha, $foto);
        if ($stmt->execute()) {
            header('Location: index.php?msg=conta_criada');
            exit;
        } else {
            $erro = 'Erro ao cadastrar: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Kamile - Cadastro</title>
    <style>
        body{
            padding: 200px 200px;
        }
        h1{
            color: #ff6b6b;
            text-align: center;
        }
    </style>
</head>
    <body>
    <h1>Kamile</h1>
        <div class="card">
            <h2>Registrar</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="nome" placeholder="Nome" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <input type="file" name="foto" id="foto">
                <button type="submit">Entrar</button>
            </form>
            <a href="index.php">Logar</a>
            <?php if (!empty($erro)) echo '<p class="erro">'.$erro.'</p>'; ?>
        </div>
    </body>
</html>