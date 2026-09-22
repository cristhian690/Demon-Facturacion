<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/quantities.php';

$clientes = get_data('clientes');
$productos = get_data('productos');
$almacenes = get_data('almacenes');
?>
<?php include '../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 text-gray-800">Registrar Nueva Venta</h2>
    <a href="<?php echo url('pages/ventas/index.php'); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<form action="<?php echo url('actions/procesar_venta.php'); ?>" method="POST" id="formVenta">
    <?php echo form_context(); ?>
    <div class="alert alert-danger d-none form-errors" role="alert"></div>
    <div class="card card-body mb-3">
        <label for="deliveryMode" class="form-label">Entrega de mercadería</label>
        <select id="deliveryMode" name="entrega" class="form-select">
            <option value="inmediata">Entregar todo al confirmar (descuenta stock)</option>
            <option value="pendiente">Dejar entrega pendiente (sin descontar stock)</option>
        </select>
        <small class="text-muted mt-2">Una entrega pendiente no reserva existencias. El stock se valida en cada despacho desde el detalle de la venta.</small>
    </div>
    
    <!-- Cabecera de la Venta -->
    <div class="card shadow mb-4 border-top-warning">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-warning-emphasis">Datos del Documento</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Cliente <span class="text-danger">*</span></label>
                    <select class="form-select" name="cliente_id" required>
                        <option value="">Seleccione cliente...</option>
                        <?php foreach($clientes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' (' . $c['numero_documento'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a class="btn btn-sm btn-outline-primary mt-2" data-select-target="cliente_id" href="<?php echo url('pages/clientes/form.php'); ?>">Agregar cliente</a>
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
                    <input type="text" class="form-control" name="numero" placeholder="000421" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Almacén Origen (Salida de mercancía) <span class="text-danger">*</span></label>
                    <select class="form-select" name="almacen_id" id="selectAlmacen" required>
                        <option value="">Seleccione...</option>
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
            <h6 class="m-0 font-weight-bold text-primary">Detalle de Productos a Vender</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarFila">
                <i class="bi bi-plus"></i> Agregar Producto
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="tablaDetalle">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th width="140" class="text-center">Stock Disp.</th>
                            <th width="120">Cantidad</th>
                            <th width="150">Precio Venta (S/)</th>
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
                                        <option data-unit="<?php echo htmlspecialchars($prod['unidad_medida'] ?? 'UN'); ?>" data-step="<?php echo quantity_step($prod['unidad_medida'] ?? 'UN'); ?>" value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['sku'] . ' - ' . $prod['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary lbl-stock">---</span>
                                <input type="hidden" class="hidden-stock" value="0">
                            </td>
                            <td><input type="number" class="form-control txt-cantidad" name="cantidades[]" min="1" step="1" value="1" required></td>
                            <td><input type="number" class="form-control txt-precio" name="precios[]" min="0.01" step="0.01" value="0.00" required></td>
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
    
    <div class="alert alert-info dispatch-preview" aria-live="polite">Selecciona producto y almacén para consultar el stock.</div>
    <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn btn-warning btn-lg fw-bold" id="btnConfirmarVenta">
            <i class="bi bi-check-circle me-2"></i> Confirmar Venta
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
                    <option data-unit="<?php echo htmlspecialchars($prod['unidad_medida'] ?? 'UN'); ?>" data-step="<?php echo quantity_step($prod['unidad_medida'] ?? 'UN'); ?>" value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['sku'] . ' - ' . $prod['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="text-center">
            <span class="badge bg-secondary lbl-stock">---</span>
            <input type="hidden" class="hidden-stock" value="0">
        </td>
        <td><input type="number" class="form-control txt-cantidad" name="cantidades[]" min="1" step="1" value="1" required></td>
        <td><input type="number" class="form-control txt-precio" name="precios[]" min="0.01" step="0.01" value="0.00" required></td>
        <td><input type="number" class="form-control txt-dscto" name="descuentos[]" min="0" max="100" step="1" value="0"></td>
        <td><input type="text" class="form-control txt-subtotal" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-danger btn-sm btn-eliminar-fila"><i class="bi bi-trash"></i></button></td>
    </tr>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.querySelector('#tablaDetalle tbody');
    const template = document.querySelector('#templateFila tbody').innerHTML;
    const selectAlmacen = document.getElementById('selectAlmacen');
    const form = document.getElementById('formVenta');
    const delivery = document.getElementById('deliveryMode');
    form.dataset.requiresStock = 'true';
    const preview = form.querySelector('.dispatch-preview');
    
    function calcularTotales() {
        let subtotalGlobal = 0;
        let valid = true;
        
        const filas = tbody.querySelectorAll('tr');
        const cantidades = {};
        filas.forEach(f => { const id = f.querySelector('.producto-select').value; cantidades[id] = (cantidades[id] || 0) + Number(f.querySelector('.txt-cantidad').value); });
        filas.forEach(fila => {
            const cantInput = fila.querySelector('.txt-cantidad');
            const cant = parseFloat(cantInput.value) || 0;
            const precio = parseFloat(fila.querySelector('.txt-precio').value) || 0;
            const dscto = parseFloat(fila.querySelector('.txt-dscto').value) || 0;
            const stockActual = parseFloat(fila.querySelector('.hidden-stock').value) || 0;
            
            // Validar stock visualmente
            const selected = fila.querySelector('.producto-select').value;
            const missingStock = fila.querySelector('.hidden-stock').dataset.ready !== 'true';
            if (delivery.value === 'inmediata' && selected !== '' && (missingStock || Math.round(cantidades[selected] * 1000) > Math.round(stockActual * 1000))) {
                cantInput.classList.add('is-invalid');
                valid = false;
            } else {
                cantInput.classList.remove('is-invalid');
            }
            
            let bruto = cant * precio;
            let descuentoMoneda = bruto * (dscto / 100);
            let subtotalFila = bruto - descuentoMoneda;
            
            fila.querySelector('.txt-subtotal').value = subtotalFila.toFixed(2);
            subtotalGlobal += Number(subtotalFila.toFixed(2));
        });
        
        const igv = subtotalGlobal * 0.18;
        const total = subtotalGlobal + igv;
        
        document.getElementById('res_subtotal').value = subtotalGlobal.toFixed(2);
        document.getElementById('res_igv').value = igv.toFixed(2);
        document.getElementById('res_total').value = total.toFixed(2);
        
        const messages = [];
        const seen = new Set();
        filas.forEach(fila => {
            const option = fila.querySelector('.producto-select').selectedOptions[0];
            if (!option?.value || seen.has(option.value)) return;
            seen.add(option.value);
            const stockInput = fila.querySelector('.hidden-stock');
            const qty = Number(cantidades[option.value].toFixed(3));
            const remaining = Number((Number(stockInput.value) - qty).toFixed(3));
            messages.push(delivery.value === 'pendiente'
                ? `${option.textContent}: facturarás ${qty} ${option.dataset.unit}; saldrán 0 ahora. Entrega pendiente.`
                : `${option.textContent}: saldrán ${qty} ${option.dataset.unit}; quedarán ${stockInput.dataset.ready === 'true' ? remaining : 'por consultar'}.`);
        });
        preview.textContent = messages.join(' ') || 'Selecciona producto y almacén para consultar el stock.';
        form.dataset.stockValid = String(valid && messages.length > 0);
        document.getElementById('btnConfirmarVenta').disabled = !valid || form.dataset.processing === 'true';
    }
    
    async function actualizarStockFila(fila) {
        const prodId = fila.querySelector('.producto-select').value;
        const almId = selectAlmacen.value;
        const lblStock = fila.querySelector('.lbl-stock');
        const hiddenStock = fila.querySelector('.hidden-stock');
        hiddenStock.dataset.ready = 'false';
        
        if (!prodId || !almId) {
            lblStock.textContent = '---';
            lblStock.className = 'badge bg-secondary lbl-stock';
            hiddenStock.value = 0;
            calcularTotales();
            return;
        }
        
        hiddenStock.value = 0;
        calcularTotales();
        lblStock.textContent = 'Buscando...';
        
        try {
            const response = await fetch(`<?php echo url('actions/api_stock.php'); ?>?producto_id=${prodId}&almacen_id=${almId}&empresa_id=${form.elements.empresa_id.value}`);
            const data = await response.json();
            if (prodId !== fila.querySelector('.producto-select').value || almId !== selectAlmacen.value) return;
            if (!response.ok || data.error) throw new Error(data.error || 'No se pudo consultar stock');
            
            hiddenStock.value = data.stock;
            hiddenStock.dataset.ready = 'true';
            lblStock.textContent = data.stock + ' ' + data.unidad_medida;
            
            if (data.stock <= 0) {
                lblStock.className = 'badge bg-danger lbl-stock';
            } else {
                lblStock.className = 'badge bg-info text-dark lbl-stock';
            }
            calcularTotales();
        } catch (e) {
            console.error('Error fetching stock:', e);
            lblStock.textContent = 'Error';
        }
    }
    
    // Al cambiar de almacén, actualizar el stock de todas las filas
    delivery.addEventListener('change', calcularTotales);
    selectAlmacen.addEventListener('change', function() {
        const filas = tbody.querySelectorAll('tr');
        filas.forEach(fila => actualizarStockFila(fila));
    });
    
    // Delegación de eventos
    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('producto-select')) {
            actualizarStockFila(e.target.closest('tr'));
        }
    });
    
    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('txt-cantidad') || 
            e.target.classList.contains('txt-precio') || 
            e.target.classList.contains('txt-dscto')) {
            calcularTotales();
        }
    });
    
    // Agregar fila
    document.getElementById('btnAgregarFila').addEventListener('click', function() {
        tbody.insertAdjacentHTML('beforeend', template);
        calcularTotales();
    });
    
    // Eliminar fila
    tbody.addEventListener('click', function(e) {
        if (e.target.closest('.btn-eliminar-fila')) {
            const filas = tbody.querySelectorAll('tr');
            if (filas.length > 1) {
                e.target.closest('tr').remove();
                calcularTotales();
            } else {
                alert('La venta debe tener al menos un producto.');
            }
        }
    });
});
</script>

<script src="<?php echo url('assets/js/quantities.js'); ?>" defer></script>
<?php include '../../includes/footer.php'; ?>
