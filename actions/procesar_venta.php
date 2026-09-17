<?php
require_once '../config.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa_id = $_SESSION['empresa_id'];
    
    // Leer JSONs
    $ventas = get_data('ventas');
    $inventario = get_data('inventario');
    $kardex = get_data('kardex');
    
    // 1. Armar objeto Venta
    $max_id = 0;
    foreach ($ventas as $v) { if ($v['id'] > $max_id) $max_id = $v['id']; }
    $venta_id = $max_id + 1;
    
    $venta = [
        'id' => $venta_id,
        'empresa_id' => $empresa_id,
        'cliente_id' => (int)$_POST['cliente_id'],
        'tipo_documento' => $_POST['tipo_documento'],
        'serie' => $_POST['serie'],
        'numero' => $_POST['numero'],
        'fecha' => $_POST['fecha'],
        'almacen_id' => (int)$_POST['almacen_id'],
        'subtotal' => (float)$_POST['res_subtotal'],
        'igv' => (float)$_POST['res_igv'],
        'total' => (float)$_POST['res_total'],
        'costo_ventas_total' => 0,
        'detalles' => []
    ];
    
    // Array para pasar a la vista de detalle
    $impacto_simulacion = [];
    $costo_ventas_total = 0;
    
    // Procesar detalles
    $productos = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios = $_POST['precios'];
    $descuentos = $_POST['descuentos'];
    
    for ($i = 0; $i < count($productos); $i++) {
        $prod_id = (int)$productos[$i];
        $cant = (int)$cantidades[$i];
        $precio = (float)$precios[$i];
        $dscto = (float)$descuentos[$i];
        
        $bruto = $cant * $precio;
        $subtotal = $bruto - ($bruto * ($dscto / 100));
        
        // 2. Leer Inventario (CPP vigente)
        $inv_index = -1;
        $stock_anterior = 0;
        $cpp_vigente = 0;
        
        foreach ($inventario as $key => $inv) {
            if ($inv['producto_id'] == $prod_id && $inv['almacen_id'] == $venta['almacen_id'] && $inv['empresa_id'] == $empresa_id) {
                $inv_index = $key;
                $stock_anterior = $inv['stock_actual'];
                $cpp_vigente = $inv['cpp'];
                break;
            }
        }
        
        // Si no hay stock o no existe, en el prototipo permitiremos que falle silenciosamente a negativo para demostrar el flujo,
        // pero lo ideal es validar. El formulario JS ya restringe enviar si stock es < cant.
        
        $costo_venta_linea = $cant * $cpp_vigente;
        $costo_ventas_total += $costo_venta_linea;
        
        $nuevo_stock = $stock_anterior - $cant;
        $nuevo_valor = $nuevo_stock * $cpp_vigente;
        
        // Actualizar inventario
        if ($inv_index >= 0) {
            $inventario[$inv_index]['stock_actual'] = $nuevo_stock;
            $inventario[$inv_index]['valor_inventario'] = $nuevo_valor;
            // El CPP NO CAMBIA en una venta
        }
        
        // Añadir a detalle de venta
        $venta['detalles'][] = [
            'producto_id' => $prod_id,
            'cantidad' => $cant,
            'precio_unitario' => $precio,
            'descuento' => $dscto,
            'subtotal' => $subtotal,
            'costo_venta_unitario' => $cpp_vigente,
            'costo_venta_total' => $costo_venta_linea
        ];
        
        // Guardar para la vista de resumen
        $impacto_simulacion[] = [
            'producto_id' => $prod_id,
            'stock_anterior' => $stock_anterior,
            'cpp_vigente' => $cpp_vigente,
            'cantidad_vendida' => $cant,
            'precio_venta' => $precio,
            'nuevo_stock' => $nuevo_stock,
            'costo_venta_linea' => $costo_venta_linea
        ];
        
        // 3. Actualizar Kardex
        $max_kardex_id = 0;
        foreach ($kardex as $k) { if ($k['id'] > $max_kardex_id) $max_kardex_id = $k['id']; }
        
        $kardex[] = [
            'id' => $max_kardex_id + 1,
            'producto_id' => $prod_id,
            'almacen_id' => $venta['almacen_id'],
            'empresa_id' => $empresa_id,
            'fecha' => $venta['fecha'],
            'documento' => $venta['tipo_documento'] . ' ' . $venta['serie'] . '-' . $venta['numero'],
            'tipo_operacion' => 'VENTA',
            'entrada_cantidad' => 0,
            'entrada_costo' => 0,
            'entrada_valor' => 0,
            'salida_cantidad' => $cant,
            'salida_costo' => $cpp_vigente, // La salida se costea al CPP vigente
            'salida_valor' => $costo_venta_linea,
            'saldo_cantidad' => $nuevo_stock,
            'saldo_valor' => $nuevo_valor,
            'cpp' => $cpp_vigente // Se mantiene el mismo
        ];
    }
    
    $venta['costo_ventas_total'] = $costo_ventas_total;
    $venta['impacto_simulacion'] = $impacto_simulacion;
    
    $ventas[] = $venta;
    
    // 4. Guardar todo
    save_data('ventas', $ventas);
    save_data('inventario', $inventario);
    save_data('kardex', $kardex);
    
    // Redirigir al detalle interactivo de la venta
    header("Location: " . BASE_URL . "pages/ventas/detalle.php?id=" . $venta_id);
    exit;
}
