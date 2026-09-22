<?php
function quantity_step($unit) {
    return in_array(strtoupper(trim($unit)), ['UN','UND','UNI','UNID','UNIDAD','UNIDADES','PZA','PZ','PZAS','PIEZA','PIEZAS'], true) ? '1' : '0.001';
}
function quantity_value($value, $unit, $allowZero = false) {
    if (!is_scalar($value) || is_bool($value) || !preg_match('/^\d+(?:\.\d{1,3})?$/D', (string)$value)) throw new InvalidArgumentException('La cantidad debe ser un número positivo con hasta 3 decimales.');
    $number = (float)$value;
    if ($number > 100000000 || $number < ($allowZero ? 0 : (float)quantity_step($unit))) throw new InvalidArgumentException('Cantidad fuera del rango permitido.');
    if (quantity_step($unit) === '1' && floor($number) !== $number) throw new InvalidArgumentException('Las unidades y piezas requieren cantidades enteras.');
    return round($number, 3);
}
