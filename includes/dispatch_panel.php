<?php
require_once __DIR__ . '/dispatches.php';
$warehouse_names = array_column(get_data('almacenes'), 'nombre', 'id');
$product_records = array_column($productos, null, 'id');
$is_pending = dispatch_status($venta) !== 'Entregada';
?>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <h3 class="h5">Entregas: <?php echo htmlspecialchars(dispatch_status($venta)); ?></h3>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?php echo url('pages/ventas/documento.php?id=' . $venta['id']); ?>" target="_blank">Ver comprobante</a>
                <a class="btn btn-outline-info" href="<?php echo url('pages/inventario/kardex.php?venta_id=' . $venta['id']); ?>">Ver movimientos en Kardex</a>
            </div>
        </div>
        <p>Almacén: <strong><?php echo htmlspecialchars($warehouse_names[$venta['almacen_id']] ?? 'Sin asignación disponible'); ?></strong>. Lo facturado no se vuelve a descontar: cada despacho registra su propia salida.</p>
        <?php if (!isset($venta['entrega'])): ?>
            <p class="alert alert-info">Venta anterior al control de entregas: su stock ya fue descontado. Se conserva como entregada.</p>
        <?php endif; ?>
        <form id="formDespacho" action="<?php echo url('actions/procesar_despacho.php'); ?>" method="POST" data-stock-url="<?php echo url('actions/api_stock.php'); ?>" data-warehouse="<?php echo (int)$venta['almacen_id']; ?>">
            <?php echo form_context(); ?>
            <input type="hidden" name="venta_id" value="<?php echo (int)$venta['id']; ?>">
            <div class="alert alert-danger d-none form-errors" role="alert"></div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr><th>Producto</th><th>Unidad</th><th>Facturado</th><th>Entregado</th><th>Pendiente</th><?php if ($is_pending): ?><th>Stock disponible</th><th>Despachar ahora</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($venta['detalles'] as $i => $line):
                        $product = $product_records[$line['producto_id']] ?? [];
                        $unit = $line['unidad_medida'] ?? $product['unidad_medida'] ?? 'UN';
                        $delivered = delivered_quantity($venta, $line);
                        $pending = round($line['cantidad'] - $delivered, 3);
                    ?>
                        <tr data-product="<?php echo (int)$line['producto_id']; ?>" data-unit="<?php echo htmlspecialchars($unit); ?>">
                            <td class="product-name"><?php echo htmlspecialchars($product['nombre'] ?? 'Producto #' . $line['producto_id'] . ' sin asignación disponible'); ?></td>
                            <td><?php echo htmlspecialchars($unit); ?></td><td><?php echo $line['cantidad']; ?></td><td><?php echo $delivered; ?></td><td><?php echo $pending; ?></td>
                            <?php if ($is_pending): ?>
                            <td class="stock-label">Consultando…</td>
                            <td><input aria-label="Cantidad a despachar, línea <?php echo $i+1; ?>" class="form-control dispatch-qty" name="cantidades[<?php echo $i; ?>]" type="number" min="0" max="<?php echo $pending; ?>" step="<?php echo quantity_step($unit); ?>" value="0" required <?php echo $pending <= 0 ? 'readonly' : ''; ?>></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($is_pending): ?>
                <label class="form-label" for="dispatchDate">Fecha del despacho</label>
                <input id="dispatchDate" class="form-control mb-3" type="date" name="fecha" min="<?php echo htmlspecialchars($venta['fecha']); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                <div class="alert alert-info dispatch-preview" role="status" aria-live="polite">Ingresa las cantidades a despachar. Se comprueba el stock nuevamente al guardar.</div>
                <button type="submit" class="btn btn-primary" disabled>Confirmar despacho</button>
            <?php endif; ?>
        </form>
        <h4 class="h6 mt-4">Historial de despachos</h4>
        <?php if (empty($venta['despachos'])): ?>
            <p><?php echo isset($venta['entrega']) ? 'Sin despachos registrados.' : 'La salida original se conserva en Kardex.'; ?></p>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fecha</th><th>Línea de venta</th><th>Producto</th><th>Entregado</th><th>Costo unitario de salida</th><th>Costo total</th></tr></thead><tbody>
            <?php foreach ($venta['despachos'] as $dispatch): foreach ($dispatch['detalles'] as $detail): ?>
                <tr><td><?php echo htmlspecialchars($dispatch['fecha']); ?></td><td><?php echo $detail['linea'] + 1; ?></td><td><?php echo htmlspecialchars($product_records[$detail['producto_id']]['nombre'] ?? 'Producto #' . $detail['producto_id']); ?></td><td><?php echo $detail['cantidad']; ?></td><td><?php echo format_money($detail['costo_unitario']); ?></td><td><?php echo format_money($detail['costo_total']); ?></td></tr>
            <?php endforeach; endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>
<?php if ($is_pending): ?><script src="<?php echo url('assets/js/dispatches.js'); ?>" defer></script><?php endif; ?>
