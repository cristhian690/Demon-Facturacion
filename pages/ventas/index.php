<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/dispatches.php';
require_once '../../includes/finance.php';

$ventas = get_data('ventas');
$clientes = array_column(get_data('clientes'), null, 'id');
$almacenes = array_column(get_data('almacenes'), null, 'id');
$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$entrega = isset($_GET['entrega']) && is_string($_GET['entrega']) ? trim($_GET['entrega']) : '';
$pago = isset($_GET['pago']) && is_string($_GET['pago']) ? trim($_GET['pago']) : '';
$desde = isset($_GET['desde']) && is_string($_GET['desde']) ? trim($_GET['desde']) : '';
$hasta = isset($_GET['hasta']) && is_string($_GET['hasta']) ? trim($_GET['hasta']) : '';
if ($desde !== '' && !valid_date($desde)) $desde = '';
if ($hasta !== '' && !valid_date($hasta)) $hasta = '';

$resumen = ['total'=>0.0, 'saldo'=>0.0, 'pendientes'=>0, 'documentos'=>count($ventas)];
$filtradas = [];
foreach ($ventas as $venta) {
    $cliente = $clientes[$venta['cliente_id']]['nombre'] ?? 'Cliente no disponible';
    $documento = trim(($venta['tipo_documento'] ?? '') . ' ' . ($venta['serie'] ?? '') . '-' . ($venta['numero'] ?? ''));
    $estadoEntrega = dispatch_status($venta);
    $finanzas = sale_financials($venta);
    $resumen['total'] += ($venta['estado_documento'] ?? 'Vigente') === 'Anulada' ? 0 : (float)$venta['total'];
    $resumen['saldo'] += $finanzas['saldo'];
    if ($estadoEntrega !== 'Entregada' && ($venta['estado_documento'] ?? 'Vigente') !== 'Anulada') $resumen['pendientes']++;
    $texto = mb_strtolower($cliente . ' ' . $documento);
    if ($q !== '' && mb_strpos($texto, mb_strtolower($q)) === false) continue;
    if ($entrega !== '' && $estadoEntrega !== $entrega) continue;
    if ($pago !== '' && $finanzas['estado'] !== $pago) continue;
    if ($desde !== '' && $venta['fecha'] < $desde) continue;
    if ($hasta !== '' && $venta['fecha'] > $hasta) continue;
    $venta['_cliente'] = $cliente;
    $venta['_documento'] = $documento;
    $venta['_entrega'] = $estadoEntrega;
    $venta['_finanzas'] = $finanzas;
    $filtradas[] = $venta;
}
usort($filtradas, fn($a, $b) => strcmp($b['fecha'], $a['fecha']) ?: ((int)$b['id'] <=> (int)$a['id']));

function sale_status_class($status) {
    return match ($status) {
        'Entregada', 'Pagado' => 'success',
        'Parcial', 'Parcialmente pagado' => 'warning',
        'Vencido' => 'danger',
        default => 'secondary'
    };
}
?>
<?php include '../../includes/header.php'; ?>

<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><span class="page-eyebrow">Operaciones comerciales</span><h1 class="h3 mb-1">Ventas y comprobantes</h1><p class="text-muted mb-0">Controla facturación, despachos y cobranza desde un solo listado.</p></div>
    <a href="<?php echo url('pages/ventas/nueva.php'); ?>" class="btn btn-warning"><i class="bi bi-plus-circle me-1"></i>Nueva venta</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card summary-card"><small>Documentos</small><strong><?php echo $resumen['documentos']; ?></strong></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card summary-card"><small>Venta vigente</small><strong><?php echo format_money($resumen['total']); ?></strong></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card summary-card"><small>Saldo por cobrar</small><strong class="text-danger"><?php echo format_money($resumen['saldo']); ?></strong></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card summary-card"><small>Despachos pendientes</small><strong class="text-warning-emphasis"><?php echo $resumen['pendientes']; ?></strong></div></div>
</div>

<form method="get" class="card filter-card mb-4">
    <div class="card-body row g-3 align-items-end">
        <div class="col-lg-4"><label class="form-label">Buscar</label><input name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Cliente, serie o número"></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Entrega</label><select name="entrega" class="form-select"><option value="">Todas</option><?php foreach(['Pendiente','Parcial','Entregada'] as $s): ?><option <?php echo $entrega===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Pago</label><select name="pago" class="form-select"><option value="">Todos</option><?php foreach(['Pendiente','Parcialmente pagado','Pagado','Vencido'] as $s): ?><option <?php echo $pago===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>"></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Hasta</label><input type="date" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>"></div>
        <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="<?php echo url('pages/ventas/index.php'); ?>">Limpiar</a><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button></div>
    </div>
</form>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"><h2 class="h6 mb-0">Comprobantes registrados</h2><span class="text-muted small"><?php echo count($filtradas); ?> resultado(s)</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle">
        <thead><tr><th>Emisión</th><th>Cliente / comprobante</th><th>Almacén</th><th>Documento</th><th>Entrega</th><th>Pago</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        <?php if (!$filtradas): ?><tr><td colspan="9" class="empty-state"><i class="bi bi-receipt"></i><strong>No se encontraron ventas</strong><span>Prueba con otros filtros o registra una nueva venta.</span></td></tr><?php endif; ?>
        <?php foreach ($filtradas as $venta): $docStatus=$venta['estado_documento']??'Vigente'; $fin=$venta['_finanzas']; ?>
            <tr>
                <td><strong><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></strong><small class="d-block text-muted">#<?php echo (int)$venta['id']; ?></small></td>
                <td><strong><?php echo htmlspecialchars($venta['_cliente']); ?></strong><small class="d-block text-muted"><?php echo htmlspecialchars($venta['_documento']); ?></small></td>
                <td><?php echo htmlspecialchars($almacenes[$venta['almacen_id']]['nombre'] ?? 'No disponible'); ?></td>
                <td><span class="badge text-bg-<?php echo $docStatus==='Anulada'?'danger':'primary'; ?>"><?php echo htmlspecialchars($docStatus); ?></span></td>
                <td><span class="badge text-bg-<?php echo sale_status_class($venta['_entrega']); ?>"><?php echo htmlspecialchars($venta['_entrega']); ?></span></td>
                <td><span class="badge text-bg-<?php echo sale_status_class($fin['estado']); ?>"><?php echo htmlspecialchars($fin['estado']); ?></span></td>
                <td class="text-end fw-bold"><?php echo format_money($venta['total']); ?></td>
                <td class="text-end <?php echo $fin['saldo']>0?'text-danger fw-bold':'text-success'; ?>"><?php echo format_money($fin['saldo']); ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?php echo url('pages/ventas/detalle.php?id='.$venta['id']); ?>" class="btn btn-sm btn-primary" title="Ver detalle"><i class="bi bi-eye"></i></a>
                    <a href="<?php echo url('pages/ventas/documento.php?id='.$venta['id']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver comprobante"><i class="bi bi-file-earmark-text"></i></a>
                    <a href="<?php echo url('pages/inventario/kardex.php?venta_id='.$venta['id']); ?>" class="btn btn-sm btn-outline-info" title="Ver Kardex"><i class="bi bi-journal-text"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<?php include '../../includes/footer.php'; ?>
