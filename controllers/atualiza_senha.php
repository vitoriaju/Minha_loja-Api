<?php
require_once __DIR__ . '/../config/conexao.php';

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';
$senha_confirma = $_POST['senha_confirma'] ?? '';

error_log("DEBUG: Email recebido = '$email' | Senha recebida = '$senha'");

if (empty($email) || empty($senha) || empty($senha_confirma)) {
    header("Location: ../views/nova_senha.php?email=" . urlencode($email) . "&erro=Preencha todos os campos.");
    exit;
}

if ($senha !== $senha_confirma) {
    header("Location: ../views/nova_senha.php?email=" . urlencode($email) . "&erro=Senhas não conferem.");
    exit;
}


$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
$stmt->bind_param("ss", $senhaHash, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: ../views/login.php?sucesso=Senha atualizada com sucesso");
    exit;
} else {
    header("Location: ../views/nova_senha.php?email=" . urlencode($email) . "&erro=Erro ao atualizar a senha.");
    exit;
}
