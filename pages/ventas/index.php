<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$ventas = get_data('ventas');
$clientes = get_data('clientes');

// Helper para obtener nombre de cliente
$getCliente = function($id) use ($clientes) {
    foreach($clientes as $c) {
        if($c['id'] == $id) return $c['nombre'];
    }
    return 'Desconocido';
};
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Ventas Registradas</h2>
    <a href="<?php echo url('pages/ventas/nueva.php'); ?>" class="btn btn-warning">
        <i class="bi bi-receipt me-1"></i> Nueva Venta
    </a>
</div>

<div class="card shadow mb-4 border-top-warning">
    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 font-weight-bold text-warning-emphasis">Listado de Ventas</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="100">Fecha</th>
                        <th width="150">Documento</th>
                        <th>Cliente</th>
                        <th>Almacén Origen</th>
                        <th class="text-end">Total (Ingreso)</th>
                        <th class="text-end">Costo de Ventas</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                        <tr><td colspan="7" class="text-center">No hay ventas registradas</td></tr>
                    <?php else: ?>
                        <?php 
                        // Ordenar por fecha descendente
                        usort($ventas, function($a, $b) {
                            return strtotime($b['fecha']) - strtotime($a['fecha']);
                        });
                        foreach ($ventas as $venta): 
                            if ($venta['empresa_id'] != $_SESSION['empresa_id']) continue;
                        ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($venta['tipo_documento']); ?></span><br>
                                    <small><?php echo htmlspecialchars($venta['serie'] . '-' . $venta['numero']); ?></small>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($getCliente($venta['cliente_id'])); ?></td>
                                <td>Almacén <?php echo $venta['almacen_id']; ?></td>
                                <td class="text-end fw-bold text-success"><?php echo format_money($venta['total']); ?></td>
                                <td class="text-end text-danger"><?php echo format_money($venta['costo_ventas_total'] ?? 0); ?></td>
                                <td>
                                    <a href="<?php echo url('pages/ventas/detalle.php?id=' . $venta['id']); ?>" class="btn btn-sm btn-outline-info" title="Ver Detalle e Impacto">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo url('pages/ventas/documento.php?id=' . $venta['id']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Factura">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
