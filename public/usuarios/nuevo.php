<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$errores = [];
$datos = ['nombre' => '', 'email' => '', 'rol' => 'operador', 'activo' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $datos['nombre'] = trim((string)($_POST['nombre'] ?? ''));
    $datos['email']  = trim((string)($_POST['email'] ?? ''));
    $datos['rol']    = (string)($_POST['rol'] ?? 'operador');
    $datos['activo'] = isset($_POST['activo']) ? 1 : 0;
    $clave           = (string)($_POST['clave'] ?? '');

    if ($datos['nombre'] === '')          $errores[] = 'Nombre obligatorio.';
    if ($datos['email'] === '')           $errores[] = 'Email obligatorio.';
    if (!in_array($datos['rol'], ['admin','operador','consulta'], true)) $errores[] = 'Rol inválido.';
    if (strlen($clave) < 6)               $errores[] = 'La contraseña debe tener al menos 6 caracteres.';

    if (!$errores) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([
                $datos['nombre'], $datos['email'],
                password_hash($clave, PASSWORD_BCRYPT),
                $datos['rol'], $datos['activo'],
            ]);
            flashSet('success', 'Usuario creado correctamente.');
            redirect('/usuarios/index.php');
        } catch (PDOException $e) {
            $errores[] = 'No se pudo crear (¿email duplicado?). ' . $e->getMessage();
        }
    }
}

layoutHead('Nuevo usuario');
?>
<form method="post" class="col-md-6" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="nombre" value="<?= e($datos['nombre']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?= e($datos['email']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input class="form-control" type="password" name="clave" required minlength="6">
    </div>
    <div class="mb-3">
        <label class="form-label">Rol</label>
        <select class="form-select" name="rol">
            <?php foreach (['admin','operador','consulta'] as $r): ?>
                <option value="<?= $r ?>" <?= $datos['rol']===$r?'selected':'' ?>><?= rolLabel($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= $datos['activo']?'checked':'' ?>>
        <label class="form-check-label" for="activo">Activo</label>
    </div>
    <button class="btn btn-primary">Guardar</button>
    <a class="btn btn-outline-secondary" href="/usuarios/index.php">Cancelar</a>
</form>
<?php layoutFoot();
