<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa_id = $_SESSION['empresa_id'];
    
    // Leer JSONs
    $compras = get_data('compras');
    $inventario = get_data('inventario');
    $kardex = get_data('kardex');
    
    // 1. Armar objeto Compra
    $max_id = 0;
    foreach ($compras as $c) { if ($c['id'] > $max_id) $max_id = $c['id']; }
    $compra_id = $max_id + 1;
    
    $compra = [
        'id' => $compra_id,
        'empresa_id' => $empresa_id,
        'proveedor_id' => (int)$_POST['proveedor_id'],
        'tipo_documento' => $_POST['tipo_documento'],
        'serie' => $_POST['serie'],
        'numero' => $_POST['numero'],
        'fecha' => $_POST['fecha'],
        'almacen_id' => (int)$_POST['almacen_id'],
        'subtotal' => (float)$_POST['res_subtotal'],
        'igv' => (float)$_POST['res_igv'],
        'total' => (float)$_POST['res_total'],
        'detalles' => []
    ];
    
    // Array para pasar a la vista de detalle
    $impacto_cpp = [];
    
    // Procesar detalles
    $productos = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $costos = $_POST['costos'];
    
    for ($i = 0; $i < count($productos); $i++) {
        $prod_id = (int)$productos[$i];
        $cant = (int)$cantidades[$i];
        $costo = (float)$costos[$i];
        
        // Añadir a detalle de compra
        $compra['detalles'][] = [
            'producto_id' => $prod_id,
            'cantidad' => $cant,
            'costo_unitario' => $costo,
            'subtotal' => $cant * $costo
        ];
        
        // 2. Actualizar Inventario (Stock y CPP)
        $inv_index = -1;
        $stock_anterior = 0;
        $cpp_anterior = 0;
        $valor_anterior = 0;
        
        foreach ($inventario as $key => $inv) {
            if ($inv['producto_id'] == $prod_id && $inv['almacen_id'] == $compra['almacen_id'] && $inv['empresa_id'] == $empresa_id) {
                $inv_index = $key;
                $stock_anterior = $inv['stock_actual'];
                $cpp_anterior = $inv['cpp'];
                $valor_anterior = $inv['valor_inventario'];
                break;
            }
        }
        
        $valor_compra = $cant * $costo;
        $nuevo_stock = $stock_anterior + $cant;
        $nuevo_valor = $valor_anterior + $valor_compra;
        $nuevo_cpp = $nuevo_stock > 0 ? ($nuevo_valor / $nuevo_stock) : 0;
        
        if ($inv_index >= 0) {
            $inventario[$inv_index]['stock_actual'] = $nuevo_stock;
            $inventario[$inv_index]['cpp'] = $nuevo_cpp;
            $inventario[$inv_index]['valor_inventario'] = $nuevo_valor;
        } else {
            $inventario[] = [
                'producto_id' => $prod_id,
                'almacen_id' => $compra['almacen_id'],
                'empresa_id' => $empresa_id,
                'stock_actual' => $nuevo_stock,
                'cpp' => $nuevo_cpp,
                'valor_inventario' => $nuevo_valor
            ];
        }
        
        // Guardar para la vista de resumen
        $impacto_cpp[] = [
            'producto_id' => $prod_id,
            'stock_anterior' => $stock_anterior,
            'cpp_anterior' => $cpp_anterior,
            'cantidad_comprada' => $cant,
            'costo_compra' => $costo,
            'nuevo_stock' => $nuevo_stock,
            'nuevo_cpp' => $nuevo_cpp
        ];
        
        // 3. Actualizar Kardex
        $max_kardex_id = 0;
        foreach ($kardex as $k) { if ($k['id'] > $max_kardex_id) $max_kardex_id = $k['id']; }
        
        $kardex[] = [
            'id' => $max_kardex_id + 1,
            'producto_id' => $prod_id,
            'almacen_id' => $compra['almacen_id'],
            'empresa_id' => $empresa_id,
            'fecha' => $compra['fecha'],
            'documento' => $compra['tipo_documento'] . ' ' . $compra['serie'] . '-' . $compra['numero'],
            'tipo_operacion' => 'COMPRA',
            'entrada_cantidad' => $cant,
            'entrada_costo' => $costo,
            'entrada_valor' => $valor_compra,
            'salida_cantidad' => 0,
            'salida_costo' => 0,
            'salida_valor' => 0,
            'saldo_cantidad' => $nuevo_stock,
            'saldo_valor' => $nuevo_valor,
            'cpp' => $nuevo_cpp
        ];
    }
    
    $compra['impacto_simulacion'] = $impacto_cpp; // Guardamos el snapshot para el diagrama didáctico
    
    $compras[] = $compra;
    
    // 4. Guardar todo
    save_data('compras', $compras);
    save_data('inventario', $inventario);
    save_data('kardex', $kardex);
    
    // Redirigir al detalle interactivo de la compra
    header("Location: " . BASE_URL . "pages/compras/detalle.php?id=" . $compra_id);
    exit;
}
