<?php
require_once __DIR__ . '/operations.php';
function filter_kardex($rows, $filters) {
    $sale = !empty($filters['venta_id']) ? owned_record('ventas', $filters['venta_id']) : null;
    $start = $filters['desde'] ?? ''; $end = $filters['hasta'] ?? '';
    $operation = $filters['tipo_operacion'] ?? '';
    if (!is_string($operation) || strlen($operation) > 60) throw new InvalidArgumentException('Tipo de operación inválido.');
    if (($start !== '' && !valid_date($start)) || ($end !== '' && !valid_date($end)) || ($start !== '' && $end !== '' && $start > $end)) throw new InvalidArgumentException('Revisa el rango de fechas.');
    foreach (['producto_id' => 'productos', 'almacen_id' => 'almacenes'] as $field => $table) {
        if (!empty($filters[$field])) owned_record($table, $filters[$field]);
    }
    $rows = array_values(array_filter($rows, function ($row) use ($filters, $start, $end, $sale, $operation) {
        $date = substr($row['fecha'], 0, 10);
        return belongs_to_company($row)
            && (!$sale || (isset($row['venta_id']) ? $row['venta_id'] == $sale['id'] : (($row['tipo_operacion'] ?? '') === 'VENTA' && ($row['documento'] ?? '') === $sale['tipo_documento'] . ' ' . $sale['serie'] . '-' . $sale['numero'] && ($row['almacen_id'] ?? null) == $sale['almacen_id'])))
            && (empty($filters['producto_id']) || $row['producto_id'] == $filters['producto_id'])
            && (empty($filters['almacen_id']) || $row['almacen_id'] == $filters['almacen_id'])
            && ($operation === '' || (string)($row['tipo_operacion'] ?? '') === $operation)
            && ($start === '' || $date >= $start) && ($end === '' || $date <= $end);
    }));
    usort($rows, function ($a, $b) { return strcmp($a['fecha'], $b['fecha']) ?: $a['id'] <=> $b['id']; });
    // Preserve stored historical balances and CPP; filtering never recalculates them.
    return $rows;
}
