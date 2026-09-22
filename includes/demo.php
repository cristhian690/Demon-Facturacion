<?php
require_once __DIR__ . '/operations.php';

// Called under data_transaction. Only records created by this loader are reused.
function demo_next_id($table) {
    $id = next_id($table);
    $foreignKey = ['clientes'=>'cliente_id', 'proveedores'=>'proveedor_id', 'almacenes'=>'almacen_id', 'productos'=>'producto_id'][$table];
    // Historical documents may reference a missing catalog record. Reserve those IDs
    // too, so a new PRUEBA record cannot accidentally acquire an old movement.
    foreach (['compras','ventas','inventario','kardex'] as $source) {
        foreach (read_data($source) as $row) {
            $id = max($id, (int)($row[$foreignKey] ?? 0) + 1);
            foreach ($row['detalles'] ?? [] as $detail) $id = max($id, (int)($detail[$foreignKey] ?? 0) + 1);
        }
    }
    return $id;
}

function demo_record($table, $key, $fields) {
    $rows = get_data($table);
    foreach ($rows as $row) if (($row['demo_key'] ?? '') === $key) return $row;
    $record = $fields + ['id' => demo_next_id($table), 'empresa_id' => (int)$_SESSION['empresa_id'], 'demo_key' => $key];
    $rows[] = $record;
    save_data($table, $rows);
    return $record;
}

function load_demo_data() {
    $session = $_SESSION;
    $summary = [];
    try {
        foreach (get_data('empresas') as $company) {
            $_SESSION['empresa_id'] = $company['id'];
            $_SESSION['empresa_nombre'] = $company['razon_social'];
            if (empty($_SESSION['form_token'])) $_SESSION['form_token'] = bin2hex(random_bytes(32));
            $suffix = ' E' . $company['id'];
            $contact = ['tipo_documento'=>'CE', 'direccion'=>'Dirección ficticia PRUEBA', 'telefono'=>'', 'correo'=>''];
            $client = demo_record('clientes', 'demo-v1-client', ['nombre'=>'PRUEBA Cliente' . $suffix, 'numero_documento'=>'PRUEBAC' . $company['id']] + $contact);
            $supplier = demo_record('proveedores', 'demo-v1-supplier', ['nombre'=>'PRUEBA Proveedor' . $suffix, 'numero_documento'=>'PRUEBAP' . $company['id']] + $contact);
            $warehouse = demo_record('almacenes', 'demo-v1-warehouse', ['nombre'=>'PRUEBA Almacén' . $suffix, 'ubicacion'=>'Ubicación ficticia PRUEBA', 'estado'=>'Activo']);
            $products = [];
            foreach ([1, 2] as $number) {
                $products[] = demo_record('productos', 'demo-v1-product-' . $number, [
                    'nombre'=>'PRUEBA Producto ' . $number . $suffix,
                    'sku'=>'PRUEBA-E' . $company['id'] . '-P' . $number,
                    'descripcion'=>'Producto ficticio para compras, ventas y Kardex.',
                    'categoria'=>'PRUEBA', 'marca'=>'PRUEBA', 'unidad_medida'=>'UN', 'stock_minimo'=>0, 'estado'=>'Activo'
                ]);
            }
            // A stable request ID prevents a second opening purchase, even after sales.
            $requestId = md5('demo-v1-opening-purchase-company-' . $company['id']);
            $purchase = process_operation(false, [
                'empresa_id'=>(string)$company['id'], 'form_token'=>$_SESSION['form_token'], 'request_id'=>$requestId,
                'proveedor_id'=>(string)$supplier['id'], 'almacen_id'=>(string)$warehouse['id'],
                'tipo_documento'=>'Factura', 'serie'=>'PRUEBA', 'numero'=>'INICIAL-E' . $company['id'],
                'fecha'=>date('Y-m-d'), 'productos'=>array_column($products, 'id'),
                'cantidades'=>['10','10'], 'costos'=>['100','50'], 'descuentos'=>['0','0']
            ]);
            $summary[] = ['empresa_id'=>$company['id'], 'empresa'=>$company['razon_social'],
                'cliente'=>$client['nombre'], 'almacen'=>$warehouse['nombre'], 'producto'=>$products[0]['nombre'],
                'compra_url'=>$purchase['redirect']];
        }
    } finally {
        // Loading data for other companies must not change the user's active context.
        $_SESSION = $session;
    }
    return ['message'=>'Datos PRUEBA disponibles. Repetir la carga no duplica registros ni repone stock vendido.', 'empresas'=>$summary];
}
