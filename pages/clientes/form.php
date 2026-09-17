<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
$clientes = get_data('clientes');
$cliente = null;

if ($id) {
    foreach ($clientes as $c) {
        if ($c['id'] == $id) {
            $cliente = $c;
            break;
        }
    }
}
$is_edit = $cliente !== null;
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800"><?php echo $is_edit ? 'Editar Cliente' : 'Nuevo Cliente'; ?></h2>
    <a href="<?php echo url('pages/clientes/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?php echo url('actions/guardar_cliente.php'); ?>" method="POST">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="form-label">Tipo Doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_documento" required>
                        <option value="DNI" <?php echo ($is_edit && ($cliente['tipo_documento'] ?? '') == 'DNI') ? 'selected' : ''; ?>>DNI</option>
                        <option value="RUC" <?php echo ($is_edit && ($cliente['tipo_documento'] ?? '') == 'RUC') ? 'selected' : ''; ?>>RUC</option>
                        <option value="CE" <?php echo ($is_edit && ($cliente['tipo_documento'] ?? '') == 'CE') ? 'selected' : ''; ?>>CE</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nro. Documento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="numero_documento" required 
                           value="<?php echo $is_edit ? htmlspecialchars($cliente['numero_documento'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Nombre / Razón Social <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" required 
                           value="<?php echo $is_edit ? htmlspecialchars($cliente['nombre']) : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" 
                           value="<?php echo $is_edit ? htmlspecialchars($cliente['direccion'] ?? '') : ''; ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" 
                           value="<?php echo $is_edit ? htmlspecialchars($cliente['telefono'] ?? '') : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" 
                           value="<?php echo $is_edit ? htmlspecialchars($cliente['correo'] ?? '') : ''; ?>">
                </div>
            </div>
            
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
