<?php
if (session_status() === PHP_SESSION_NONE) {
    $defaultPath = ini_get('session.save_path') ?: sys_get_temp_dir();
    if (!@is_writable($defaultPath)) {
        $local = __DIR__ . '/../data/sessions';
        if (!is_dir($local)) @mkdir($local, 0700, true);
        session_save_path($local);
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['uid'])) return null;
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int)$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u) { logout_user(); return null; }
    if ((int)$u['is_blocked'] === 1) { logout_user(); return null; }
    return $u;
}
function require_login(PDO $pdo): array {
    $u = current_user($pdo);
    if (!$u) { header('Location: /login.php'); exit; }
    return $u;
}
function require_admin(PDO $pdo): array {
    $u = require_login($pdo);
    if ((int)$u['is_admin'] !== 1) { http_response_code(403); die('غير مصرح'); }
    return $u;
}
function login_user(int $uid): void { $_SESSION['uid'] = $uid; }
function logout_user(): void { $_SESSION = []; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
        http_response_code(400); die('CSRF token غير صالح');
    }
}
