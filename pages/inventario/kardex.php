<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../includes/kardex.php';

$productos=get_data('productos'); $almacenes=get_data('almacenes'); $inventario=get_data('inventario');
$kardexRows=get_data('kardex'); $compras=get_data('compras'); $ventas=get_data('ventas'); $empresa=get_empresa_activa();
$get=function($name){$value=$_GET[$name]??'';return is_string($value)?trim($value):'';};
$producto_id=$get('producto_id'); $almacen_id=$get('almacen_id'); $desde=$get('desde'); $hasta=$get('hasta');
$venta_id=$get('venta_id'); $tipo_operacion=$get('tipo_operacion');
$typeLabels=['COMPRA'=>'Compra','VENTA'=>'Venta / despacho','DEVOLUCION_VENTA'=>'Devolución de venta','SALDO INICIAL'=>'Saldo inicial'];
$typeLabel=fn($type)=>$typeLabels[$type]??ucwords(strtolower(str_replace('_',' ',(string)$type)));
$typeClass=fn($type)=>$type==='COMPRA'?'movement-entry':($type==='VENTA'?'movement-exit':($type==='DEVOLUCION_VENTA'?'movement-return':'movement-other'));
$operationTypes=[];
foreach($kardexRows as $row){$type=(string)($row['tipo_operacion']??'');if($type!=='')$operationTypes[$type]=true;}
$operationTypes=array_keys($operationTypes); sort($operationTypes,SORT_NATURAL|SORT_FLAG_CASE);
$filterError=''; $movimientos=[]; $productoSeleccionado=null; $almacenSeleccionado=null;
try{
 $movimientos=filter_kardex($kardexRows,['producto_id'=>$producto_id,'almacen_id'=>$almacen_id,'desde'=>$desde,'hasta'=>$hasta,'venta_id'=>$venta_id,'tipo_operacion'=>$tipo_operacion]);
 if($producto_id!=='')$productoSeleccionado=owned_record('productos',$producto_id);
 if($almacen_id!=='')$almacenSeleccionado=owned_record('almacenes',$almacen_id);
}catch(InvalidArgumentException $e){$filterError=$e->getMessage();}
$productoNames=array_column($productos,'nombre','id'); $almacenNames=array_column($almacenes,'nombre','id'); $ventasById=array_column($ventas,null,'id');
$purchaseDocuments=[];
foreach($compras as $compra){$doc=trim(($compra['tipo_documento']??'').' '.($compra['serie']??'').'-'.($compra['numero']??''));if($doc!=='')$purchaseDocuments[$doc][]=$compra;}
$summary=['entrada_cantidad'=>0.0,'entrada_valor'=>0.0,'salida_cantidad'=>0.0,'salida_valor'=>0.0]; $movementDates=[]; $hasNegative=false;
foreach($movimientos as $m){
 foreach($summary as $field=>$unused)$summary[$field]+=(float)($m[$field]??0);
 $day=substr((string)$m['fecha'],0,10); if(!isset($movementDates[$day]))$movementDates[$day]=['entrada'=>0.0,'salida'=>0.0];
 $movementDates[$day]['entrada']+=(float)($m['entrada_valor']??0); $movementDates[$day]['salida']+=(float)($m['salida_valor']??0);
 if((float)($m['saldo_cantidad']??0)<0)$hasNegative=true;
}
$currentRows=[];
if($producto_id!=='')foreach($inventario as $row)if((string)$row['producto_id']===$producto_id&&($almacen_id===''||(string)$row['almacen_id']===$almacen_id))$currentRows[]=$row;
$currentStock=array_sum(array_map(fn($r)=>(float)($r['stock_actual']??0),$currentRows));
$currentValue=array_sum(array_map(fn($r)=>(float)($r['valor_inventario']??0),$currentRows));
$currentCpp=count($currentRows)===1?(float)$currentRows[0]['cpp']:null;
$currentWarehouse=$almacenSeleccionado['nombre']??(count($currentRows)===1?($almacenNames[$currentRows[0]['almacen_id']]??'Almacén no disponible'):(count($currentRows)>1?count($currentRows).' almacenes':'Sin existencia actual'));
$exportFilters=array_filter(['empresa_id'=>(string)($_SESSION['empresa_id']??''),'producto_id'=>$producto_id,'almacen_id'=>$almacen_id,'tipo_operacion'=>$tipo_operacion,'desde'=>$desde,'hasta'=>$hasta,'venta_id'=>$venta_id],fn($value)=>$value!=='');
?>
<?php include '../../includes/header.php'; ?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
 <div><span class="page-eyebrow">Registro técnico de inventario</span><h1 class="h3 mb-1">Kardex Valorizado</h1><p class="text-muted mb-0">Historial valorizado de entradas, salidas y saldos de inventario por producto y almacén.</p></div>
 <div class="d-flex gap-2"><a class="btn btn-outline-success" href="<?php echo url('actions/exportar_kardex.php?'.http_build_query($exportFilters)); ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i><span>Exportar Kardex</span></a><a href="<?php echo url('pages/inventario/index.php'); ?>" class="btn btn-outline-primary"><i class="bi bi-boxes me-1"></i>Inventario</a></div>
