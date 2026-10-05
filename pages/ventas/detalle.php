<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/returns.php';
require_once '../../includes/finance.php';
$id=$_GET['id']??null; if(!$id){header('Location: '.url('pages/ventas/index.php'));exit;}
$venta=null; foreach(get_data('ventas') as $row) if((string)$row['id']===(string)$id){$venta=$row;break;}
if(!$venta){http_response_code(404);echo 'Venta no encontrada.';exit;}
$clientes=array_column(get_data('clientes'),'nombre','id'); $productos=get_data('productos'); $producto_por_id=array_column($productos,null,'id'); $almacenes=array_column(get_data('almacenes'),'nombre','id');
$getProdName=fn($pid)=>isset($producto_por_id[$pid]) ? (($producto_por_id[$pid]['sku']??'').' - '.$producto_por_id[$pid]['nombre']) : 'Producto #'.$pid.' no disponible';
$financial=sale_financials($venta); $deliveryStatus=dispatch_status($venta); $documentStatus=$venta['estado_documento']??'Vigente';
$totalDespachado=0; $totalPendiente=0; foreach($venta['detalles'] as $line){$sent=delivered_quantity($venta,$line);$totalDespachado+=$sent;$totalPendiente+=max(0,(float)$line['cantidad']-$sent);}
include '../../includes/header.php';
?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
 <div><span class="page-eyebrow">Ventas / Facturación</span><h1 class="h3 mb-1"><?php echo htmlspecialchars($venta['tipo_documento'].' '.$venta['serie'].'-'.$venta['numero']); ?></h1><p class="text-muted mb-0">Detalle comercial, inventario, Kardex, entrega y cobranza.</p></div>
 <div class="d-flex flex-wrap gap-2"><a href="<?php echo url('pages/ventas/index.php'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Comprobantes</a><a href="<?php echo url('pages/ventas/documento.php?id='.$venta['id']); ?>" target="_blank" class="btn btn-outline-primary"><i class="bi bi-file-earmark-text me-1"></i>Ver comprobante</a><a href="<?php echo url('pages/inventario/kardex.php?venta_id='.$venta['id']); ?>" class="btn btn-primary"><i class="bi bi-journal-text me-1"></i>Ver movimiento en Kardex</a></div>
</div>

<div class="sale-state-strip mb-4">
 <span><small>Documento</small><strong class="<?php echo $documentStatus==='Anulada'?'text-danger':''; ?>"><?php echo htmlspecialchars($documentStatus); ?></strong></span>
 <span><small>Entrega</small><strong><?php echo htmlspecialchars($deliveryStatus); ?></strong></span>
 <span><small>Pago</small><strong><?php echo htmlspecialchars($financial['estado']); ?></strong></span>
 <span><small>Estado SUNAT</small><strong class="text-muted">En desarrollo</strong></span>
</div>
<?php if($documentStatus==='Anulada'): ?><div class="alert alert-danger"><strong>COMPROBANTE ANULADO</strong><?php if(!empty($venta['anulacion'])): ?> · <?php echo date('d/m/Y',strtotime($venta['anulacion']['fecha'])); ?> · <?php echo htmlspecialchars($venta['anulacion']['motivo']); ?><?php endif; ?></div><?php endif; ?>

<div class="row g-4 mb-4">
 <div class="col-xl-8"><section class="card h-100"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-file-earmark-text"></i><span>Datos del comprobante</span></div></div><div class="card-body"><dl class="sale-data-grid mb-0">
  <div><dt>Tipo</dt><dd><?php echo htmlspecialchars($venta['tipo_documento']); ?></dd></div><div><dt>Serie y número</dt><dd><?php echo htmlspecialchars($venta['serie'].'-'.$venta['numero']); ?></dd></div><div><dt>Fecha de emisión</dt><dd><?php echo date('d/m/Y',strtotime($venta['fecha'])); ?></dd></div><div><dt>Cliente</dt><dd><?php echo htmlspecialchars($clientes[$venta['cliente_id']]??'Cliente no disponible'); ?></dd></div><div><dt>Vendedor</dt><dd><?php echo htmlspecialchars($venta['vendedor']??'Administrador'); ?></dd></div><div><dt>Operación</dt><dd><?php echo htmlspecialchars($venta['tipo_operacion']??'Venta interna'); ?></dd></div><div><dt>Moneda</dt><dd><?php echo htmlspecialchars($venta['moneda']??'PEN'); ?></dd></div><div><dt>Almacén</dt><dd><?php echo htmlspecialchars($almacenes[$venta['almacen_id']]??'Almacén no disponible'); ?></dd></div>
 </dl></div></section></div>
 <div class="col-xl-4"><section class="card sale-totals h-100"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-calculator"></i><span>Totales</span></div></div><div class="card-body"><div><span>Subtotal</span><strong><?php echo format_money($venta['subtotal']); ?></strong></div><div><span>IGV (18%)</span><strong><?php echo format_money($venta['igv']); ?></strong></div><div class="sale-grand-total"><span>Total</span><strong><?php echo format_money($venta['total']); ?></strong></div></div></section></div>
