<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$proveedores = get_data('proveedores');
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Proveedores</h2>
    <a href="<?php echo url('pages/proveedores/form.php'); ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Proveedor
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Listado de Proveedores</h6>
        <div class="input-group input-group-sm" style="width: 250px;">
            <input type="text" class="form-control" placeholder="Buscar proveedor...">
            <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="120">Documento</th>
                        <th>Nombre / Razón Social</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr><td colspan="6" class="text-center">No hay proveedores registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($prov['tipo_documento'] ?? ''); ?></span>
                                    <?php echo htmlspecialchars($prov['numero_documento'] ?? ''); ?>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($prov['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($prov['direccion'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($prov['telefono'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($prov['correo'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo url('pages/proveedores/form.php?id=' . $prov['id']); ?>" class="btn btn-sm btn-outline-primary" title="Editar">
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
