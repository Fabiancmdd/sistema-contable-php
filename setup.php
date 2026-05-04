<?php
/**
 * Asistente de instalación web.
 *
 * Si falta `config.php` en la raíz del proyecto, este script muestra un
 * formulario para configurar la base de datos y crear todo automáticamente:
 *  - genera config.php
 *  - crea la base de datos
 *  - importa el schema y el plan de cuentas
 *  - crea los 3 usuarios demo
 *
 * Una vez que existe config.php, el setup queda deshabilitado por defecto.
 * Para volver a usarlo se puede borrar config.php o pasar `?force=1`.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/installer.php';

$projectRoot = __DIR__;
$configFile  = $projectRoot . '/config.php';

// Si ya está configurado, no permitir re-correr (a menos que ?force=1).
$alreadyConfigured = file_exists($configFile) && empty($_GET['force']);

// Base path = directorio del script (ej. "/sistema-contable" en XAMPP, "" si servís la raíz)
$_script = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($_script)), '/');
if ($base === '.') { $base = ''; }

$errores = [];
$ok      = false;

$datos = INSTALLER_DEFAULTS;

if (!$alreadyConfigured && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $v) {
        $datos[$k] = (string)($_POST[$k] ?? $v);
    }
    // Borrar marcador de auto-install fallido si existía: el usuario está
    // configurando manualmente, así que la próxima visita no necesita reintentar.
    @unlink($projectRoot . '/storage/.autoinstall_failed');

    $result  = runInstaller($datos);
    $ok      = $result['ok'];
    $errores = $result['errores'];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalador · Sistema Contable</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:720px">
    <h1 class="h3 mb-4">Instalador del Sistema Contable</h1>

<?php if ($alreadyConfigured): ?>
    <div class="alert alert-info">
        El sistema ya está configurado (<code>config.php</code> existe).
        <br>Para reinstalar pasá <code>?force=1</code> en la URL, pero ojo: esto NO borra la BD,
        sólo permite reescribir <code>config.php</code> y reimportar el plan de cuentas.
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($base . '/login.php') ?>">Ir al login</a>

<?php elseif ($ok): ?>
    <div class="alert alert-success">
        <h5>¡Instalación completa!</h5>
        <p class="mb-2">Se creó la base <code><?= htmlspecialchars($datos['db_name']) ?></code>,
        se importó el plan de cuentas y se generaron 3 usuarios demo:</p>
        <ul class="mb-2">
            <li><code>admin@sistema.local</code> / <code>admin123</code> (admin)</li>
            <li><code>operador@sistema.local</code> / <code>operador123</code> (operador)</li>
            <li><code>consulta@sistema.local</code> / <code>consulta123</code> (consulta)</li>
        </ul>
        <p class="mb-0"><strong>Importante:</strong> cambiá las contraseñas demo cuando entres
        y luego borrá <code>public/setup.php</code> y <code>install.php</code> por seguridad.</p>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($base . '/login.php') ?>">Ir al login</a>

<?php else: ?>
    <p class="text-muted">No se encontró <code>config.php</code>. Completá los datos de tu MySQL/MariaDB y
    el sistema queda listo en un click.</p>

    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>

    <form method="post" class="card p-4 shadow-sm bg-white">
        <h5 class="mb-3">Conexión a la base de datos</h5>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Host</label>
                <input class="form-control" name="db_host" value="<?= htmlspecialchars($datos['db_host']) ?>" required>
                <small class="text-muted">XAMPP por defecto: <code>127.0.0.1</code></small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Puerto</label>
                <input class="form-control" name="db_port" value="<?= htmlspecialchars($datos['db_port']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Usuario</label>
                <input class="form-control" name="db_user" value="<?= htmlspecialchars($datos['db_user']) ?>" required>
                <small class="text-muted">XAMPP por defecto: <code>root</code></small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Contraseña</label>
                <input class="form-control" type="password" name="db_password" value="<?= htmlspecialchars($datos['db_password']) ?>">
                <small class="text-muted">XAMPP por defecto: vacío</small>
            </div>
            <div class="col-md-12">
                <label class="form-label">Nombre de la base de datos</label>
                <input class="form-control" name="db_name" value="<?= htmlspecialchars($datos['db_name']) ?>" required>
                <small class="text-muted">Si no existe, se crea automáticamente.</small>
            </div>
        </div>

        <h5 class="mt-4 mb-3">Datos de la empresa</h5>
        <div class="row g-3">
            <div class="col-md-9">
                <label class="form-label">Nombre / Razón social</label>
                <input class="form-control" name="app_empresa" value="<?= htmlspecialchars($datos['app_empresa']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Símbolo de moneda</label>
                <input class="form-control" name="app_moneda" value="<?= htmlspecialchars($datos['app_moneda']) ?>">
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary btn-lg w-100">Instalar</button>
        </div>
    </form>
<?php endif; ?>
</main>
</body>
</html>
