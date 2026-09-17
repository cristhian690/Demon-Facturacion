<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$compras = get_data('compras');
$proveedores = get_data('proveedores');

// Helper para obtener nombre de proveedor
$getProveedor = function($id) use ($proveedores) {
    foreach($proveedores as $p) {
        if($p['id'] == $id) return $p['nombre'];
    }
    return 'Desconocido';
};
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Compras Registradas</h2>
    <a href="<?php echo url('pages/compras/nueva.php'); ?>" class="btn btn-success">
        <i class="bi bi-cart-plus me-1"></i> Nueva Compra
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-success">Listado de Compras</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="100">Fecha</th>
                        <th width="150">Documento</th>
                        <th>Proveedor</th>
                        <th>Almacén</th>
                        <th class="text-end">Total</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($compras)): ?>
                        <tr><td colspan="6" class="text-center">No hay compras registradas</td></tr>
                    <?php else: ?>
                        <?php 
                        // Ordenar por fecha descendente
                        usort($compras, function($a, $b) {
                            return strtotime($b['fecha']) - strtotime($a['fecha']);
                        });
                        foreach ($compras as $compra): 
                            if ($compra['empresa_id'] != $_SESSION['empresa_id']) continue;
                        ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($compra['fecha'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($compra['tipo_documento']); ?></span><br>
                                    <small><?php echo htmlspecialchars($compra['serie'] . '-' . $compra['numero']); ?></small>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($getProveedor($compra['proveedor_id'])); ?></td>
                                <td>Almacén <?php echo $compra['almacen_id']; ?></td>
                                <td class="text-end fw-bold text-success"><?php echo format_money($compra['total']); ?></td>
                                <td>
                                    <a href="<?php echo url('pages/compras/detalle.php?id=' . $compra['id']); ?>" class="btn btn-sm btn-outline-info" title="Ver Detalle e Impacto">
                                        <i class="bi bi-eye"></i>
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
