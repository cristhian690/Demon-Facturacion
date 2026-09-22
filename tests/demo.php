<?php
$temp = sys_get_temp_dir() . '/demo-test-' . bin2hex(random_bytes(8));
mkdir($temp);
define('DATA_PATH', $temp . '/');
define('BASE_URL', '/');
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/demo.php';
$_SESSION = ['empresa_id'=>1, 'empresa_nombre'=>'Empresa 1', 'form_token'=>'test'];
$checks = 0;
function demo_check($condition, $label) {
    global $checks;
    if (!$condition) throw new RuntimeException($label);
    $checks++;
}
function demo_hashes() {
    $hashes = [];
    foreach (glob(DATA_PATH . '*.json') as $file) $hashes[basename($file)] = hash_file('sha256', $file);
    return $hashes;
}
try {
    file_put_contents(DATA_PATH . 'empresas.json', json_encode([['id'=>1,'razon_social'=>'Empresa 1'],['id'=>2,'razon_social'=>'Empresa 2']]));
    $legacy = ['id'=>1, 'nombre'=>'PRUEBA Cliente E1', 'otra_propiedad'=>'No asignar'];
    foreach (['clientes','proveedores','productos','almacenes'] as $table) file_put_contents(DATA_PATH . $table . '.json', json_encode([$legacy]));
    file_put_contents(DATA_PATH . 'inventario.json', json_encode([['producto_id'=>99,'almacen_id'=>99,'empresa_id'=>99,'stock_actual'=>10,'cpp'=>200,'valor_inventario'=>2000]]));
    $session = $_SESSION;
    $result = data_transaction('load_demo_data');
    demo_check(count($result['empresas'])===2 && $_SESSION===$session, 'Both companies loaded, session restored');
    foreach (['clientes','proveedores','productos','almacenes'] as $table) {
        demo_check(read_data($table)[0]===$legacy, 'Legacy preserved in ' . $table);
    }
    foreach ([1,2] as $company) {
        $_SESSION['empresa_id']=$company;
        foreach (['clientes'=>1,'proveedores'=>1,'almacenes'=>1,'productos'=>2,'compras'=>1,'inventario'=>2,'kardex'=>2] as $table=>$count) {
            $rows = get_data($table);
            demo_check(count($rows)===$count, 'Count ' . $table);
            foreach ($rows as $row) demo_check($row['empresa_id']===$company, 'Company isolation ' . $table);
        }
        $stock = get_data('inventario')[0];
        demo_check($stock['stock_actual']==10 && $stock['cpp']==100 && $stock['valor_inventario']==1000, 'Initial stock and CPP');
        $purchase = get_data('compras')[0];
        demo_check($purchase['subtotal']==1500 && $purchase['igv']==270 && $purchase['total']==1770, 'Purchase amounts exclude tax from stock cost');
        demo_check(get_data('kardex')[0]['entrada_valor']==1000, 'Opening ledger matches stock');
    }
    $_SESSION=$session;
    $before = demo_hashes();
    data_transaction('load_demo_data');
    demo_check(demo_hashes()===$before, 'Repeated load performs no writes');
    $product = get_data('productos')[0];
    demo_check($product['id']>99 && get_data('almacenes')[0]['id']>99, 'Orphan historical references are never assigned to demo records');
    $sale = ['empresa_id'=>'1','form_token'=>'test','request_id'=>str_repeat('f',32),
        'cliente_id'=>(string)get_data('clientes')[0]['id'], 'almacen_id'=>(string)get_data('almacenes')[0]['id'],
        'tipo_documento'=>'Factura','serie'=>'PRUEBA-VENTA','numero'=>'1','fecha'=>date('Y-m-d'),
        'productos'=>[(string)$product['id']], 'cantidades'=>['2'], 'precios'=>['150'], 'descuentos'=>['0']];
    data_transaction(function () use ($sale) { return process_operation(true, $sale); });
    $stock = get_data('inventario')[0];
    demo_check($stock['stock_actual']==8 && $stock['cpp']==100 && $stock['valor_inventario']==800, 'Example: stock 8, CPP 100, balance 800');
    $ledger = get_data('kardex'); $last = end($ledger);
    demo_check($last['salida_cantidad']==2 && $last['salida_valor']==200 && $last['saldo_valor']==800, 'Example: valued output 200');
    demo_check(get_data('ventas')[0]['total']==354, 'Sale: 300 plus 54 tax');
    $afterSale = demo_hashes();
    data_transaction('load_demo_data');
    demo_check(demo_hashes()===$afterSale, 'Reload after sale does not replenish stock');
    $_SESSION['empresa_id']=2;
    demo_check(count(get_data('ventas'))===0 && get_data('inventario')[0]['stock_actual']==10, 'Other company unaffected by sale');
    $denied = false;
    try { owned_record('productos', $product['id']); } catch (InvalidArgumentException $e) { $denied=true; }
    demo_check($denied, 'Foreign product cannot be selected');
    echo "OK: $checks verificaciones de carga PRUEBA, aislamiento y venta; solo archivos temporales.\n";
} finally {
    foreach (glob(DATA_PATH . '*') as $file) unlink($file);
    if (is_file(DATA_PATH . '.write.lock')) unlink(DATA_PATH . '.write.lock');
    rmdir($temp);
}
