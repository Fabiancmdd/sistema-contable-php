<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Devuelve el usuario logueado o null.
 */
function currentUser(): ?array
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $stmt = db()->prepare('SELECT id, nombre, email, rol, activo FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $u = $stmt->fetch();
    if ($u === false || (int)$u['activo'] !== 1) {
        session_destroy();
        return null;
    }
    return $cache = $u;
}

/**
 * Si no hay usuario logueado, redirige al login.
 */
function requireLogin(): array
{
    $u = currentUser();
    if ($u === null) {
        $redirect = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . url('/login.php') . '?redirect=' . urlencode($redirect));
        exit;
    }
    return $u;
}

/**
 * Exige que el usuario tenga uno de los roles indicados.
 * Si no, muestra 403.
 */
function requireRole(string ...$roles): array
{
    $u = requireLogin();
    if (!in_array($u['rol'], $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/forbidden.php';
        exit;
    }
    return $u;
}

function hasRole(string ...$roles): bool
{
    $u = currentUser();
    return $u !== null && in_array($u['rol'], $roles, true);
}

function login(string $email, string $clave): bool
{
    $stmt = db()->prepare('SELECT id, password_hash, activo FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u === false || (int)$u['activo'] !== 1) {
        return false;
    }
    if (!password_verify($clave, $u['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int)$u['id'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        exit('Token CSRF inválido');
    }
}
