<?php
$ventasSunat=get_data('ventas');
$clientesSunat=array_column(get_data('clientes'),'nombre','id');
?>
<div class="table-responsive">
<table class="table align-middle mb-0 sunat-documents-table">
 <thead><tr><th>Tipo</th><th>Serie</th><th>Número</th><th>Cliente</th><th>Fecha</th><th class="text-end">Total</th><th>Estado interno</th><th>Estado SUNAT</th><?php if(!empty($showFiles)): ?><th>XML</th><th>CDR</th><th class="text-end">Acciones</th><?php endif; ?></tr></thead>
 <tbody>
 <?php if(!$ventasSunat): ?><tr><td colspan="<?php echo !empty($showFiles)?11:8; ?>" class="text-center py-5 text-muted">No hay comprobantes en la empresa activa.</td></tr><?php endif; ?>
 <?php foreach($ventasSunat as $venta): ?><tr>
  <td><span class="sale-doc-type"><i class="bi bi-receipt"></i><?php echo htmlspecialchars($venta['tipo_documento']??'Comprobante'); ?></span></td>
  <td><?php echo htmlspecialchars($venta['serie']??'—'); ?></td><td><strong><?php echo htmlspecialchars($venta['numero']??'—'); ?></strong></td>
  <td><?php echo htmlspecialchars($clientesSunat[$venta['cliente_id']]??'Cliente no disponible'); ?></td>
  <td class="text-nowrap"><?php echo !empty($venta['fecha'])?date('d/m/Y',strtotime($venta['fecha'])):'—'; ?></td>
  <td class="text-end fw-semibold"><?php echo format_money($venta['total']??0); ?></td>
  <td><span class="document-state"><?php echo htmlspecialchars($venta['estado_documento']??'Vigente'); ?></span></td>
  <td><span class="sunat-not-sent"><i class="bi bi-dash-circle"></i>No enviado</span></td>
  <?php if(!empty($showFiles)): ?><td><span class="file-unavailable">No generado</span></td><td><span class="file-unavailable">No disponible</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo url('pages/ventas/detalle.php?id='.$venta['id']); ?>">Ver comprobante</a></td><?php endif; ?>
 </tr><?php endforeach; ?>
 </tbody>
</table>
</div>