</div>

<section class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><div class="invoice-section-title"><i class="bi bi-list-check"></i><span>Detalle de productos</span></div><small class="text-muted">Precio comercial de la venta</small></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Producto</th><th>Unidad</th><th class="text-end">Cantidad</th><th class="text-end">Precio venta</th><th class="text-end">Descuento</th><th class="text-end">Subtotal</th></tr></thead><tbody>
<?php foreach($venta['detalles'] as $line): ?><tr><td><strong><?php echo htmlspecialchars($getProdName($line['producto_id'])); ?></strong></td><td><?php echo htmlspecialchars($line['unidad_medida']??'UN'); ?></td><td class="text-end"><?php echo $line['cantidad']; ?></td><td class="text-end"><?php echo format_money($line['precio_unitario']); ?></td><td class="text-end"><?php echo number_format((float)($line['descuento']??0),2); ?>%</td><td class="text-end fw-bold"><?php echo format_money($line['subtotal']); ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>

<section class="card mb-4"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="invoice-section-title"><i class="bi bi-box-arrow-up"></i><span>Inventario y Kardex</span></div><a href="<?php echo url('pages/inventario/kardex.php?venta_id='.$venta['id']); ?>" class="btn btn-sm btn-outline-primary">Ver movimiento en Kardex</a></div><div class="card-body"><div class="inventory-impact-summary"><div><small>Almacén</small><strong><?php echo htmlspecialchars($almacenes[$venta['almacen_id']]??'No disponible'); ?></strong></div><div><small>Cantidad despachada</small><strong><?php echo $totalDespachado; ?></strong></div><div><small>Cantidad pendiente</small><strong><?php echo $totalPendiente; ?></strong></div><div><small>Costo de salida</small><strong><?php echo format_money($venta['costo_ventas_total']??0); ?></strong></div></div><p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>El precio de venta es comercial. La salida del Kardex utiliza automáticamente el CPP vigente registrado en cada despacho.</p></div></section>

<?php include '../../includes/dispatch_panel.php'; ?>

<?php include '../../includes/sale_finance_panel.php'; ?>
<div class="row g-4 mt-1 mb-4"><div class="col-lg-7"><section class="card future-billing-card h-100"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-cloud-check"></i><span>Facturación electrónica</span></div><a href="<?php echo url('pages/sunat/documentos.php'); ?>" class="btn btn-sm btn-outline-primary">Ver módulo SUNAT</a></div><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><span>Estado SUNAT</span><span class="sunat-status"><i class="bi bi-tools"></i>En desarrollo</span></div><p class="small text-muted">Módulo preparado para la futura integración con SUNAT. El estado tributario no modifica el inventario ni el Kardex.</p><div class="future-actions"><button disabled>Generar XML</button><button disabled>Firmar XML</button><button disabled>Enviar a SUNAT</button><button disabled>Consultar CDR</button></div></div></section></div>
<div class="col-lg-5"><section class="card h-100"><div class="card-header"><div class="invoice-section-title"><i class="bi bi-file-earmark-minus"></i><span>Nota de crédito</span></div></div><div class="card-body"><button class="btn btn-outline-secondary w-100" disabled>Emitir nota de crédito · Próximamente</button><p class="small text-muted mt-3">Permitirá anulación tributaria, devolución, descuento o corrección.</p><div class="alert alert-warning mb-0"><strong>Anulación y devolución física son acciones distintas.</strong> Anular conserva el historial y no devuelve automáticamente la mercadería.</div></div></section></div></div>
<?php include '../../includes/footer.php'; ?>
