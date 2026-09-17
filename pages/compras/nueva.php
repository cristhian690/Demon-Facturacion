<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$proveedores = get_data('proveedores');
$productos = get_data('productos');
$almacenes = get_data('almacenes');
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Registrar Nueva Compra</h2>
    <a href="<?php echo url('pages/compras/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<form action="<?php echo url('actions/procesar_compra.php'); ?>" method="POST" id="formCompra">
    
    <!-- Cabecera de la Compra -->
    <div class="card shadow mb-4 border-left-success">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">Datos del Documento</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                    <select class="form-select" name="proveedor_id" required>
                        <option value="">Seleccione proveedor...</option>
                        <?php foreach($proveedores as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre'] . ' (' . $p['numero_documento'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo Doc. <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo_documento" required>
                        <option value="Factura">Factura</option>
                        <option value="Boleta">Boleta</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Serie <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="serie" placeholder="F001" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="numero" placeholder="000123" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Almacén Destino <span class="text-danger">*</span></label>
                    <select class="form-select" name="almacen_id" required>
                        <?php foreach($almacenes as $a): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de Productos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Detalle de Productos</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarFila">
                <i class="bi bi-plus"></i> Agregar Producto
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaDetalle">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th width="120">Cantidad</th>
                            <th width="150">Costo Unit. (S/)</th>
                            <th width="120">Dscto (%)</th>
                            <th width="150">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila inicial -->
                        <tr>
                            <td>
                                <select class="form-select producto-select" name="productos[]" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['sku'] . ' - ' . $prod['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" class="form-control txt-cantidad" name="cantidades[]" min="1" step="1" value="1" required></td>
                            <td><input type="number" class="form-control txt-costo" name="costos[]" min="0.01" step="0.01" value="0.00" required></td>
                            <td><input type="number" class="form-control txt-dscto" name="descuentos[]" min="0" max="100" step="1" value="0"></td>
                            <td><input type="text" class="form-control txt-subtotal" readonly value="0.00"></td>
                            <td><button type="button" class="btn btn-danger btn-sm btn-eliminar-fila"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="row justify-content-end mt-3">
                <div class="col-md-4">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-end fw-bold">Subtotal:</td>
                            <td width="150"><input type="text" class="form-control text-end" id="res_subtotal" name="res_subtotal" readonly value="0.00"></td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">IGV (18%):</td>
                            <td><input type="text" class="form-control text-end" id="res_igv" name="res_igv" readonly value="0.00"></td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold fs-5">TOTAL:</td>
                            <td><input type="text" class="form-control text-end fw-bold fs-5 text-success bg-light" id="res_total" name="res_total" readonly value="0.00"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="bi bi-check-circle me-2"></i> Confirmar Compra
        </button>
    </div>

</form>

<!-- Template para nueva fila oculta -->
<table id="templateFila" style="display:none;">
    <tr>
        <td>
            <select class="form-select producto-select" name="productos[]" required>
                <option value="">Seleccionar...</option>
                <?php foreach($productos as $prod): ?>
                    <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['sku'] . ' - ' . $prod['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" class="form-control txt-cantidad" name="cantidades[]" min="1" step="1" value="1" required></td>
        <td><input type="number" class="form-control txt-costo" name="costos[]" min="0.01" step="0.01" value="0.00" required></td>
        <td><input type="number" class="form-control txt-dscto" name="descuentos[]" min="0" max="100" step="1" value="0"></td>
        <td><input type="text" class="form-control txt-subtotal" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-danger btn-sm btn-eliminar-fila"><i class="bi bi-trash"></i></button></td>
    </tr>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.querySelector('#tablaDetalle tbody');
    const template = document.querySelector('#templateFila tbody').innerHTML;
    
    function calcularTotales() {
        let subtotalGlobal = 0;
        
        const filas = tbody.querySelectorAll('tr');
        filas.forEach(fila => {
            const cant = parseFloat(fila.querySelector('.txt-cantidad').value) || 0;
            const costo = parseFloat(fila.querySelector('.txt-costo').value) || 0;
            const dscto = parseFloat(fila.querySelector('.txt-dscto').value) || 0;
            
            let bruto = cant * costo;
            let descuentoMoneda = bruto * (dscto / 100);
            let subtotalFila = bruto - descuentoMoneda;
            
            fila.querySelector('.txt-subtotal').value = subtotalFila.toFixed(2);
            subtotalGlobal += subtotalFila;
        });
        
        const igv = subtotalGlobal * 0.18;
        const total = subtotalGlobal + igv;
        
        document.getElementById('res_subtotal').value = subtotalGlobal.toFixed(2);
        document.getElementById('res_igv').value = igv.toFixed(2);
        document.getElementById('res_total').value = total.toFixed(2);
    }
    
    // Delegación de eventos para calcular al cambiar valores
    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('txt-cantidad') || 
            e.target.classList.contains('txt-costo') || 
            e.target.classList.contains('txt-dscto')) {
            calcularTotales();
        }
    });
    
    // Agregar fila
    document.getElementById('btnAgregarFila').addEventListener('click', function() {
        tbody.insertAdjacentHTML('beforeend', template);
    });
    
    // Eliminar fila
    tbody.addEventListener('click', function(e) {
        if (e.target.closest('.btn-eliminar-fila')) {
            const filas = tbody.querySelectorAll('tr');
            if (filas.length > 1) {
                e.target.closest('tr').remove();
                calcularTotales();
            } else {
                alert('La compra debe tener al menos un producto.');
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