</div>
<section class="card kardex-filter-card mb-4">
 <div class="card-header"><div><span class="chart-kicker">Consulta histórica</span><h2 class="h6 mb-0">Filtros del Kardex</h2></div><span class="active-company"><i class="bi bi-building"></i><?php echo htmlspecialchars($empresa['nombre']); ?></span></div>
 <div class="card-body">
 <?php if($venta_id): ?><div class="kardex-context mb-3"><i class="bi bi-receipt"></i><span>Movimientos relacionados con la venta #<?php echo htmlspecialchars($venta_id); ?>.</span><a href="<?php echo url('pages/inventario/kardex.php'); ?>">Quitar relación</a></div><?php endif; ?>
 <form method="GET" class="kardex-filter-grid"><?php if($venta_id): ?><input type="hidden" name="venta_id" value="<?php echo htmlspecialchars($venta_id); ?>"><?php endif; ?>
  <label><span>Producto</span><select name="producto_id" class="form-select"><option value="">Todos los productos</option><?php foreach($productos as $p): ?><option value="<?php echo (int)$p['id']; ?>" <?php echo $producto_id==$p['id']?'selected':''; ?>><?php echo htmlspecialchars(($p['sku']??'S/C').' · '.$p['nombre']); ?></option><?php endforeach; ?></select></label>
  <label><span>Almacén</span><select name="almacen_id" class="form-select"><option value="">Todos los almacenes</option><?php foreach($almacenes as $a): ?><option value="<?php echo (int)$a['id']; ?>" <?php echo $almacen_id==$a['id']?'selected':''; ?>><?php echo htmlspecialchars($a['nombre']); ?></option><?php endforeach; ?></select></label>
  <label><span>Operación</span><select name="tipo_operacion" class="form-select"><option value="">Todas las operaciones</option><?php foreach($operationTypes as $type): ?><option value="<?php echo htmlspecialchars($type); ?>" <?php echo $tipo_operacion===$type?'selected':''; ?>><?php echo htmlspecialchars($typeLabel($type)); ?></option><?php endforeach; ?></select></label>
  <label><span>Desde (incluido)</span><input type="date" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>"></label>
  <label><span>Hasta (incluido)</span><input type="date" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>"></label>
  <div class="kardex-filter-actions"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Aplicar</button><a class="btn btn-light" href="<?php echo url('pages/inventario/kardex.php'); ?>">Limpiar</a></div>
 </form></div>
