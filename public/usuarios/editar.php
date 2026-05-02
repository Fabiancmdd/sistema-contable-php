<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/layout.php';
$me = requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$usuario = $stmt->fetch();
if (!$usuario) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $usuario['nombre'] = trim((string)($_POST['nombre'] ?? ''));
    $usuario['email']  = trim((string)($_POST['email'] ?? ''));
    $usuario['rol']    = (string)($_POST['rol'] ?? $usuario['rol']);
    $usuario['activo'] = isset($_POST['activo']) ? 1 : 0;
    $clave             = (string)($_POST['clave'] ?? '');

    if ($usuario['nombre'] === '')   $errores[] = 'Nombre obligatorio.';
    if ($usuario['email'] === '')    $errores[] = 'Email obligatorio.';
    if (!in_array($usuario['rol'], ['admin','operador','consulta'], true)) $errores[] = 'Rol inválido.';
    if ($me['id'] === $usuario['id'] && (int)$usuario['activo'] === 0) {
        $errores[] = 'No puede desactivar su propio usuario.';
    }
    if ($clave !== '' && strlen($clave) < 6) $errores[] = 'La contraseña nueva debe tener al menos 6 caracteres.';

    if (!$errores) {
        if ($clave !== '') {
            $sql = 'UPDATE usuarios SET nombre=?, email=?, rol=?, activo=?, password_hash=? WHERE id=?';
            $params = [$usuario['nombre'], $usuario['email'], $usuario['rol'], $usuario['activo'],
                       password_hash($clave, PASSWORD_BCRYPT), $usuario['id']];
        } else {
            $sql = 'UPDATE usuarios SET nombre=?, email=?, rol=?, activo=? WHERE id=?';
            $params = [$usuario['nombre'], $usuario['email'], $usuario['rol'], $usuario['activo'], $usuario['id']];
        }
        try {
            db()->prepare($sql)->execute($params);
            flashSet('success', 'Usuario actualizado.');
            redirect('/usuarios/index.php');
        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

layoutHead('Editar usuario #' . (int)$usuario['id']);
?>
<form method="post" class="col-md-6" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input class="form-control" name="nombre" value="<?= e($usuario['nombre']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?= e($usuario['email']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
        <input class="form-control" type="password" name="clave">
    </div>
    <div class="mb-3">
        <label class="form-label">Rol</label>
        <select class="form-select" name="rol">
            <?php foreach (['admin','operador','consulta'] as $r): ?>
                <option value="<?= $r ?>" <?= $usuario['rol']===$r?'selected':'' ?>><?= rolLabel($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= (int)$usuario['activo']?'checked':'' ?>>
        <label class="form-check-label" for="activo">Activo</label>
    </div>
    <button class="btn btn-primary">Guardar</button>
    <a class="btn btn-outline-secondary" href="/usuarios/index.php">Cancelar</a>
</form>
<?php layoutFoot();
