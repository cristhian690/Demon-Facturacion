<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$productos = get_data('productos');
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Productos</h2>
    <a href="<?php echo url('pages/productos/form.php'); ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Producto
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Listado de Productos</h6>
        <div class="input-group input-group-sm" style="width: 250px;">
            <input type="text" class="form-control" placeholder="Buscar producto...">
            <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="80">Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th width="80">Unidad</th>
                        <th>Estado</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="7" class="text-center">No hay productos registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $prod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prod['sku']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($prod['nombre']); ?>
                                    <div class="text-muted fw-normal" style="font-size: 0.8rem;"><?php echo htmlspecialchars($prod['descripcion']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($prod['categoria'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($prod['marca'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($prod['unidad_medida']); ?></td>
                                <td>
                                    <?php if(($prod['estado'] ?? 'Activo') == 'Activo'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo url('pages/productos/form.php?id=' . $prod['id']); ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
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
