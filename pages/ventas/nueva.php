<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/quantities.php';

$clientes = get_data('clientes');
$productos = get_data('productos');
$almacenes = get_data('almacenes');
?>
<?php include '../../includes/header.php'; ?>

<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><span class="page-eyebrow">Ventas / Facturación</span><h1 class="h3 mb-1">Nuevo comprobante</h1><p class="text-muted mb-0">Completa los datos comerciales, productos, entrega y condición de pago.</p></div>
    <a href="<?php echo url('pages/ventas/index.php'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver al listado</a>
</div>

<form action="<?php echo url('actions/procesar_venta.php'); ?>" method="POST" id="formVenta" class="invoice-form">
    <?php echo form_context(); ?>
    <div class="alert alert-danger d-none form-errors" role="alert"></div>
    <div class="card invoice-shell mb-4">
        <div class="card-header"><div class="invoice-section-title"><i class="bi bi-1-circle"></i><span>Datos del comprobante</span></div></div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-xl-3"><label class="form-label">Empresa</label><input class="form-control readonly-field" value="<?php echo htmlspecialchars(get_empresa_activa()['nombre']); ?>" readonly></div>
                <div class="col-md-6 col-xl-3"><label class="form-label">Vendedor</label><input class="form-control readonly-field" value="<?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Administrador'); ?>" readonly></div>
                <div class="col-md-6 col-xl-2"><label class="form-label">Tipo de comprobante <span class="text-danger">*</span></label><select class="form-select" name="tipo_documento" required><option value="Factura">Factura</option><option value="Boleta">Boleta</option></select></div>
                <div class="col-md-3 col-xl-2"><label class="form-label">Serie <span class="text-danger">*</span></label><input type="text" class="form-control text-uppercase" name="serie" value="F001" maxlength="10" pattern="[A-Za-z0-9-]+" oninput="this.value=this.value.toUpperCase()" required></div>
                <div class="col-md-3 col-xl-2"><label class="form-label">Número <span class="text-danger">*</span></label><input type="text" inputmode="numeric" class="form-control" name="numero" placeholder="000001" maxlength="12" pattern="[0-9]+" required></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6 col-xl-3"><label class="form-label">Fecha de emisión <span class="text-danger">*</span></label><input type="date" class="form-control" name="fecha" value="<?php echo date('Y-m-d'); ?>" required></div>
                <div class="col-md-6 col-xl-3"><label class="form-label">Tipo de operación</label><select name="tipo_operacion" class="form-select"><option value="Venta interna">Venta interna</option><option value="Venta para exportación">Venta para exportación</option></select></div>
                <div class="col-md-6 col-xl-3"><label class="form-label">Moneda</label><select name="moneda" id="saleCurrency" class="form-select"><option value="PEN">Soles (PEN)</option></select></div>
                <div class="col-md-6 col-xl-3"><label class="form-label">Tipo de cambio</label><input type="number" id="exchangeRate" name="tipo_cambio" class="form-control readonly-field" value="1.000" min="0.001" step="0.001" readonly></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><div class="invoice-section-title"><i class="bi bi-2-circle"></i><span>Cliente e información comercial</span></div></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Cliente <span class="text-danger">*</span> <a class="ms-1 text-decoration-none" data-select-target="cliente_id" href="<?php echo url('pages/clientes/form.php'); ?>">[+ Nuevo]</a></label>
                    <select class="form-select" name="cliente_id" required>
                        <option value="">Seleccione cliente...</option>
                        <?php foreach($clientes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' (' . $c['numero_documento'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3"><label class="form-label">Orden de compra</label><input class="form-control" name="orden_compra" maxlength="100" placeholder="Opcional"></div>
                <div class="col-md-6 col-lg-3"><label class="form-label">Observación</label><input class="form-control" name="observacion" maxlength="500" placeholder="Información adicional"></div>
            </div>
        </div>
    </div>

    <!-- Detalle de Productos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><div class="invoice-section-title"><i class="bi bi-3-circle"></i><span>Productos y totales</span></div><small class="text-muted">El precio de venta lo defines aquí. El costo del Kardex se toma automáticamente del CPP vigente y no puede modificarse.</small></div>
            <div class="invoice-toolbar"><a class="btn btn-sm btn-outline-success" data-select-target="productos[]" href="<?php echo url('pages/productos/form.php'); ?>"><i class="bi bi-plus-circle me-1"></i>Crear producto</a><button type="button" class="btn btn-sm btn-primary" id="btnAgregarFila"><i class="bi bi-plus"></i>Agregar línea</button></div>
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
    
    <div class="row g-4 mb-4">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-4-circle"></i><span>Entrega</span></div></div><div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label">Almacén de salida <span class="text-danger">*</span></label><select class="form-select" name="almacen_id" id="selectAlmacen" required><option value="">Seleccione almacén...</option><?php foreach($almacenes as $a): ?><option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label for="deliveryMode" class="form-label">Tipo de entrega</label><select id="deliveryMode" name="entrega" class="form-select"><option value="inmediata">Despachar al confirmar</option><option value="pendiente">Dejar pendiente de despacho</option></select></div>
            <div class="col-12"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>El inventario se descuenta únicamente cuando la mercadería se despacha.</small></div>
        </div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-5-circle"></i><span>Condición de pago</span></div></div><div class="card-body row g-3">
            <div class="col-md-6"><label for="paymentTerms" class="form-label">Condición <span class="text-danger">*</span></label><select id="paymentTerms" name="condicion_pago" class="form-select" required><option value="contado">Contado</option><option value="credito">Crédito</option></select></div>
            <div class="col-md-6"><label for="dueDate" class="form-label">Fecha de vencimiento</label><input id="dueDate" name="fecha_vencimiento" type="date" class="form-control" disabled></div>
            <div class="col-12"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>Las ventas a crédito aparecerán automáticamente en Cuentas por cobrar.</small></div>
        </div></div></div>
    </div>
    <div class="card sale-impact-card mb-4">
        <div class="card-header"><div class="invoice-section-title"><i class="bi bi-6-circle"></i><span>Resumen final e impacto de la operación</span></div></div>
        <div class="card-body"><div class="alert alert-info dispatch-preview mb-3" aria-live="polite">Selecciona producto y almacén para consultar el stock.</div><div id="saleCostPreview" class="sale-cost-preview"><i class="bi bi-calculator"></i><span>El costo de salida del Kardex se calculará automáticamente usando el CPP vigente.</span></div></div>
    </div>
    <div class="sale-submit-bar mb-5">
        <div><small>Revisa el comprobante antes de continuar</small><strong>La confirmación registrará la venta y, si corresponde, el despacho.</strong></div>
        <button type="submit" class="btn btn-warning fw-bold" id="btnConfirmarVenta"><i class="bi bi-check-circle me-2"></i>Confirmar venta</button>
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
    const terms = document.getElementById('paymentTerms');
    const due = document.getElementById('dueDate');
    terms.addEventListener('change', () => { due.disabled = terms.value !== 'credito'; due.required = terms.value === 'credito'; if (due.disabled) due.value = ''; });
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
        const costPreview = document.getElementById('saleCostPreview');
        const costLines = [];
        filas.forEach(fila => {
            const option = fila.querySelector('.producto-select').selectedOptions[0];
            const stockInput = fila.querySelector('.hidden-stock');
            if (!option?.value || stockInput.dataset.ready !== 'true') return;
            const price = Number(fila.querySelector('.txt-precio').value || 0);
            const cpp = Number(stockInput.dataset.cpp || 0);
            costLines.push(`${option.textContent}: precio de venta S/ ${price.toFixed(2)} · costo Kardex actual S/ ${cpp.toFixed(2)}.`);
        });
        costPreview.querySelector('span').textContent = costLines.join(' ') || 'El costo de salida del Kardex se calculará automáticamente usando el CPP vigente.';
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
            hiddenStock.dataset.cpp = data.cpp;
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
