<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Devuelve el prefijo URL del proyecto (sin slash final).
 *
 * - Si el document root del servidor apunta a `public/` (ej. `php -S` o un
 *   vhost configurado), devuelve "".
 * - Si el proyecto está bajo XAMPP en `htdocs/<nombre>/public/`, devuelve
 *   `/<nombre>/public`.
 *
 * Detecta esto buscando el segmento `/public/` en `SCRIPT_NAME`.
 */
function basePath(): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $pos = strrpos($script, '/public/');
    if ($pos !== false) {
        return $cache = substr($script, 0, $pos + strlen('/public'));
    }
    return $cache = '';
}

/**
 * Construye una URL interna anteponiendo basePath().
 * Pasar la ruta empezando con `/`, ej: url('/login.php').
 */
function url(string $path): string
{
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return basePath() . $path;
}

function money($n): string
{
    $cfg = appConfig();
    $simbolo = $cfg['app']['moneda'] ?? '$';
    return $simbolo . ' ' . number_format((float)$n, 2, ',', '.');
}

function flashSet(string $tipo, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

function flashRender(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['tipo']) . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function tipoCuentaLabel(string $tipo): string
{
    return [
        'activo'     => 'Activo',
        'pasivo'     => 'Pasivo',
        'patrimonio' => 'Patrimonio',
        'ingreso'    => 'Ingreso',
        'egreso'     => 'Egreso',
    ][$tipo] ?? $tipo;
}

function rolLabel(string $rol): string
{
    return [
        'admin'    => 'Administrador',
        'operador' => 'Operador',
        'consulta' => 'Consulta',
    ][$rol] ?? $rol;
}
