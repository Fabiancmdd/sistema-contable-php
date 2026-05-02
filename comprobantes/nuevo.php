<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
$me = requireRole('admin','operador');

$cuentas = db()->query(
    'SELECT id, codigo, nombre FROM cuentas WHERE imputable = 1 AND activo = 1 ORDER BY codigo'
)->fetchAll();

$errores = [];
$datos = [
    'fecha'       => date('Y-m-d'),
    'tipo'        => 'diario',
    'descripcion' => '',
    'detalles'    => [
        ['cuenta_id' => '', 'debe' => '', 'haber' => '', 'descripcion' => ''],
        ['cuenta_id' => '', 'debe' => '', 'haber' => '', 'descripcion' => ''],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $datos['fecha']       = (string)($_POST['fecha'] ?? '');
    $datos['tipo']        = (string)($_POST['tipo']  ?? 'diario');
    $datos['descripcion'] = trim((string)($_POST['descripcion'] ?? ''));
    $datos['detalles']    = $_POST['detalles'] ?? [];

    if ($datos['fecha'] === '' || !DateTime::createFromFormat('Y-m-d', $datos['fecha'])) {
        $errores[] = 'Fecha inválida.';
    }
    if (!in_array($datos['tipo'], ['diario','ingreso','egreso','traspaso'], true)) {
        $errores[] = 'Tipo inválido.';
    }

    // Limpiar y validar detalles
    $cuentaIds   = array_column($cuentas, 'id');
    $totalDebe   = 0.0;
    $totalHaber  = 0.0;
    $limpios     = [];
    foreach ($datos['detalles'] as $d) {
        $cId   = (int)($d['cuenta_id'] ?? 0);
        $debe  = (float)str_replace(',', '.', (string)($d['debe']  ?? '0'));
        $haber = (float)str_replace(',', '.', (string)($d['haber'] ?? '0'));
        $desc  = trim((string)($d['descripcion'] ?? ''));
        if ($cId === 0 && $debe == 0.0 && $haber == 0.0) {
            continue;
        }
        if ($cId === 0 || !in_array($cId, $cuentaIds)) {
            $errores[] = 'Hay un renglón sin cuenta válida.';
            continue;
        }
        if ($debe < 0 || $haber < 0) {
            $errores[] = 'No se permiten valores negativos.';
        }
        if ($debe > 0 && $haber > 0) {
            $errores[] = 'Cada renglón debe tener Debe O Haber, no ambos.';
        }
        if ($debe == 0.0 && $haber == 0.0) {
            $errores[] = 'Hay un renglón sin importe.';
        }
        $totalDebe  += $debe;
        $totalHaber += $haber;
        $limpios[]   = ['cuenta_id'=>$cId,'debe'=>$debe,'haber'=>$haber,'descripcion'=>$desc];
    }
    if (count($limpios) < 2) {
        $errores[] = 'Un comprobante necesita al menos 2 renglones.';
    }
    if (round($totalDebe, 2) !== round($totalHaber, 2)) {
        $errores[] = 'El comprobante está desbalanceado: Debe '
                   . money($totalDebe) . ' vs Haber ' . money($totalHaber) . '.';
    }
    if ($totalDebe == 0.0) {
        $errores[] = 'El total no puede ser 0.';
    }

    if (!$errores) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $numero = (int)$pdo->query('SELECT COALESCE(MAX(numero),0) + 1 FROM comprobantes')->fetchColumn();
            $stmt = $pdo->prepare(
                'INSERT INTO comprobantes (numero, fecha, tipo, descripcion, usuario_id) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([$numero, $datos['fecha'], $datos['tipo'], $datos['descripcion'], $me['id']]);
            $compId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare(
                'INSERT INTO comprobante_detalles (comprobante_id, cuenta_id, debe, haber, descripcion)
                 VALUES (?,?,?,?,?)'
            );
            foreach ($limpios as $d) {
                $stmt->execute([$compId, $d['cuenta_id'], $d['debe'], $d['haber'], $d['descripcion']]);
            }
            $pdo->commit();
            flashSet('success', 'Comprobante Nº ' . $numero . ' guardado.');
            redirect(url('/comprobantes/ver.php') . '?id=' . $compId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errores[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

layoutHead('Nuevo comprobante');
?>
<form method="post" id="frm">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php foreach ($errores as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="<?= e($datos['fecha']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
                <?php foreach (['diario','ingreso','egreso','traspaso'] as $t): ?>
                    <option value="<?= $t ?>" <?= $datos['tipo']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Descripción</label>
            <input class="form-control" name="descripcion" value="<?= e($datos['descripcion']) ?>" maxlength="255">
        </div>
    </div>

    <table class="table tabla-asiento" id="tabla">
        <thead>
            <tr>
                <th class="cuenta">Cuenta</th>
                <th>Detalle</th>
                <th style="width:140px;text-align:right">Debe</th>
                <th style="width:140px;text-align:right">Haber</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($datos['detalles'] as $i => $d): ?>
            <tr>
                <td>
                    <select name="detalles[<?= $i ?>][cuenta_id]" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($cuentas as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"
                                <?= (int)($d['cuenta_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['codigo']) ?> — <?= e($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input class="form-control form-control-sm" name="detalles[<?= $i ?>][descripcion]" value="<?= e($d['descripcion'] ?? '') ?>"></td>
                <td><input class="form-control form-control-sm campo-debe"  type="number" step="0.01" min="0"
                           name="detalles[<?= $i ?>][debe]"  value="<?= e((string)($d['debe']  ?? '')) ?>"></td>
                <td><input class="form-control form-control-sm campo-haber" type="number" step="0.01" min="0"
                           name="detalles[<?= $i ?>][haber]" value="<?= e((string)($d['haber'] ?? '')) ?>"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger btn-eliminar">×</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="totales-asiento">
                <td colspan="2" class="text-end">Totales</td>
                <td class="text-end" id="totDebe">0,00</td>
                <td class="text-end" id="totHaber">0,00</td>
                <td></td>
            </tr>
            <tr id="filaDif" class="d-none">
                <td colspan="2" class="text-end text-danger">Diferencia</td>
                <td colspan="2" class="text-end text-danger" id="diferencia">0,00</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAgregar">+ Agregar renglón</button>
    <hr>
    <button class="btn btn-primary">Guardar comprobante</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/comprobantes/index.php')) ?>">Cancelar</a>
</form>

<template id="tplFila">
    <tr>
        <td>
            <select name="detalles[__i__][cuenta_id]" class="form-select form-select-sm">
                <option value="">— Seleccionar —</option>
                <?php foreach ($cuentas as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['codigo']) ?> — <?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input class="form-control form-control-sm" name="detalles[__i__][descripcion]"></td>
        <td><input class="form-control form-control-sm campo-debe"  type="number" step="0.01" min="0" name="detalles[__i__][debe]"></td>
        <td><input class="form-control form-control-sm campo-haber" type="number" step="0.01" min="0" name="detalles[__i__][haber]"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-eliminar">×</button></td>
    </tr>
</template>

<script>
(function(){
    const tabla   = document.getElementById('tabla');
    const tbody   = tabla.querySelector('tbody');
    const tpl     = document.getElementById('tplFila').innerHTML;
    const totDebe = document.getElementById('totDebe');
    const totHaber= document.getElementById('totHaber');
    const filaDif = document.getElementById('filaDif');
    const elDif   = document.getElementById('diferencia');

    function fmt(n) { return n.toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2}); }

    function recalc() {
        let d = 0, h = 0;
        tbody.querySelectorAll('.campo-debe').forEach(i => d += parseFloat(i.value) || 0);
        tbody.querySelectorAll('.campo-haber').forEach(i => h += parseFloat(i.value) || 0);
        totDebe.textContent  = fmt(d);
        totHaber.textContent = fmt(h);
        const dif = +(d - h).toFixed(2);
        if (dif === 0) {
            filaDif.classList.add('d-none');
        } else {
            filaDif.classList.remove('d-none');
            elDif.textContent = fmt(dif);
        }
    }

    document.getElementById('btnAgregar').addEventListener('click', () => {
        const i = tbody.children.length;
        const html = tpl.replace(/__i__/g, i);
        tbody.insertAdjacentHTML('beforeend', html);
    });

    tabla.addEventListener('click', (ev) => {
        if (ev.target.classList.contains('btn-eliminar')) {
            ev.target.closest('tr').remove();
            recalc();
        }
    });

    tabla.addEventListener('input', (ev) => {
        if (ev.target.classList.contains('campo-debe') && parseFloat(ev.target.value) > 0) {
            const haber = ev.target.closest('tr').querySelector('.campo-haber');
            if (haber.value) haber.value = '';
        }
        if (ev.target.classList.contains('campo-haber') && parseFloat(ev.target.value) > 0) {
            const debe = ev.target.closest('tr').querySelector('.campo-debe');
            if (debe.value) debe.value = '';
        }
        recalc();
    });

    recalc();
})();
</script>
<?php layoutFoot();
