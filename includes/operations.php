<?php
require_once __DIR__ . '/quantities.php';
function operation_number($value, $label, $min, $max, $integer = false) {
    if (!is_scalar($value) || !is_numeric($value)) throw new InvalidArgumentException('Valor inválido: ' . $label);
    $number = (float)$value;
    if (!is_finite($number) || $number < $min || $number > $max || ($integer && floor($number) !== $number)) throw new InvalidArgumentException('Valor inválido: ' . $label);
    return $integer ? (int)$number : $number;
}
function valid_date($date) {
    if (!is_string($date)) return false;
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
function sale_document_series($type) {
    return match ($type) { 'Factura' => 'F001', 'Boleta' => 'B001', default => throw new InvalidArgumentException('Tipo de documento inválido.') };
}
function next_sale_document_number($documents, $type) {
    $series = sale_document_series($type); $maximum = 0;
    foreach ($documents as $document) {
        if (!belongs_to_company($document) || ($document['tipo_documento'] ?? '') !== $type || ($document['serie'] ?? '') !== $series) continue;
        $number = (string)($document['numero'] ?? '');
        if (ctype_digit($number)) $maximum = max($maximum, (int)$number);
    }
    return str_pad((string)($maximum + 1), 6, '0', STR_PAD_LEFT);
}
function process_operation($sale, $input) {
    validate_context($input);
    $table = $sale ? 'ventas' : 'compras';
    $delivery = $sale ? ($input['entrega'] ?? 'inmediata') : 'inmediata';
    if (!in_array($delivery, ['inmediata','pendiente'], true)) throw new InvalidArgumentException('Tipo de entrega inválido.');
    $party = $sale ? 'cliente_id' : 'proveedor_id';
    $request = input_text($input, 'request_id', true);
    if (!preg_match('/^[a-f0-9]{32}$/', $request)) throw new InvalidArgumentException('Identificador de formulario inválido.');
    $documents = get_data($table);
    foreach ($documents as $doc) if (($doc['request_id'] ?? '') === $request) return ['redirect' => url('pages/' . $table . '/detalle.php?id=' . $doc['id'])];
    $partyRecord = owned_record($sale ? 'clientes' : 'proveedores', $input[$party] ?? '');
    if (($partyRecord['estado'] ?? 'Activo') !== 'Activo') throw new InvalidArgumentException(($sale ? 'El cliente' : 'El proveedor') . ' seleccionado está inactivo.');
    $warehouse = owned_record('almacenes', $input['almacen_id'] ?? '');
    if (($warehouse['estado'] ?? 'Activo') !== 'Activo') throw new InvalidArgumentException('El almacén "' . ($warehouse['nombre'] ?? '') . '" está inactivo. Selecciona otro almacén.');
    $doc = ['id' => next_id($table), 'empresa_id' => (int)$_SESSION['empresa_id'], $party => (int)$input[$party], 'almacen_id' => $warehouse['id'], 'request_id' => $request, 'detalles' => []];
    foreach (['tipo_documento','fecha'] as $key) $doc[$key] = input_text($input, $key, true);
    if ($sale) {
        $doc['serie'] = sale_document_series($doc['tipo_documento']);
        $doc['numero'] = next_sale_document_number($documents, $doc['tipo_documento']);
        $doc['fecha_emision'] = $doc['fecha'];
        $doc['hora_emision'] = date('H:i:s');
    } else {
        foreach (['serie','numero'] as $key) $doc[$key] = input_text($input, $key, true);
    }
    if (!valid_date($doc['fecha'])) throw new InvalidArgumentException('Fecha inválida.');
    if (!in_array($doc['tipo_documento'], ['Factura','Boleta'], true)) throw new InvalidArgumentException('Tipo de documento inválido.');
    if (!preg_match('/^[A-Z0-9-]{1,10}$/D', $doc['serie'])) throw new InvalidArgumentException('La serie contiene caracteres inválidos.');
    if (!preg_match('/^[0-9]{1,12}$/D', $doc['numero'])) throw new InvalidArgumentException('El número del documento debe contener solo dígitos.');
    if ($sale) {
        $doc['tipo_operacion'] = input_text($input + ['tipo_operacion'=>'Venta interna'], 'tipo_operacion', true);
        if (!in_array($doc['tipo_operacion'], ['Venta interna','Venta para exportación'], true)) throw new InvalidArgumentException('Tipo de operación inválido.');
        $doc['moneda'] = input_text($input + ['moneda'=>'PEN'], 'moneda', true);
        if (!in_array($doc['moneda'], ['PEN','USD'], true)) throw new InvalidArgumentException('Moneda inválida.');
        $doc['tipo_cambio'] = $doc['moneda'] === 'PEN' ? 1.0 : operation_number($input['tipo_cambio'] ?? null, 'tipo de cambio', 0.001, 1000);
        $doc['orden_compra'] = input_text($input, 'orden_compra');
        $doc['observacion'] = input_text($input, 'observacion');
        $doc['vendedor'] = $_SESSION['usuario']['nombre'] ?? 'Administrador';
    }
    foreach ($documents as $old) {
        if ($old['tipo_documento'] === $doc['tipo_documento'] && $old['serie'] === $doc['serie'] && $old['numero'] === $doc['numero'] && ($sale || $old[$party] == $doc[$party])) throw new InvalidArgumentException('El documento ' . $doc['serie'] . '-' . $doc['numero'] . ' ya está registrado.');
    }
    $priceField = $sale ? 'precios' : 'costos';
    $products = $input['productos'] ?? null;
    if (!is_array($products) || !count($products) || count($products) > 500 || array_keys($products) !== range(0, count($products)-1)) throw new InvalidArgumentException('Agrega al menos un producto válido.');
    foreach (['cantidades', $priceField, 'descuentos'] as $key) if (!is_array($input[$key] ?? null) || array_keys($input[$key]) !== array_keys($products)) throw new InvalidArgumentException('El detalle está incompleto.');
    $inventory = get_data('inventario');
    $ledger = get_data('kardex');
    $required = []; $requiredNames = []; $lines = []; $subtotal = 0;
    foreach ($products as $i => $id) {
        $product = owned_record('productos', $id);
        if (($product['estado'] ?? 'Activo') !== 'Activo') throw new InvalidArgumentException('El producto "' . ($product['nombre'] ?? $product['id']) . '" está inactivo.');
        $qty = quantity_value($input['cantidades'][$i], $product['unidad_medida'] ?? 'UN');
        $price = operation_number($input[$priceField][$i], 'precio/costo', 0.01, 100000000);
        $discount = operation_number($input['descuentos'][$i], 'descuento', 0, 100);
        $amount = round($qty * $price * (1 - $discount / 100), 2);
        $lines[] = ['producto_id' => $product['id'], 'unidad_medida'=>$product['unidad_medida'] ?? 'UN', 'cantidad' => $qty, 'precio' => $price, 'descuento' => $discount, 'subtotal' => $amount];
        $required[$product['id']] = ($required[$product['id']] ?? 0) + $qty;
        $requiredNames[$product['id']] = $product['nombre'] ?? ('Producto '.$product['id']);
        $subtotal += $amount;
    }
    // Aggregate repeated products before changing any stock or ledger entry.
    foreach ($required as $productId => $qty) {
        $stock = 0; $matches = 0;
        foreach ($inventory as $inv) if ($inv['producto_id'] == $productId && $inv['almacen_id'] == $warehouse['id']) { $stock = $inv['stock_actual']; $matches++; }
        if ($matches > 1) throw new InvalidArgumentException('El inventario tiene registros duplicados para este producto y almacén. Revisa los datos antes de operar.');
        if ($sale && $delivery === 'inmediata' && round($qty - $stock, 3) > 0) throw new InvalidArgumentException('Stock insuficiente para "' . $requiredNames[$productId] . '": disponible ' . $stock . ', solicitado ' . $qty . '.');
        // Historical snapshots cannot be recalculated safely by inserting backdated entries.
        if ($delivery === 'inmediata') foreach ($ledger as $movement) if ($movement['producto_id'] == $productId && $movement['almacen_id'] == $warehouse['id'] && substr($movement['fecha'], 0, 10) > $doc['fecha']) throw new InvalidArgumentException('La fecha es anterior al último movimiento de este producto y almacén.');
    }
    $ledgerId = next_id('kardex'); $impact = []; $totalCost = 0;
    foreach ($lines as $line) {
        if ($sale && $delivery === 'pendiente') {
            $doc['detalles'][] = ['producto_id'=>$line['producto_id'], 'unidad_medida'=>$line['unidad_medida'], 'cantidad'=>$line['cantidad'], 'precio_unitario'=>$line['precio'], 'subtotal'=>$line['subtotal'], 'descuento'=>$line['descuento'], 'despachado'=>0, 'costo_venta_unitario'=>0, 'costo_venta_total'=>0];
            continue;
        }
        $index = null;
        foreach ($inventory as $key => $inv) if ($inv['producto_id'] == $line['producto_id'] && $inv['almacen_id'] == $warehouse['id']) { $index = $key; break; }
        if ($index === null) {
            $inventory[] = ['producto_id' => $line['producto_id'], 'almacen_id' => $warehouse['id'], 'empresa_id' => $doc['empresa_id'], 'stock_actual' => 0, 'cpp' => 0, 'valor_inventario' => 0];
            $index = count($inventory)-1;
        }
        $previous = $inventory[$index]; $qty = $line['cantidad'];
        $cost = $sale ? $previous['cpp'] : $line['subtotal'] / $qty;
        $value = $sale ? $qty * $cost : $line['subtotal'];
        $stock = round($previous['stock_actual'] + ($sale ? -$qty : $qty), 3);
        $newValue = $sale ? $stock * $cost : $previous['valor_inventario'] + $value;
        $cpp = $sale ? $cost : ($stock > 0 ? $newValue / $stock : 0);
        $inventory[$index] = array_merge($previous, ['stock_actual' => $stock, 'valor_inventario' => $newValue, 'cpp' => $cpp]);
        $detail = ['producto_id' => $line['producto_id'], 'unidad_medida'=>$line['unidad_medida'], 'cantidad' => $qty, 'subtotal' => $line['subtotal'], 'descuento' => $line['descuento']];
        if ($sale) $detail['despachado'] = $qty;
        if ($sale) $detail += ['precio_unitario' => $line['precio'], 'costo_venta_unitario' => $cost, 'costo_venta_total' => $value];
        else $detail += ['costo_unitario' => $cost];
        $doc['detalles'][] = $detail;
        $impact[] = ['producto_id' => $line['producto_id'], 'stock_anterior' => $previous['stock_actual'], 'cpp_anterior' => $previous['cpp'], 'cpp_vigente' => $previous['cpp'], 'cantidad_vendida' => $qty, 'cantidad_comprada' => $qty, 'precio_venta' => $line['precio'], 'costo_compra' => $cost, 'nuevo_stock' => $stock, 'nuevo_cpp' => $cpp, 'costo_venta_linea' => $value];
        $totalCost += $sale ? $value : 0;
        $ledger[] = ['id' => $ledgerId++, 'producto_id' => $line['producto_id'], 'almacen_id' => $warehouse['id'], 'empresa_id' => $doc['empresa_id'], 'fecha' => $doc['fecha'], 'documento' => $doc['tipo_documento'] . ' ' . $doc['serie'] . '-' . $doc['numero'], 'tipo_operacion' => $sale ? 'VENTA' : 'COMPRA', 'entrada_cantidad' => $sale ? 0 : $qty, 'entrada_costo' => $sale ? 0 : $cost, 'entrada_valor' => $sale ? 0 : $value, 'salida_cantidad' => $sale ? $qty : 0, 'salida_costo' => $sale ? $cost : 0, 'salida_valor' => $sale ? $value : 0, 'saldo_cantidad' => $stock, 'saldo_valor' => $newValue, 'cpp' => $cpp];
    }
    $doc['subtotal'] = round($subtotal, 2); $doc['igv'] = round($subtotal * 0.18, 2); $doc['total'] = $doc['subtotal'] + $doc['igv'];
    $doc['impacto_simulacion'] = $impact;
    if ($sale) $doc['costo_ventas_total'] = $totalCost;
    if ($sale) {
        $doc['entrega'] = $delivery;
        $doc['despachos'] = [];
        $paymentType = $input['condicion_pago'] ?? 'contado';
        if (!in_array($paymentType, ['contado','credito'], true)) throw new InvalidArgumentException('Condición de pago inválida.');
        $doc['condicion_pago'] = $paymentType;
        $doc['fecha_vencimiento'] = $paymentType === 'credito' ? input_text($input, 'fecha_vencimiento', true) : '';
        if ($paymentType === 'credito' && (!valid_date($doc['fecha_vencimiento']) || $doc['fecha_vencimiento'] < $doc['fecha'])) throw new InvalidArgumentException('El vencimiento debe ser igual o posterior a la fecha de venta.');
        $doc['estado_documento'] = 'Vigente';
        $doc['devoluciones'] = [];
        if ($delivery === 'inmediata') {
            $dispatchLines = [];
            foreach ($doc['detalles'] as $i => $detail) $dispatchLines[] = ['linea'=>$i, 'producto_id'=>$detail['producto_id'], 'cantidad'=>$detail['cantidad'], 'costo_unitario'=>$detail['costo_venta_unitario'], 'costo_total'=>$detail['costo_venta_total']];
            $doc['despachos'][] = ['request_id'=>$request, 'fecha'=>$doc['fecha'], 'detalles'=>$dispatchLines, 'origen'=>'Confirmación de venta'];
            // The last N entries belong to this sale; link them without changing costs.
            for ($i = count($ledger) - count($lines); $i < count($ledger); $i++) $ledger[$i]['venta_id'] = $doc['id'];
        }
    }
    $payments = null;
    if ($sale && $doc['condicion_pago'] === 'contado') {
        $doc['pago_contado_automatico'] = true;
        $payments = get_data('pagos');
        $payments[] = [
            'id'=>next_id('pagos'),
            'empresa_id'=>$doc['empresa_id'],
            'venta_id'=>$doc['id'],
            'request_id'=>$request,
            'fecha'=>$doc['fecha'],
            'importe'=>$doc['total'],
            'medio'=>'Contado',
            'referencia'=>'Pago automático al confirmar'
        ];
    }
    $documents[] = $doc;
    if ($sale && $delivery === 'pendiente') {
        $batch = [$table=>$documents];
        if ($payments !== null) $batch['pagos']=$payments;
        save_batch($batch);
    } else {
        $batch = [$table=>$documents, 'inventario'=>$inventory, 'kardex'=>$ledger];
        if ($payments !== null) $batch['pagos']=$payments;
        save_batch($batch);
    }
    return ['redirect' => url('pages/' . $table . '/detalle.php?id=' . $doc['id'])];
}
