<?php
/**
 * Instalador del sistema contable.
 *
 * Crea la base de datos, importa el esquema, carga el plan de cuentas
 * y crea tres usuarios de ejemplo (admin / operador / consulta).
 *
 * Uso (CLI):
 *   php install.php
 *
 * Uso (web): abrir http://localhost:8000/install.php una sola vez y
 * luego borrar este archivo.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    fwrite(STDERR, "Falta config.php. Copie config.example.php a config.php y ajuste credenciales.\n");
    exit(1);
}
$config = require $configFile;
$db = $config['db'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};charset={$db['charset']}",
        $db['user'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo conectar al servidor MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "==> Aplicando schema.sql\n";
$schema = file_get_contents(__DIR__ . '/sql/schema.sql');
$pdo->exec($schema);

echo "==> Aplicando seed.sql (plan de cuentas)\n";
$seed = file_get_contents(__DIR__ . '/sql/seed.sql');
$pdo->exec($seed);

echo "==> Creando usuarios de ejemplo\n";
$pdo->exec("USE `{$db['name']}`");
$stmt = $pdo->prepare(
    "INSERT INTO usuarios (nombre, email, password_hash, rol, activo)
     VALUES (?, ?, ?, ?, 1)"
);
$usuarios = [
    ['Administrador', 'admin@sistema.local',    'admin123',    'admin'],
    ['Operador',      'operador@sistema.local', 'operador123', 'operador'],
    ['Consulta',      'consulta@sistema.local', 'consulta123', 'consulta'],
];
foreach ($usuarios as [$nombre, $email, $clave, $rol]) {
    $stmt->execute([$nombre, $email, password_hash($clave, PASSWORD_BCRYPT), $rol]);
    echo "    - {$email} (clave: {$clave}) rol={$rol}\n";
}

echo "\nListo. Borre install.php por seguridad y abra public/login.php\n";