</section>
<?php if($filterError): ?><div class="alert alert-danger"><?php echo htmlspecialchars($filterError); ?></div><?php else: ?>
<?php if($productoSeleccionado): ?>
<section class="kardex-product-summary mb-4">
 <div class="kardex-product-main"><span class="kardex-product-icon"><i class="bi bi-box-seam"></i></span><div><small>Producto seleccionado</small><h2><?php echo htmlspecialchars($productoSeleccionado['nombre']); ?></h2><span><?php echo htmlspecialchars($productoSeleccionado['sku']??'Sin código'); ?> · <?php echo htmlspecialchars($productoSeleccionado['unidad_medida']??'Unidad no disponible'); ?></span></div></div>
 <div><small>Almacén</small><strong><?php echo htmlspecialchars($currentWarehouse); ?></strong></div><div><small>Stock actual</small><strong><?php echo quantity_display($currentStock); ?></strong></div><div><small>CPP actual</small><strong><?php echo $currentCpp===null?'Varía por almacén':format_money($currentCpp); ?></strong></div><div><small>Valor actual</small><strong><?php echo format_money($currentValue); ?></strong></div>
</section><?php endif; ?>
<div class="kardex-metrics mb-4">
 <div class="kardex-metric entry"><small>Total entradas visibles</small><strong><?php echo quantity_display($summary['entrada_cantidad']); ?></strong><span><?php echo format_money($summary['entrada_valor']); ?></span></div>
 <div class="kardex-metric exit"><small>Total salidas visibles</small><strong><?php echo quantity_display($summary['salida_cantidad']); ?></strong><span><?php echo format_money($summary['salida_valor']); ?></span></div>
 <div class="kardex-metric movements"><small>Cantidad de movimientos</small><strong><?php echo count($movimientos); ?></strong><span>Según filtros aplicados</span></div>
 <div class="kardex-metric balance"><small>Saldo actual</small><strong><?php echo $producto_id!==''&&$almacen_id!==''?quantity_display($currentStock):'—'; ?></strong><span><?php echo $producto_id!==''&&$almacen_id!==''?format_money($currentValue):'Selecciona producto y almacén'; ?></span></div>
