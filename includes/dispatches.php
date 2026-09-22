<?php
require_once __DIR__ . '/operations.php';

function delivered_quantity($sale, $line) {
    // Historical sales already deducted all stock. Never interpret them as pending.
    if (!isset($sale['entrega'])) return (float)$line['cantidad'];
    return (float)($line['despachado'] ?? 0);
}
function dispatch_status($sale) {
    $billed = 0; $delivered = 0;
    foreach ($sale['detalles'] as $line) { $billed += $line['cantidad']; $delivered += delivered_quantity($sale, $line); }
    return round($billed - $delivered, 3) <= 0 ? 'Entregada' : ($delivered > 0 ? 'Entrega parcial' : 'Pendiente');
}
// action_response holds the shared write lock across this entire operation.
function process_dispatch($input) {
    validate_context($input);
    $sale = owned_record('ventas', $input['venta_id'] ?? '');
    $request = input_text($input, 'request_id', true);
    if (!preg_match('/^[a-f0-9]{32}$/D', $request)) throw new InvalidArgumentException('Identificador de formulario inválido.');
    $response = ['redirect'=>url('pages/ventas/detalle.php?id=' . $sale['id']), 'message'=>'Despacho registrado.'];
    foreach ($sale['despachos'] ?? [] as $dispatch) if ($dispatch['request_id'] === $request) return $response;
    if (dispatch_status($sale) === 'Entregada') throw new InvalidArgumentException('Esta venta ya fue entregada por completo.');
    $date = input_text($input, 'fecha', true);
    if (!valid_date($date) || $date < $sale['fecha']) throw new InvalidArgumentException('La fecha del despacho debe ser igual o posterior a la venta.');
    $warehouse = owned_record('almacenes', $sale['almacen_id']);
    if (($warehouse['estado'] ?? 'Activo') !== 'Activo') throw new InvalidArgumentException('El almacén está inactivo.');
    $quantities = $input['cantidades'] ?? null;
    if (!is_array($quantities) || array_keys($quantities) !== array_keys($sale['detalles'])) throw new InvalidArgumentException('Detalle de despacho incompleto.');
    $inventory = get_data('inventario'); $ledger = get_data('kardex');
    $required = []; $selected = [];
    foreach ($sale['detalles'] as $i => $line) {
        $product = owned_record('productos', $line['producto_id']);
        $qty = quantity_value($quantities[$i], $line['unidad_medida'] ?? $product['unidad_medida'] ?? 'UN', true);
        if (round($qty - ($line['cantidad'] - delivered_quantity($sale, $line)), 3) > 0) throw new InvalidArgumentException('La línea ' . ($i+1) . ' supera la cantidad pendiente.');
        if ($qty == 0) continue;
        if (($product['estado'] ?? 'Activo') !== 'Activo') throw new InvalidArgumentException('El producto está inactivo.');
        $selected[$i] = $qty;
        $required[$line['producto_id']] = round(($required[$line['producto_id']] ?? 0) + $qty, 3);
    }
    if (!$selected) throw new InvalidArgumentException('Ingresa al menos una cantidad a despachar.');
    $indices = [];
    foreach ($required as $productId => $qty) {
        $matches = [];
        foreach ($inventory as $i => $row) if ($row['producto_id'] == $productId && $row['almacen_id'] == $warehouse['id']) $matches[] = $i;
        if (count($matches) !== 1 || round($qty - $inventory[$matches[0]]['stock_actual'], 3) > 0) throw new InvalidArgumentException('Stock insuficiente o inventario inconsistente para el producto ' . $productId . '.');
        $indices[$productId] = $matches[0];
        foreach ($ledger as $movement) if ($movement['producto_id'] == $productId && $movement['almacen_id'] == $warehouse['id'] && substr($movement['fecha'],0,10) > $date) throw new InvalidArgumentException('La fecha es anterior al último movimiento del producto y almacén.');
    }
    $ledgerId = next_id('kardex'); $details = [];
    foreach ($selected as $i => $qty) {
        $line = $sale['detalles'][$i]; $index = $indices[$line['producto_id']];
        $previous = $inventory[$index]; $cost = $previous['cpp']; $value = $qty * $cost;
        $stock = round($previous['stock_actual'] - $qty, 3);
        $inventory[$index]['stock_actual'] = $stock;
        $inventory[$index]['valor_inventario'] = $stock * $cost;
        $sale['detalles'][$i]['despachado'] = round(delivered_quantity($sale, $line) + $qty, 3);
        $sale['detalles'][$i]['costo_venta_total'] = ($line['costo_venta_total'] ?? 0) + $value;
        $sale['detalles'][$i]['costo_venta_unitario'] = $sale['detalles'][$i]['costo_venta_total'] / $sale['detalles'][$i]['despachado'];
        $sale['costo_ventas_total'] = ($sale['costo_ventas_total'] ?? 0) + $value;
        $details[] = ['linea'=>$i, 'producto_id'=>$line['producto_id'], 'cantidad'=>$qty, 'costo_unitario'=>$cost, 'costo_total'=>$value];
        $ledger[] = ['id'=>$ledgerId++, 'venta_id'=>$sale['id'], 'linea'=>$i, 'request_id'=>$request, 'producto_id'=>$line['producto_id'], 'almacen_id'=>$warehouse['id'], 'empresa_id'=>$sale['empresa_id'], 'fecha'=>$date, 'documento'=>$sale['tipo_documento'].' '.$sale['serie'].'-'.$sale['numero'], 'tipo_operacion'=>'DESPACHO', 'entrada_cantidad'=>0, 'entrada_costo'=>0, 'entrada_valor'=>0, 'salida_cantidad'=>$qty, 'salida_costo'=>$cost, 'salida_valor'=>$value, 'saldo_cantidad'=>$stock, 'saldo_valor'=>$stock * $cost, 'cpp'=>$cost];
    }
    $sale['despachos'][] = ['request_id'=>$request, 'fecha'=>$date, 'detalles'=>$details, 'origen'=>'Entrega parcial'];
    $sales = get_data('ventas');
    foreach ($sales as $i => $row) if ($row['id'] == $sale['id']) { $sales[$i] = $sale; break; }
    save_batch(['ventas'=>$sales, 'inventario'=>$inventory, 'kardex'=>$ledger]);
    return $response;
}
