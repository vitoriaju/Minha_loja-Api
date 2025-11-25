<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $perfil = trim($_POST['perfil'] ?? 'user'); 

    
    if (!$email || !$senha || !in_array($perfil, ['user','admin'])) {
        flash_set('erro', 'Preencha todos os campos corretamente!');
        header('Location: ../views/cadastrar.php');
        exit;
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha_hash, perfil) VALUES (?, ?, ?)");
        $stmt->execute([$email, $hash, $perfil]);

        flash_set('sucesso', 'Conta criada com sucesso! Você já pode fazer login.');
        header('Location: ../index.php');
        exit;

    } catch (PDOException $e) {
        
        if ($e->getCode() === "23000") {
            flash_set('erro', 'Este e-mail já está cadastrado!');
        } else {
            flash_set('erro', 'Erro ao criar conta: ' . $e->getMessage());
        }
        header('Location: ../views/cadastrar.php');
        exit;
    }

} else {
    header('Location: ../index.php');
    exit;
}
