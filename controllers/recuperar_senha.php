<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nova_senha = trim($_POST['nova_senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');

    if (empty($email) || empty($nova_senha) || empty($confirmar_senha)) {
        flash_set('erro', 'Preencha todos os campos.');
        header('Location: ../views/recuperar.php');
        exit;
    }

    if ($nova_senha !== $confirmar_senha) {
        flash_set('erro', 'As senhas não coincidem.');
        header('Location: ../views/recuperar.php');
        exit;
    }

    
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na preparação da query SELECT: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        flash_set('erro', 'E-mail não encontrado.');
        header('Location: ../views/recuperar.php');
        exit;
    }

    
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $sql_update = "UPDATE usuarios SET senha_hash = ? WHERE email = ?";
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        die("Erro na preparação da query UPDATE: " . $conn->error);
    }
    $stmt_update->bind_param("ss", $senha_hash, $email);

    if ($stmt_update->execute()) {
        flash_set('sucesso', 'Senha alterada com sucesso! Agora você já pode fazer login.');
    } else {
        flash_set('erro', 'Erro ao atualizar senha: ' . $stmt_update->error);
    }

    $stmt->close();
    $stmt_update->close();
    $conn->close();

    header('Location: ../views/recuperar.php');
    exit;
}
