<?php
declare(strict_types=1);

/**
 * Si todavía no existe config.php intenta auto-instalación silenciosa con los
 * defaults de XAMPP (root@127.0.0.1, sin password, db sistema_contable). Si
 * eso funciona el usuario nunca ve el wizard. Si falla (por ejemplo MySQL
 * tiene otra contraseña) cae al asistente web (setup.php).
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

    // 1) Intentar auto-instalación silenciosa (XAMPP por defecto).
    require_once __DIR__ . '/installer.php';
    if (tryAutoInstall()) {
        return; // config.php ya existe, db()/appConfig() siguen normalmente.
    }

    // 2) Fallback: redirigir al wizard manual.
    // Calcular base path (mismo método que helpers::basePath, duplicado acá
    // porque helpers.php podría no haberse cargado todavía).
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $scriptFile  = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $base = '';
    if ($projectRoot !== '' && $scriptFile !== '' && str_starts_with($scriptFile, $projectRoot)) {
        $rel = substr($scriptFile, strlen($projectRoot));
        if ($rel !== '' && str_ends_with($script, $rel)) {
            $base = substr($script, 0, strlen($script) - strlen($rel));
        }
    }
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('Location: ' . $base . '/setup.php');
        exit;
    }
    http_response_code(500);
    exit('Falta config.php. Abrí el instalador en /setup.php o copiá config.example.php a config.php.');
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
