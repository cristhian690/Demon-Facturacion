<?php
// Focused ETAPA 5 checks. Uses only synthetic files in a temporary directory.
$temp=sys_get_temp_dir().'/kardex-view-test-'.bin2hex(random_bytes(6)); mkdir($temp);
define('DATA_PATH',$temp.'/'); define('BASE_URL','/'); $_SESSION=['empresa_id'=>1,'empresa_nombre'=>'Empresa Uno'];
require __DIR__.'/../includes/helpers.php'; require __DIR__.'/../includes/kardex.php';
$checks=0;
function kv_check($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
function kv_fixture($name,$rows){file_put_contents(DATA_PATH.$name.'.json',json_encode($rows));}
try{
 kv_fixture('productos',[['id'=>1,'empresa_id'=>1,'nombre'=>'Producto A'],['id'=>2,'empresa_id'=>1,'nombre'=>'Producto B'],['id'=>3,'empresa_id'=>2,'nombre'=>'Producto ajeno']]);
 kv_fixture('almacenes',[['id'=>1,'empresa_id'=>1,'nombre'=>'Principal'],['id'=>2,'empresa_id'=>1,'nombre'=>'Secundario'],['id'=>3,'empresa_id'=>2,'nombre'=>'Ajeno']]);
 kv_fixture('ventas',[['id'=>10,'empresa_id'=>1,'tipo_documento'=>'Factura','serie'=>'F001','numero'=>'10','almacen_id'=>1],['id'=>20,'empresa_id'=>2,'tipo_documento'=>'Factura','serie'=>'F002','numero'=>'20','almacen_id'=>3]]);
 foreach(['clientes','proveedores','compras','inventario'] as $table)kv_fixture($table,[]);
 $rows=[
  ['id'=>3,'empresa_id'=>1,'producto_id'=>1,'almacen_id'=>1,'fecha'=>'2026-02-03','documento'=>'NC 1','tipo_operacion'=>'DEVOLUCION_VENTA','venta_id'=>10,'entrada_cantidad'=>1,'entrada_costo'=>8,'entrada_valor'=>8,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>-1,'saldo_valor'=>-8,'cpp'=>8],
  ['id'=>1,'empresa_id'=>1,'producto_id'=>1,'almacen_id'=>1,'fecha'=>'2026-02-01','documento'=>'Factura C-1','tipo_operacion'=>'COMPRA','entrada_cantidad'=>5,'entrada_costo'=>8,'entrada_valor'=>40,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>5,'saldo_valor'=>40,'cpp'=>8],
  ['id'=>2,'empresa_id'=>1,'producto_id'=>1,'almacen_id'=>1,'fecha'=>'2026-02-02 12:00:00','documento'=>'Factura F001-10','tipo_operacion'=>'VENTA','venta_id'=>10,'entrada_cantidad'=>0,'entrada_costo'=>0,'entrada_valor'=>0,'salida_cantidad'=>7,'salida_costo'=>8,'salida_valor'=>56,'saldo_cantidad'=>-2,'saldo_valor'=>-16,'cpp'=>8],
  ['id'=>4,'empresa_id'=>1,'producto_id'=>2,'almacen_id'=>2,'fecha'=>'2026-02-04','documento'=>'AJ-1','tipo_operacion'=>'AJUSTE','entrada_cantidad'=>2,'entrada_costo'=>3,'entrada_valor'=>6,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>2,'saldo_valor'=>6,'cpp'=>3],
  ['id'=>5,'empresa_id'=>2,'producto_id'=>3,'almacen_id'=>3,'fecha'=>'2026-02-01','documento'=>'OTRA','tipo_operacion'=>'COMPRA','entrada_cantidad'=>99,'entrada_costo'=>1,'entrada_valor'=>99,'salida_cantidad'=>0,'salida_costo'=>0,'salida_valor'=>0,'saldo_cantidad'=>99,'saldo_valor'=>99,'cpp'=>1]
 ];
 kv_fixture('kardex',$rows);
 $scoped=get_data('kardex'); kv_check(count($scoped)===4,'Company isolation');
 kv_check(count(filter_kardex($scoped,['producto_id'=>'1']))===3,'Product filter');
 kv_check(count(filter_kardex($scoped,['almacen_id'=>'2']))===1,'Warehouse filter');
 kv_check(count(filter_kardex($scoped,['desde'=>'2026-02-02','hasta'=>'2026-02-03']))===2,'Inclusive date filter');
 kv_check(count(filter_kardex($scoped,['venta_id'=>'10']))===2,'Sale relation filter');
 kv_check(count(filter_kardex($scoped,['tipo_operacion'=>'COMPRA']))===1,'Operation filter');
 $ordered=filter_kardex($scoped,[]); kv_check(array_column($ordered,'id')===[1,2,3,4],'Chronological order');
 $dateFiltered=filter_kardex($scoped,['desde'=>'2026-02-02','hasta'=>'2026-02-02']);
 kv_check($dateFiltered[0]['saldo_cantidad']===-2,'Stored historical balance preserved');
 kv_check($dateFiltered[0]['cpp']===8,'Stored historical CPP preserved');
 kv_check($ordered[1]['saldo_cantidad']===-2,'Negative balance displayed as stored');
 kv_check($ordered[0]['entrada_cantidad']===5&&$ordered[0]['salida_cantidad']===0,'Purchase is an entry');
 kv_check($ordered[1]['salida_cantidad']===7&&$ordered[1]['salida_costo']===8,'Sale is an output at historical cost');
 kv_check($ordered[2]['entrada_cantidad']===1&&$ordered[2]['entrada_costo']===8,'Return is an entry at historical cost');
 $before=file_get_contents(DATA_PATH.'kardex.json'); filter_kardex($scoped,['tipo_operacion'=>'VENTA']); kv_check(file_get_contents(DATA_PATH.'kardex.json')===$before,'Filtering never mutates Kardex');
 try{filter_kardex($scoped,['producto_id'=>'3']);throw new RuntimeException('Foreign filter accepted');}catch(InvalidArgumentException $e){kv_check(true,'Foreign product filter rejected');}
 echo "OK: $checks verificaciones de Kardex; solo datos temporales.\n";
}finally{foreach(glob(DATA_PATH.'*') as $file)unlink($file);rmdir($temp);}

