<?php
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        header("Location: ../views/login.php?erro=Preencha todos os campos");
        exit;
    }

    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        
        if (password_verify($senha, $usuario['senha'])) {
           
            session_start();
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_email'] = $usuario['email'];
            header("Location: ../views/inicial.php"); 
            exit;
        } else {
            header("Location: ../index.php?erro=Senha incorreta");
            exit;
        }
    } else {
        header("Location: ../index.php?erro=E-mail não encontrado");
        exit;
    }
}
?>
