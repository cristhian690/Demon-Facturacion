<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$almacenes = get_data('almacenes');
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Almacenes</h2>
    <a href="<?php echo url('pages/almacenes/form.php'); ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Almacén
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Listado de Almacenes</h6>
        <div class="input-group input-group-sm" style="width: 250px;">
            <input type="text" class="form-control" placeholder="Buscar almacén...">
            <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50">ID</th>
                        <th>Nombre del Almacén</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($almacenes)): ?>
                        <tr><td colspan="5" class="text-center">No hay almacenes registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($almacenes as $alm): ?>
                            <tr>
                                <td><?php echo $alm['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($alm['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($alm['ubicacion'] ?? ''); ?></td>
                                <td>
                                    <?php if(($alm['estado'] ?? 'Activo') == 'Activo'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo url('pages/almacenes/form.php?id=' . $alm['id']); ?>" class="btn btn-sm btn-outline-primary" title="Editar">
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
