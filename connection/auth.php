<?php

/**
 * IMS Auth helper
 *
 * This project originally used hardcoded credentials.
 * We bootstrap a DB-backed users table so credentials can be updated in Settings.
 */

function ims_ensure_users_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            password_plain VARCHAR(255) NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    // Lightweight migration for older installs that created the table before we added password_plain.
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_plain'");
        $col = $colStmt->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) NULL AFTER username");
        }
    } catch (Throwable $e) {
        // If this fails, the rest of the app can still function using password_hash.
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users");
    $count = (int)($stmt->fetch()['c'] ?? 0);
    if ($count > 0) {
        return;
    }

    $seedUsers = [
        ['username' => 'admin', 'password' => '123', 'role' => 'admin'],
        ['username' => 'staff', 'password' => '456', 'role' => 'staff'],
    ];

    $insert = $pdo->prepare("INSERT INTO users (username, password_plain, password_hash, role) VALUES (:username, :password_plain, :password_hash, :role)");
    foreach ($seedUsers as $seed) {
        $insert->execute([
            ':username' => $seed['username'],
            ':password_plain' => $seed['password'],
            ':password_hash' => password_hash($seed['password'], PASSWORD_DEFAULT),
            ':role' => $seed['role'],
        ]);
    }
}

function ims_get_user_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();
    return $row ? $row : null;
}

function ims_get_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ? $row : null;
}

function ims_verify_login(PDO $pdo, string $username, string $password): ?array
{
    $user = ims_get_user_by_username($pdo, $username);
    if (!$user) {
        return null;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function ims_update_username(PDO $pdo, string $currentUsername, string $newUsername): void
{
    $stmt = $pdo->prepare("UPDATE users SET username = :newUsername WHERE username = :currentUsername");
    $stmt->execute([
        ':newUsername' => $newUsername,
        ':currentUsername' => $currentUsername,
    ]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Username update failed.');
    }
}

function ims_update_username_by_id(PDO $pdo, int $id, string $newUsername): void
{
    $stmt = $pdo->prepare("UPDATE users SET username = :newUsername WHERE id = :id");
    $stmt->execute([
        ':newUsername' => $newUsername,
        ':id' => $id,
    ]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Username update failed.');
    }
}

function ims_update_password(PDO $pdo, string $username, string $newPassword): void
{
    $stmt = $pdo->prepare("UPDATE users SET password_plain = :plain, password_hash = :hash WHERE username = :username");
    $stmt->execute([
        ':plain' => $newPassword,
        ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':username' => $username,
    ]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Password update failed.');
    }
}

function ims_update_password_by_id(PDO $pdo, int $id, string $newPassword): void
{
    $stmt = $pdo->prepare("UPDATE users SET password_plain = :plain, password_hash = :hash WHERE id = :id");
    $stmt->execute([
        ':plain' => $newPassword,
        ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $id,
    ]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Password update failed.');
    }
}

function ims_get_login_credentials(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, username, role, password_plain FROM users ORDER BY role, username");
    $rows = $stmt->fetchAll();
    return $rows ? $rows : [];
}

function ims_validate_username(string $username): ?string
{
    $username = trim($username);
    if ($username === '') {
        return 'Username is required.';
    }
    if (strlen($username) < 3 || strlen($username) > 50) {
        return 'Username must be 3 to 50 characters.';
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        return 'Username can contain only letters, numbers, and underscore.';
    }
    return null;
}

function ims_validate_password(string $password): ?string
{
    if ($password === '') {
        return 'Password is required.';
    }
    if (strlen($password) < 3) {
        return 'Password must be at least 3 characters.';
    }
    return null;
}
