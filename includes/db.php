<?php
declare(strict_types=1);

/**
 * Si todavía no existe config.php redirige al asistente web (setup.php).
 * Esto se ejecuta antes de db() y appConfig() para que cualquier página
 * disparada antes de configurar muestre el wizard en vez de un error.
 */
function _ensureConfigured(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;
    $configFile = dirname(__DIR__) . '/config.php';
    if (file_exists($configFile)) return;

    // Si ya estamos en setup.php no redirigir.
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_ends_with($script, '/setup.php')) return;

    // Calcular base path para redirigir correctamente bajo XAMPP/subdir
    $pos  = strrpos($script, '/public/');
    $base = $pos !== false ? substr($script, 0, $pos + strlen('/public')) : '';
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('Location: ' . $base . '/setup.php');
        exit;
    }
    http_response_code(500);
    exit('Falta config.php. Abrí el instalador en /public/setup.php o copiá config.example.php a config.php.');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    _ensureConfigured();
    $configFile = dirname(__DIR__) . '/config.php';
    $config = require $configFile;
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

function appConfig(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    _ensureConfigured();
    $cfg = require dirname(__DIR__) . '/config.php';
    return $cfg;
}
