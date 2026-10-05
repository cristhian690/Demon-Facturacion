<?php
$temp=sys_get_temp_dir().'/returns-finance-'.bin2hex(random_bytes(8)); mkdir($temp); define('DATA_PATH',$temp.'/'); define('BASE_URL','/');
$_SESSION=['empresa_id'=>1,'form_token'=>'test','usuario'=>['nombre'=>'Tester']];
require __DIR__.'/../includes/helpers.php'; require __DIR__.'/../includes/returns.php'; require __DIR__.'/../includes/finance.php';
$checks=0; function ok_rf($value,$label){global $checks;if(!$value)throw new RuntimeException($label);$checks++;} function put_rf($name,$rows){file_put_contents(DATA_PATH.$name.'.json',json_encode($rows));}
try{
 put_rf('clientes',[['id'=>1,'empresa_id'=>1,'nombre'=>'Cliente']]); put_rf('proveedores',[]); put_rf('almacenes',[['id'=>1,'empresa_id'=>1,'nombre'=>'Central','estado'=>'Activo']]); put_rf('productos',[['id'=>1,'empresa_id'=>1,'nombre'=>'Granel','unidad_medida'=>'KG','estado'=>'Activo']]); foreach(['ventas','compras','inventario','kardex','pagos','reembolsos','devoluciones','auditoria'] as $t)put_rf($t,[]);
 $base=['empresa_id'=>'1','form_token'=>'test','request_id'=>str_repeat('a',32),'cliente_id'=>'1','almacen_id'=>'1','tipo_documento'=>'Factura','serie'=>'F001','numero'=>'1','fecha'=>'2026-10-01','productos'=>['1'],'cantidades'=>['10'],'precios'=>['20'],'costos'=>['8'],'descuentos'=>['0']];
 $buy=$base;$buy['proveedor_id']='1';put_rf('proveedores',[['id'=>1,'empresa_id'=>1,'nombre'=>'Proveedor']]);$buy['request_id']=str_repeat('b',32);process_operation(false,$buy);$base['condicion_pago']='credito';$base['fecha_vencimiento']='2026-10-10';process_operation(true,$base);$sale=get_data('ventas')[0];
 ok_rf(get_data('inventario')[0]['stock_actual']==0,'sale dispatched');
 $return=['empresa_id'=>'1','form_token'=>'test','request_id'=>str_repeat('c',32),'venta_id'=>'1','fecha'=>'2026-10-02','motivo'=>'Retorno','cantidades'=>['4']]; process_return($return);$sale=get_data('ventas')[0];
 ok_rf(get_data('inventario')[0]['stock_actual']==4,'returned stock'); ok_rf(get_data('inventario')[0]['valor_inventario']==32,'original cost restored'); ok_rf($sale['costo_devoluciones_total']==32,'returned cost traced'); ok_rf(sale_return_credit($sale)==94.4,'credit at billed value');
 $hash=hash_file('sha256',DATA_PATH.'kardex.json');process_return($return);ok_rf(hash_file('sha256',DATA_PATH.'kardex.json')===$hash,'return retry idempotent');
 $tooMuch=$return;$tooMuch['request_id']=str_repeat('d',32);$tooMuch['cantidades']=['7'];$denied=false;try{process_return($tooMuch);}catch(InvalidArgumentException $e){$denied=true;}ok_rf($denied,'excess return rejected');
 $f=sale_financials($sale,[],[],'2026-10-11');ok_rf($f['saldo']==141.6&&$f['estado']==='Vencido','due and credit');
 $pay=['empresa_id'=>'1','form_token'=>'test','request_id'=>str_repeat('e',32),'venta_id'=>'1','fecha'=>'2026-10-03','importe'=>'100','medio'=>'Transferencia','referencia'=>'OP1'];process_payment($pay);$f=sale_financials(get_data('ventas')[0],null,null,'2026-10-03');ok_rf($f['saldo']==41.6&&$f['estado']==='Parcialmente pagado','partial payment');$count=count(get_data('pagos'));process_payment($pay);ok_rf(count(get_data('pagos'))===$count,'payment retry idempotent');
 process_void_sale(['empresa_id'=>'1','form_token'=>'test','request_id'=>str_repeat('f',32),'venta_id'=>'1','fecha'=>'2026-10-04','motivo'=>'Cancelada']);$f=sale_financials(get_data('ventas')[0]);ok_rf($f['saldo_favor']==100,'void keeps payment as credit');ok_rf(get_data('inventario')[0]['stock_actual']==4,'void does not invent stock return');
 echo "OK: $checks verificaciones de devoluciones y finanzas; solo datos temporales.\n";
}finally{foreach(glob(DATA_PATH.'*') as $file)unlink($file);if(is_file(DATA_PATH.'.write.lock'))unlink(DATA_PATH.'.write.lock');rmdir($temp);}
