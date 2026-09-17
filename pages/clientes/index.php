<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$clientes = get_data('clientes');
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Clientes</h2>
    <a href="<?php echo url('pages/clientes/form.php'); ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Cliente
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Listado de Clientes</h6>
        <div class="input-group input-group-sm" style="width: 250px;">
            <input type="text" class="form-control" placeholder="Buscar cliente...">
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
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="6" class="text-center">No hay clientes registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($cli['tipo_documento'] ?? ''); ?></span>
                                    <?php echo htmlspecialchars($cli['numero_documento'] ?? ''); ?>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($cli['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cli['direccion'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cli['telefono'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cli['correo'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo url('pages/clientes/form.php?id=' . $cli['id']); ?>" class="btn btn-sm btn-outline-primary" title="Editar">
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
