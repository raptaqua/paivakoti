<?php
/**
 * auth.php — Session & access control helpers
 */

require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // set true when using HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function currentUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireLogin(): array {
    $user = currentUser();
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    // Force a password change before anything else (default admin password etc.)
    if (!empty($user['must_change']) && !in_array(basename($_SERVER['SCRIPT_NAME']), ['profile.php', 'logout.php'], true)) {
        $prefix = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin' ? '../' : '';
        header('Location: ' . $prefix . 'profile.php?force=1');
        exit;
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        header('Location: dashboard.php?err=noperm');
        exit;
    }
    return $user;
}

function isAdmin(): bool {
    $u = currentUser();
    return $u && $u['role'] === 'admin';
}

function login(string $username, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username=? AND active=1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Still using the documented default password → must change it
        $mustChange = !empty($user['must_change_password']) || password_verify('admin1234', $user['password_hash']);
        startSession();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
            'must_change' => $mustChange,
        ];
        return true;
    }
    return false;
}

function logout(): void {
    startSession();
    session_destroy();
    header('Location: index.php');
    exit;
}

function generateResetToken(string $username): ?string {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE username=? AND active=1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user) return null;

    $token = bin2hex(random_bytes(20));
    $expires = time() + 3600; // 1 hour
    $db->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?")
       ->execute([$token, $expires, $user['id']]);
    return $token;
}

function resetPasswordWithToken(string $token, string $newPassword): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > ?");
    $stmt->execute([$token, time()]);
    $user = $stmt->fetch();
    if (!$user) return false;

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password_hash=?, must_change_password=0, reset_token=NULL, reset_expires=NULL WHERE id=?")
       ->execute([$hash, $user['id']]);
    return true;
}

function changePassword(int $userId, string $newPassword): void {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    getDB()->prepare("UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?")
           ->execute([$hash, $userId]);
}
