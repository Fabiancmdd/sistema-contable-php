<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$usuarios = db()->query('SELECT id, nombre, email, rol, activo, creado_en FROM usuarios ORDER BY id')->fetchAll();
layoutHead('Usuarios');
?>
<div class="d-flex justify-content-between mb-3">
    <div></div>
    <a class="btn btn-primary" href="<?= e(url('/usuarios/nuevo.php')) ?>">+ Nuevo usuario</a>
</div>
<table class="table table-striped">
    <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Alta</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e(rolLabel($u['rol'])) ?></td>
            <td><?= $u['activo'] ? 'Sí' : 'No' ?></td>
            <td><?= e($u['creado_en']) ?></td>
            <td><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/usuarios/editar.php')) ?>?id=<?= (int)$u['id'] ?>">Editar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php layoutFoot();
