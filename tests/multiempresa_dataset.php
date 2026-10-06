<?php
// Read-only validation of the presentation dataset. It never writes project data.
require __DIR__.'/../config.php'; require __DIR__.'/../includes/helpers.php'; require __DIR__.'/../includes/finance.php';
$checks=0; function dataset_ok($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
$companies=read_data('empresas');
$expected=['Empresa Demo S.A.C.','Comercial Andina S.A.C.','Distribuidora del Sur S.A.C.','Inversiones Pacífico S.A.C.'];
dataset_ok(count($companies)===4,'Deben existir cuatro empresas');
dataset_ok(array_column($companies,'razon_social')===$expected,'Empresas esperadas y ordenadas');
dataset_ok(count(array_unique(array_column($companies,'id')))===4 && count(array_filter($companies,fn($r)=>($r['estado']??'Activo')==='Activo'))===4,'IDs únicos y cuatro activas');
$allIds=[]; $metricSignatures=[]; $receivables=[];
foreach($companies as $company){
    $_SESSION['empresa_id']=$company['id']; $_SESSION['empresa_nombre']=$company['razon_social'];
    $counts=['clientes'=>5,'proveedores'=>5,'productos'=>5,'almacenes'=>2,'compras'=>3,'ventas'=>3,'inventario'=>5,'kardex'=>8];
    foreach($counts as $table=>$expectedCount){$rows=get_data($table);dataset_ok(count($rows)===$expectedCount,$company['razon_social']." $table aislado");dataset_ok(count(array_filter($rows,fn($r)=>(int)$r['empresa_id']===(int)$company['id']))===$expectedCount,"$table pertenece a la empresa activa");}
    foreach(['clientes','proveedores','productos','almacenes'] as $table){foreach(get_data($table) as $row){dataset_ok(!isset($allIds[$table][$row['id']]),"ID global único en $table");$allIds[$table][$row['id']]=true;dataset_ok(($row['estado']??'Activo')==='Activo',"$table activo");}}
    $clients=array_column(get_data('clientes'),null,'id');$suppliers=array_column(get_data('proveedores'),null,'id');$products=array_column(get_data('productos'),null,'id');$warehouses=array_column(get_data('almacenes'),null,'id');
    foreach(get_data('compras') as $doc){dataset_ok(isset($suppliers[$doc['proveedor_id']],$warehouses[$doc['almacen_id']]),'Compra sin huérfanos');foreach($doc['detalles'] as $line)dataset_ok(isset($products[$line['producto_id']]),'Detalle de compra válido');}
    foreach(get_data('ventas') as $doc){dataset_ok(isset($clients[$doc['cliente_id']],$warehouses[$doc['almacen_id']]),'Venta sin huérfanos');foreach($doc['detalles'] as $line)dataset_ok(isset($products[$line['producto_id']]),'Detalle de venta válido');}
    foreach(get_data('inventario') as $row)dataset_ok(isset($products[$row['producto_id']],$warehouses[$row['almacen_id']]),'Inventario sin huérfanos');
    foreach(get_data('kardex') as $row)dataset_ok(isset($products[$row['producto_id']],$warehouses[$row['almacen_id']]),'Kardex sin huérfanos');
    $stocks=array_column(get_data('inventario'),'stock_actual');dataset_ok(in_array(0,$stocks,true)||in_array(0.0,$stocks,true),'Existe producto sin stock');dataset_ok(count(array_filter($stocks,fn($v)=>$v>0&&$v<=3))>=1,'Existe stock bajo');dataset_ok(count(array_filter($stocks,fn($v)=>$v>3))>=1,'Existe stock normal');
    $sales=get_data('ventas');$pending=0;foreach($sales as $sale){$finance=sale_financials($sale);if($finance['saldo']>0)$pending+=$finance['saldo'];if($sale['condicion_pago']==='contado')dataset_ok($finance['saldo']===0.0||$finance['saldo']===0,'Contado pagado automáticamente');}
    $receivables[$company['razon_social']]=round($pending,2);
    $metricSignatures[]=array_sum(array_column(get_data('compras'),'total')).'|'.array_sum(array_column($sales,'total')).'|'.array_sum(array_column(get_data('inventario'),'valor_inventario'));
}
dataset_ok(count(array_unique($metricSignatures))===4,'Dashboard tendrá métricas distintas por empresa');
dataset_ok($receivables['Empresa Demo S.A.C.']>0 && $receivables['Comercial Andina S.A.C.']>0,'Dos empresas con crédito pendiente');
dataset_ok($receivables['Distribuidora del Sur S.A.C.']>0 && $receivables['Distribuidora del Sur S.A.C.']<$receivables['Comercial Andina S.A.C.'],'Pago parcial reduce saldo');
dataset_ok($receivables['Inversiones Pacífico S.A.C.']===0.0,'Crédito pagado no queda por cobrar');
$_SESSION['empresa_id']=1;$first=array_column(get_data('clientes'),'id');$_SESSION['empresa_id']=2;$second=array_column(get_data('clientes'),'id');dataset_ok(!$first||!array_intersect($first,$second),'Cambiar empresa cambia completamente el contexto');
echo "MULTIEMPRESA OK: $checks verificaciones de datos, aislamiento, relaciones y cuentas por cobrar.\n";
