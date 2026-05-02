<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Devuelve el prefijo URL del proyecto (sin slash final).
 *
 *  - Si servís la raíz del proyecto directo (ej. `php -S 0.0.0.0:8000`),
 *    devuelve "".
 *  - Si el proyecto está bajo XAMPP en `htdocs/<nombre>/`, devuelve
 *    `/<nombre>` y se aplica automáticamente a todas las URLs internas.
 *
 * Estrategia: el archivo `helpers.php` vive en `<projectRoot>/includes/`,
 * así que sabemos cuál es el filesystem path del proyecto. Comparándolo
 * contra `SCRIPT_FILENAME` averiguamos qué porción de `SCRIPT_NAME` es
 * el prefijo URL.
 */
function basePath(): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $scriptFile  = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName  = (string)($_SERVER['SCRIPT_NAME'] ?? '');

    if ($projectRoot !== '' && $scriptFile !== '' && str_starts_with($scriptFile, $projectRoot)) {
        $rel = substr($scriptFile, strlen($projectRoot)); // ej. "/login.php" o "/usuarios/nuevo.php"
        if ($rel !== '' && str_ends_with($scriptName, $rel)) {
            return $cache = substr($scriptName, 0, strlen($scriptName) - strlen($rel));
        }
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
