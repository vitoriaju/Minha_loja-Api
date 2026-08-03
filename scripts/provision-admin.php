<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!in_array('--replace-all-users', $argv, true)) {
    fwrite(STDERR, "Use --replace-all-users para confirmar a substituicao de todas as contas.\n");
    exit(1);
}

$name = trim((string) getenv('ADMIN_NAME'));
$email = strtolower(trim((string) getenv('ADMIN_EMAIL')));
$password = (string) getenv('ADMIN_INITIAL_PASSWORD');
$generatedPassword = false;

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Defina ADMIN_NAME e ADMIN_EMAIL com valores validos.\n");
    exit(1);
}

function generate_strong_password(int $length = 24): string
{
    $groups = [
        'ABCDEFGHJKLMNPQRSTUVWXYZ',
        'abcdefghijkmnopqrstuvwxyz',
        '23456789',
        '!@#$%&*+-_=?.',
    ];
    $characters = [];

    foreach ($groups as $group) {
        $characters[] = $group[random_int(0, strlen($group) - 1)];
    }

    $all = implode('', $groups);
    while (count($characters) < $length) {
        $characters[] = $all[random_int(0, strlen($all) - 1)];
    }

    for ($i = count($characters) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
    }

    return implode('', $characters);
}

function password_is_strong(string $password): bool
{
    return strlen($password) >= 16
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

if ($password === '') {
    $password = generate_strong_password();
    $generatedPassword = true;
}

if (!password_is_strong($password)) {
    fwrite(STDERR, "ADMIN_INITIAL_PASSWORD deve ter ao menos 16 caracteres, com maiuscula, minuscula, numero e simbolo.\n");
    exit(1);
}

session_save_path(sys_get_temp_dir());
require_once __DIR__ . '/../config/config.php';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $adminId = $stmt->fetchColumn();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $columns = $pdo->query('SHOW COLUMNS FROM usuarios')->fetchAll(PDO::FETCH_COLUMN);
    $hasName = in_array('nome', $columns, true);
    $hasLegacyPassword = in_array('senha', $columns, true);

    if ($adminId) {
        $sets = ['senha_hash = ?', "perfil = 'admin'", 'email_verificado = 1'];
        $values = [$hash];
        if ($hasName) {
            array_unshift($sets, 'nome = ?');
            array_unshift($values, $name);
        }
        if ($hasLegacyPassword) {
            $sets[] = 'senha = NULL';
        }
        $values[] = $adminId;
        $stmt = $pdo->prepare('UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($values);
    } else {
        $insertColumns = ['email', 'senha_hash', 'perfil', 'email_verificado'];
        $placeholders = ['?', '?', "'admin'", '1'];
        $values = [$email, $hash];
        if ($hasName) {
            array_unshift($insertColumns, 'nome');
            array_unshift($placeholders, '?');
            array_unshift($values, $name);
        }
        if ($hasLegacyPassword) {
            $insertColumns[] = 'senha';
            $placeholders[] = 'NULL';
        }
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (' . implode(', ', $insertColumns) . ') VALUES ('
            . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($values);
        $adminId = $pdo->lastInsertId();
    }

    // Bases antigas usam RESTRICT em vendas.usuario_id. Reatribui o historico
    // ao unico administrador antes de remover os logins obsoletos.
    $hasSalesTable = (bool) $pdo->query("SHOW TABLES LIKE 'vendas'")->fetchColumn();
    if ($hasSalesTable) {
        $stmt = $pdo->prepare('UPDATE vendas SET usuario_id = ? WHERE usuario_id IS NOT NULL AND usuario_id <> ?');
        $stmt->execute([$adminId, $adminId]);
    }

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id <> ?');
    $stmt->execute([$adminId]);
    $removedUsers = $stmt->rowCount();

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Falha ao provisionar administrador: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, "Administrador provisionado: {$email}\n");
fwrite(STDOUT, "Contas removidas: {$removedUsers}\n");
if ($generatedPassword) {
    fwrite(STDOUT, "Senha inicial (guarde agora): {$password}\n");
}