</div>
<div class="kardex-cpp-help mb-4"><i class="bi bi-info-circle"></i><div><strong>Cómo se usa el CPP</strong><span>El Costo Promedio Ponderado (CPP) se actualiza con las entradas de compra y se utiliza como costo de salida en ventas y despachos. Los saldos y el CPP de cada fila son snapshots históricos guardados; los filtros no los recalculan.</span></div></div>
<?php if($hasNegative): ?><div class="kardex-warning mb-4"><i class="bi bi-exclamation-triangle-fill"></i><span><strong>Saldo negativo</strong>El registro histórico se muestra sin modificarlo.</span></div><?php endif; ?>
<section class="card chart-card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><div><span class="chart-kicker">Periodo visible</span><h2 class="h6 mb-0">Flujo valorizado de entradas y salidas</h2></div><span class="text-muted small"><?php echo count($movimientos); ?> movimiento(s)</span></div><div class="card-body"><div class="chart-wrap chart-wrap-kardex"><canvas id="kardexMovementChart" aria-label="Entradas y salidas valorizadas del Kardex"></canvas></div></div></section>
<script id="kardexChartData" type="application/json"><?php echo json_encode(['labels'=>array_map(fn($d)=>date('d/m',strtotime($d)),array_keys($movementDates)),'entries'=>array_column($movementDates,'entrada'),'exits'=>array_column($movementDates,'salida')],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?></script>
<section class="card kardex-ledger mb-4" id="movimientos">
 <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><span class="chart-kicker">Libro de movimientos</span><h2 class="h6 mb-0"><?php echo htmlspecialchars($productoSeleccionado['nombre']??'Todos los productos'); ?></h2></div><span class="text-muted small">Orden cronológico · <?php echo count($movimientos); ?> registro(s)</span></div>
 <div class="table-responsive kardex-scroll"><table class="table table-sm align-middle mb-0 kardex-table"><thead class="text-center align-middle">
  <tr><th colspan="4" class="block-movement">Datos del movimiento</th><th colspan="3" class="block-entry">Entradas</th><th colspan="3" class="block-exit">Salidas</th><th colspan="3" class="block-balance">Saldo histórico</th></tr>
  <tr><th>Producto / almacén</th><th>Fecha</th><th>Documento / origen</th><th>Operación</th><th>Cantidad</th><th>Costo unit.</th><th>Valor</th><th>Cantidad</th><th>Costo unit.</th><th>Valor</th><th>Cantidad</th><th>CPP</th><th>Valor</th></tr>
 </thead><tbody>
 <?php if(!$movimientos): ?><tr><td colspan="13" class="text-center py-5 text-muted">No hay movimientos para los filtros seleccionados.</td></tr><?php endif; ?>
 <?php foreach($movimientos as $m): $type=(string)($m['tipo_operacion']??''); $originLink=null; $originLabel='';
  if(!empty($m['venta_id'])&&isset($ventasById[$m['venta_id']])){$originLink=url('pages/ventas/detalle.php?id='.$m['venta_id']);$originLabel=$type==='DEVOLUCION_VENTA'?'Ver venta relacionada':'Ver comprobante';}
  $matches=$purchaseDocuments[(string)($m['documento']??'')]??[]; if($type==='COMPRA'&&count($matches)===1){$originLink=url('pages/compras/detalle.php?id='.$matches[0]['id']);$originLabel='Ver compra';}
  $negative=(float)($m['saldo_cantidad']??0)<0; ?>
  <?php $orphanProduct=!isset($productoNames[$m['producto_id']]); $orphanWarehouse=!isset($almacenNames[$m['almacen_id']]); ?>
  <tr class="<?php echo $negative?'kardex-negative-row':''; ?>">
   <td class="<?php echo ($orphanProduct||$orphanWarehouse)?'orphan-record':''; ?>"><strong><?php if($orphanProduct): ?><i class="bi bi-exclamation-circle"></i><?php endif; ?><?php echo htmlspecialchars($productoNames[$m['producto_id']]??'Producto pendiente #'.$m['producto_id']); ?></strong><small><i class="bi bi-shop"></i><?php echo htmlspecialchars($almacenNames[$m['almacen_id']]??'Almacén no disponible #'.$m['almacen_id']); ?></small></td>
   <td class="text-center text-nowrap"><?php echo date('d/m/Y',strtotime($m['fecha'])); ?></td>
   <td><span class="document-number"><?php echo htmlspecialchars($m['documento']??'No disponible'); ?></span><?php if($originLink): ?><a href="<?php echo $originLink; ?>"><?php echo $originLabel; ?> <i class="bi bi-arrow-up-right"></i></a><?php else: ?><small>Origen sin enlace directo</small><?php endif; ?></td>
   <td class="text-center"><span class="movement-badge <?php echo $typeClass($type); ?>"><?php echo htmlspecialchars($typeLabel($type)); ?></span></td>
   <td class="number entry-cell"><?php echo (float)($m['entrada_cantidad']??0)>0?quantity_display($m['entrada_cantidad']):'—'; ?></td><td class="number entry-cell"><?php echo (float)($m['entrada_cantidad']??0)>0?format_money($m['entrada_costo']):'—'; ?></td><td class="number entry-cell strong"><?php echo (float)($m['entrada_cantidad']??0)>0?format_money($m['entrada_valor']):'—'; ?></td>
   <td class="number exit-cell"><?php echo (float)($m['salida_cantidad']??0)>0?quantity_display($m['salida_cantidad']):'—'; ?></td><td class="number exit-cell"><?php echo (float)($m['salida_cantidad']??0)>0?format_money($m['salida_costo']):'—'; ?></td><td class="number exit-cell strong"><?php echo (float)($m['salida_cantidad']??0)>0?format_money($m['salida_valor']):'—'; ?></td>
   <td class="number balance-cell"><strong><?php echo quantity_display($m['saldo_cantidad']); ?></strong><?php if($negative): ?><span class="negative-label">Saldo negativo</span><?php endif; ?></td><td class="number balance-cell"><?php echo format_money($m['cpp']); ?></td><td class="number balance-cell strong"><?php echo format_money($m['saldo_valor']); ?></td>
  </tr><?php endforeach; ?>
 </tbody></table></div><div class="kardex-ledger-note"><i class="bi bi-shield-check"></i>La secuencia histórica se protege: no se insertan movimientos retroactivos que invaliden saldos registrados.</div>
</section>
<?php endif; ?>
<?php $page_scripts=['assets/js/kardex-chart.js']; include '../../includes/footer.php'; ?>
