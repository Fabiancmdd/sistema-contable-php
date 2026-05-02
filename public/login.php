<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';

if (currentUser()) {
    redirect('/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $clave = (string)($_POST['clave'] ?? '');
    if (login($email, $clave)) {
        $r = $_GET['redirect'] ?? '/index.php';
        if (!is_string($r) || !preg_match('#^/[\w/.\-?=&%]*$#', $r)) {
            $r = '/index.php';
        }
        redirect($r);
    }
    $error = 'Credenciales inválidas o usuario inactivo';
}

layoutHead('Iniciar sesión');
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-3">Iniciar sesión</h4>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" required autofocus
                               value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input class="form-control" type="password" name="clave" required>
                    </div>
                    <button class="btn btn-primary w-100">Entrar</button>
                </form>
                <hr>
                <small class="text-muted">
                    Usuarios de prueba:<br>
                    admin@sistema.local / admin123<br>
                    operador@sistema.local / operador123<br>
                    consulta@sistema.local / consulta123
                </small>
            </div>
        </div>
    </div>
</div>
<?php layoutFoot();
