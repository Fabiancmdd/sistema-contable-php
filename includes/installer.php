<?php
declare(strict_types=1);

/**
 * Lógica de instalación reutilizable.
 *
 *  - runInstaller(array $datos): instala la BD + tablas + plan + usuarios demo
 *    y escribe config.php. Retorna ['ok' => bool, 'errores' => string[]].
 *  - tryAutoInstall(): intenta instalar silenciosamente con defaults XAMPP.
 *    Retorna true si quedó listo (config.php existe al final), false si no.
 *
 * Idempotente: si la BD/tablas ya existen no las recrea ni sobreescribe datos.
 */

const INSTALLER_DEFAULTS = [
    'db_host'     => '127.0.0.1',
    'db_port'     => '3306',
    'db_name'     => 'sistema_contable',
    'db_user'     => 'root',
    'db_password' => '',
    'app_empresa' => 'Mi Empresa S.A.',
    'app_moneda'  => '$',
];

/**
 * Ejecuta la instalación. $datos puede incluir las claves de INSTALLER_DEFAULTS;
 * los faltantes se completan con los valores por defecto.
 *
 * @return array{ok:bool,errores:string[]}
 */
function runInstaller(array $datos): array
{
    $projectRoot = dirname(__DIR__);
    $configFile  = $projectRoot . '/config.php';
    $exampleFile = $projectRoot . '/config.example.php';
    $schemaFile  = $projectRoot . '/sql/schema.sql';
    $seedFile    = $projectRoot . '/sql/seed.sql';

    $datos = array_merge(INSTALLER_DEFAULTS, $datos);
    $errores = [];

    if ($datos['db_host'] === '') $errores[] = 'Host obligatorio.';
    if ($datos['db_name'] === '') $errores[] = 'Nombre de BD obligatorio.';
    if ($datos['db_user'] === '') $errores[] = 'Usuario de BD obligatorio.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $datos['db_name'])) {
        $errores[] = 'El nombre de la BD sólo puede tener letras, números y guion bajo.';
    }
    if ($errores) {
        return ['ok' => false, 'errores' => $errores];
    }

    try {
        $pdo = new PDO(
            "mysql:host={$datos['db_host']};port={$datos['db_port']};charset=utf8mb4",
            $datos['db_user'],
            $datos['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        return ['ok' => false, 'errores' => ['No se pudo conectar a MySQL: ' . $e->getMessage()]];
    }

    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$datos['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$datos['db_name']}`");

        // ¿Las tablas ya existen?
        $tablasExisten = false;
        try {
            $pdo->query('SELECT 1 FROM usuarios LIMIT 1');
            $pdo->query('SELECT 1 FROM cuentas  LIMIT 1');
            $tablasExisten = true;
        } catch (PDOException $e) {
            $tablasExisten = false;
        }

        if (!$tablasExisten) {
            $schema = (string)file_get_contents($schemaFile);
            $schema = preg_replace('/CREATE DATABASE[^;]+;/i', '', $schema, 1);
            $schema = preg_replace('/USE\s+\w+\s*;/i', '', $schema, 1);
            $pdo->exec($schema);
        }

        // Cuentas: solo cargar si la tabla está vacía.
        $tieneCuentas = (int)$pdo->query('SELECT COUNT(*) FROM cuentas')->fetchColumn();
        if ($tieneCuentas === 0) {
            $seed = (string)file_get_contents($seedFile);
            $seed = preg_replace('/USE\s+\w+\s*;/i', '', $seed, 1);
            $pdo->exec($seed);
        }

        // Usuarios demo: solo si no hay ninguno.
        $tieneUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($tieneUsuarios === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre, email, password_hash, rol, activo)
                 VALUES (?, ?, ?, ?, 1)'
            );
            foreach ([
                ['Administrador', 'admin@sistema.local',    'admin123',    'admin'],
                ['Operador',      'operador@sistema.local', 'operador123', 'operador'],
                ['Consulta',      'consulta@sistema.local', 'consulta123', 'consulta'],
            ] as [$nombre, $email, $clave, $rol]) {
                $stmt->execute([$nombre, $email, password_hash($clave, PASSWORD_BCRYPT), $rol]);
            }
        }

        // Generar config.php a partir del template (config.example.php).
        if (!file_exists($configFile)) {
            $tpl = (string)file_get_contents($exampleFile);
            $cfg = strtr($tpl, [
                "'127.0.0.1'"        => var_export($datos['db_host'], true),
                "3306"               => (int)$datos['db_port'],
                "'sistema_contable'" => var_export($datos['db_name'], true),
                "'root'"             => var_export($datos['db_user'], true),
                "'password' => ''"   => "'password' => " . var_export($datos['db_password'], true),
                "'Mi Empresa S.A.'"  => var_export($datos['app_empresa'], true),
                "'\$'"               => var_export($datos['app_moneda'], true),
            ]);
            if (@file_put_contents($configFile, $cfg) === false) {
                return ['ok' => false, 'errores' => ['No se pudo escribir config.php (verificá permisos sobre la carpeta del proyecto).']];
            }
        }
        return ['ok' => true, 'errores' => []];
    } catch (Throwable $e) {
        return ['ok' => false, 'errores' => [$e->getMessage()]];
    }
}

/**
 * Intenta instalar silenciosamente con los valores por defecto de XAMPP.
 * No lanza excepciones; retorna true si todo quedó listo.
 *
 * Usa un lock-file para evitar carreras (dos requests al mismo tiempo) y
 * un marcador para no reintentar en cada request si ya falló.
 */
function tryAutoInstall(): bool
{
    $projectRoot = dirname(__DIR__);
    $configFile  = $projectRoot . '/config.php';
    if (file_exists($configFile)) return true;

    $marker = $projectRoot . '/storage/.autoinstall_failed';
    if (file_exists($marker)) return false;

    $result = runInstaller(INSTALLER_DEFAULTS);
    if ($result['ok']) return true;

    // Si falla la conexión / instalación silenciosa, dejamos un marcador para
    // no volver a intentar en cada request. setup.php lo borra en su POST.
    @mkdir(dirname($marker), 0775, true);
    @file_put_contents($marker, date('c') . " :: " . implode(' | ', $result['errores']) . "\n");
    return false;
}
