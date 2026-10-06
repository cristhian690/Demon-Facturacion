<?php
// Controlled end-to-end scenario. Defaults to temporary data and never touches project JSON.
// CONTROLLED_OUTPUT_DIR may point to an empty staging directory for a reviewed data reset.
$externalOutput=getenv('CONTROLLED_OUTPUT_DIR');
$temp=$externalOutput?:sys_get_temp_dir().'/controlled-scenarios-'.bin2hex(random_bytes(8));
if(!is_dir($temp))mkdir($temp,0777,true);
define('DATA_PATH',$temp.'/'); define('BASE_URL','/');
$_SESSION=['empresa_id'=>1,'empresa_nombre'=>'Empresa Demo S.A.C.','form_token'=>'controlled-token','usuario'=>['nombre'=>'Administrador','rol'=>'Administrador']];
require __DIR__.'/../includes/helpers.php';
require __DIR__.'/../includes/returns.php';
require __DIR__.'/../includes/finance.php';
$checks=0;
function controlled_ok($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
function controlled_put($name,$rows){file_put_contents(DATA_PATH.$name.'.json',json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));}
function controlled_tx($callback){return data_transaction($callback);}
function controlled_operation($sale,$input){return controlled_tx(fn()=>process_operation($sale,$input));}
function controlled_inventory($product){foreach(get_data('inventario') as $row)if((int)$row['producto_id']===$product&&(int)$row['almacen_id']===1)return $row;return null;}
function controlled_input($changes=[]){
 static $request=0;$request++;
 return array_replace(['empresa_id'=>'1','form_token'=>'controlled-token','request_id'=>str_pad(dechex($request),32,'0',STR_PAD_LEFT),'almacen_id'=>'1','tipo_documento'=>'Factura','serie'=>'C001','numero'=>(string)$request,'fecha'=>'2026-09-25','productos'=>['1'],'cantidades'=>['1'],'costos'=>['1'],'precios'=>['1'],'descuentos'=>['0']],$changes);
}
try{
 controlled_put('empresas',[['id'=>1,'razon_social'=>'Empresa Demo S.A.C.','ruc'=>'20123456789','direccion'=>'Av. Principal 123, Lima','telefono'=>'01-123-4567','correo'=>'contacto@empresademo.com','logo'=>'logo-empresa-1.png']]);
 controlled_put('almacenes',[['id'=>1,'empresa_id'=>1,'nombre'=>'Almacén Principal','ubicacion'=>'Sede Central','estado'=>'Activo']]);
 controlled_put('clientes',[
  ['id'=>1,'empresa_id'=>1,'tipo_documento'=>'DNI','numero_documento'=>'70000001','nombre'=>'Cliente Contado','direccion'=>'Lima','telefono'=>'','correo'=>''],
  ['id'=>2,'empresa_id'=>1,'tipo_documento'=>'RUC','numero_documento'=>'20111111111','nombre'=>'Cliente Crédito','direccion'=>'Lima','telefono'=>'','correo'=>'']
 ]);
 controlled_put('proveedores',[
  ['id'=>1,'empresa_id'=>1,'tipo_documento'=>'RUC','numero_documento'=>'20222222221','nombre'=>'Proveedor Uno','direccion'=>'Lima','telefono'=>'','correo'=>''],
  ['id'=>2,'empresa_id'=>1,'tipo_documento'=>'RUC','numero_documento'=>'20222222222','nombre'=>'Proveedor Dos','direccion'=>'Lima','telefono'=>'','correo'=>'']
 ]);
 controlled_put('productos',[
  ['id'=>1,'empresa_id'=>1,'sku'=>'PROD-A','nombre'=>'Producto A','descripcion'=>'Producto controlado A','unidad_medida'=>'UN','categoria'=>'General','marca'=>'','stock_minimo'=>10,'estado'=>'Activo'],
  ['id'=>2,'empresa_id'=>1,'sku'=>'PROD-B','nombre'=>'Producto B','descripcion'=>'Producto controlado B','unidad_medida'=>'UN','categoria'=>'General','marca'=>'','stock_minimo'=>5,'estado'=>'Activo']
 ]);
 foreach(['compras','ventas','inventario','kardex','devoluciones','pagos','reembolsos','auditoria'] as $table)controlled_put($table,[]);

 // 1: Purchase A, 100 @ 10.
 controlled_operation(false,controlled_input(['proveedor_id'=>'1','numero'=>'1','fecha'=>'2026-09-25','productos'=>['1'],'cantidades'=>['100'],'costos'=>['10']]));
 $a=controlled_inventory(1);controlled_ok($a['stock_actual']==100&&$a['cpp']==10&&$a['valor_inventario']==1000,'Case 1: first purchase A');

 // 2: Purchase A, 50 @ 12. Expected CPP 1600/150.
 controlled_operation(false,controlled_input(['proveedor_id'=>'2','numero'=>'2','fecha'=>'2026-09-26','productos'=>['1'],'cantidades'=>['50'],'costos'=>['12']]));
 $a=controlled_inventory(1);$expectedCpp=1600/150;
 controlled_ok($a['stock_actual']==150&&abs($a['cpp']-$expectedCpp)<0.000001&&$a['valor_inventario']==1600,'Case 2: weighted CPP');

 // 3: Purchase B, 20 @ 5.
 controlled_operation(false,controlled_input(['proveedor_id'=>'1','numero'=>'3','fecha'=>'2026-09-27','productos'=>['2'],'cantidades'=>['20'],'costos'=>['5']]));
 $b=controlled_inventory(2);controlled_ok($b['stock_actual']==20&&$b['cpp']==5&&$b['valor_inventario']==100,'Case 3: purchase B');

 // 4: Immediate sale A, 30 @ 20.
 controlled_operation(true,controlled_input(['cliente_id'=>'1','serie'=>'F001','numero'=>'1','fecha'=>'2026-09-28','productos'=>['1'],'cantidades'=>['30'],'precios'=>['20'],'entrega'=>'inmediata','condicion_pago'=>'contado']));
 $a=controlled_inventory(1);$saleA=get_data('ventas')[0];$dispatchDetails=$saleA['despachos'][0]['detalles'];$last=end($dispatchDetails);
 controlled_ok($a['stock_actual']==120&&abs($saleA['costo_ventas_total']-30*$expectedCpp)<0.000001&&$last['costo_unitario']==$expectedCpp,'Case 4: output uses CPP');
 $cashFinance=sale_financials($saleA);controlled_ok($cashFinance['pagado']==708&&$cashFinance['saldo']==0&&$cashFinance['estado']==='Pagado','Cash sale is paid automatically');
 $doublePay=['empresa_id'=>'1','form_token'=>'controlled-token','request_id'=>str_repeat('a',32),'venta_id'=>(string)$saleA['id'],'fecha'=>'2026-09-28','importe'=>'1','medio'=>'Efectivo','referencia'=>''];
 $paymentFilesBefore=hash_file('sha256',DATA_PATH.'pagos.json');$doubleRejected=false;try{controlled_tx(fn()=>process_payment($doublePay));}catch(InvalidArgumentException $e){$doubleRejected=str_contains($e->getMessage(),'ya quedó pagada');}
 controlled_ok($doubleRejected&&hash_file('sha256',DATA_PATH.'pagos.json')===$paymentFilesBefore,'Cash sale rejects a duplicate manual payment');

 // 5: Immediate sale B, all 20.
 controlled_operation(true,controlled_input(['cliente_id'=>'1','serie'=>'F001','numero'=>'2','fecha'=>'2026-09-29','productos'=>['2'],'cantidades'=>['20'],'precios'=>['9'],'entrega'=>'inmediata','condicion_pago'=>'contado']));
 $b=controlled_inventory(2);controlled_ok($b['stock_actual']==0&&$b['cpp']==5&&$b['valor_inventario']==0,'Case 5: B without stock');

 // 6: Failed sale B leaves all files unchanged.
 $before=[];foreach(glob(DATA_PATH.'*.json') as $file)$before[basename($file)]=hash_file('sha256',$file);
 $failed=false;try{controlled_operation(true,controlled_input(['cliente_id'=>'1','serie'=>'F001','numero'=>'3','fecha'=>'2026-09-29','productos'=>['2'],'cantidades'=>['1'],'precios'=>['9'],'entrega'=>'inmediata','condicion_pago'=>'contado']));}catch(InvalidArgumentException $e){$failed=str_contains($e->getMessage(),'Stock insuficiente');}
 $after=[];foreach(glob(DATA_PATH.'*.json') as $file)$after[basename($file)]=hash_file('sha256',$file);
 controlled_ok($failed&&$before===$after,'Case 6: insufficient stock is atomic');

 // 7: Pending delivery A, 20.
 controlled_operation(true,controlled_input(['cliente_id'=>'1','serie'=>'F001','numero'=>'4','fecha'=>'2026-09-30','productos'=>['1'],'cantidades'=>['20'],'precios'=>['22'],'entrega'=>'pendiente','condicion_pago'=>'contado']));
 $pending=get_data('ventas')[2];$pendingFinance=sale_financials($pending);
 controlled_ok(controlled_inventory(1)['stock_actual']==120&&dispatch_status($pending)==='Pendiente','Case 7: pending sale does not reduce stock');
 controlled_ok($pendingFinance['saldo']==0&&$pendingFinance['estado']==='Pagado','Cash payment is independent from pending delivery');

 // 8: Dispatch 10 from pending sale.
 $dispatch=['empresa_id'=>'1','form_token'=>'controlled-token','request_id'=>str_repeat('d',32),'venta_id'=>(string)$pending['id'],'fecha'=>'2026-10-01','cantidades'=>['10']];
 controlled_tx(fn()=>process_dispatch($dispatch));$pending=get_data('ventas')[2];
 controlled_ok(controlled_inventory(1)['stock_actual']==110&&$pending['detalles'][0]['despachado']==10&&dispatch_status($pending)==='Entrega parcial','Case 8: partial dispatch');

 // 9: Return 5 from immediate sale A at historical dispatch cost.
 $return=['empresa_id'=>'1','form_token'=>'controlled-token','request_id'=>str_repeat('e',32),'venta_id'=>(string)$saleA['id'],'fecha'=>'2026-10-02','motivo'=>'Devolución física controlada','cantidades'=>['5']];
 controlled_tx(fn()=>process_return($return));$a=controlled_inventory(1);$ledgerAfterReturn=get_data('kardex');$returnMovement=end($ledgerAfterReturn);
 controlled_ok($a['stock_actual']==115&&abs($a['cpp']-$expectedCpp)<0.000000000001&&abs($returnMovement['entrada_costo']-$expectedCpp)<0.000000000001&&$returnMovement['tipo_operacion']==='DEVOLUCION_VENTA','Case 9: physical return preserves CPP precision');

 // 10: Immediate credit sale A, 10, then partial payment.
 controlled_operation(true,controlled_input(['cliente_id'=>'2','serie'=>'F001','numero'=>'5','fecha'=>'2026-10-03','productos'=>['1'],'cantidades'=>['10'],'precios'=>['24'],'entrega'=>'inmediata','condicion_pago'=>'credito','fecha_vencimiento'=>'2026-10-20']));
 $creditSale=get_data('ventas')[3];$financial=sale_financials($creditSale);
 controlled_ok($financial['cargo']==283.2&&$financial['saldo']==283.2&&$financial['estado']==='Pendiente','Case 10: credit receivable');
 $payment=['empresa_id'=>'1','form_token'=>'controlled-token','request_id'=>str_repeat('f',32),'venta_id'=>(string)$creditSale['id'],'fecha'=>'2026-10-04','importe'=>'100','medio'=>'Transferencia','referencia'=>'PAGO-CONTROLADO-1'];
 controlled_tx(fn()=>process_payment($payment));$financial=sale_financials(get_data('ventas')[3]);
 controlled_ok($financial['pagado']==100&&$financial['saldo']==183.2&&$financial['estado']==='Parcialmente pagado','Case 10: partial payment');

 $a=controlled_inventory(1);$b=controlled_inventory(2);
 controlled_ok($a['stock_actual']==105&&abs($a['cpp']-$expectedCpp)<0.000000000001&&abs($a['valor_inventario']-1120)<0.000000001,'Final A inventory');
 controlled_ok($b['stock_actual']==0&&$b['cpp']==5&&$b['valor_inventario']==0,'Final B inventory');
 controlled_ok(count(get_data('inventario'))===2,'No duplicate product and warehouse inventory');
 controlled_ok(count(get_data('compras'))===3&&count(get_data('ventas'))===4&&count(get_data('kardex'))===8&&count(get_data('devoluciones'))===1&&count(get_data('pagos'))===4,'Final operation counts');
 $receivables=array_values(array_filter(get_data('ventas'),fn($sale)=>sale_financials($sale)['saldo']>0));
 controlled_ok(count($receivables)===1&&$receivables[0]['condicion_pago']==='credito'&&$receivables[0]['id']===$creditSale['id'],'Only the credit sale remains receivable');
 foreach(['ventas'=>'cliente_id','compras'=>'proveedor_id'] as $table=>$field)foreach(get_data($table) as $row)controlled_ok((bool)array_filter(get_data($table==='ventas'?'clientes':'proveedores'),fn($master)=>$master['id']==$row[$field]),'No orphan party in '.$table);
 foreach(get_data('kardex') as $row){controlled_ok((bool)array_filter(get_data('productos'),fn($p)=>$p['id']==$row['producto_id']),'No orphan Kardex product');controlled_ok((bool)array_filter(get_data('almacenes'),fn($w)=>$w['id']==$row['almacen_id']),'No orphan Kardex warehouse');}
 controlled_ok(count(get_data('empresas'))===1&&count(get_data('productos'))===2&&count(get_data('clientes'))===2&&count(get_data('proveedores'))===2&&count(get_data('almacenes'))===1,'Controlled catalogs');
 echo "OK: $checks verificaciones de 10 casos controlados; solo datos temporales.\n";
 echo json_encode(['stock_a'=>$a,'stock_b'=>$b,'cpp_esperado'=>$expectedCpp,'finanzas'=>$financial,'counts'=>['compras'=>count(get_data('compras')),'ventas'=>count(get_data('ventas')),'kardex'=>count(get_data('kardex'))]],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
}finally{if(!$externalOutput){foreach(glob(DATA_PATH.'*') as $file)unlink($file);if(is_file(DATA_PATH.'.write.lock'))unlink(DATA_PATH.'.write.lock');rmdir($temp);}}

