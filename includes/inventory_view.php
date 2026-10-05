<?php
function inventory_row_status($stock, $minimum) {
    $stock=(float)$stock; $minimum=(float)$minimum;
    if ($stock < 0) return ['key'=>'inconsistente','label'=>'Revisar','class'=>'danger'];
    if ($stock == 0) return ['key'=>'sin_stock','label'=>'Sin stock','class'=>'secondary'];
    if ($stock <= $minimum) return ['key'=>'bajo','label'=>'Stock bajo','class'=>'warning'];
    return ['key'=>'disponible','label'=>'Disponible','class'=>'success'];
}
function inventory_view_rows($inventory, $products, $warehouses, $filters=[]) {
    $productMap=array_column($products,null,'id'); $warehouseMap=array_column($warehouses,null,'id'); $result=[];
    $query=mb_strtolower(trim((string)($filters['q']??''))); $productId=(string)($filters['producto_id']??''); $warehouseId=(string)($filters['almacen_id']??''); $state=(string)($filters['estado']??'');
    foreach ($inventory as $row) {
        $product=$productMap[$row['producto_id']]??null; $warehouse=$warehouseMap[$row['almacen_id']]??null;
        $status=inventory_row_status($row['stock_actual']??0,$product['stock_minimo']??0);
        if($query!=='' && mb_strpos(mb_strtolower(($product['sku']??'#'.$row['producto_id']).' '.($product['nombre']??'Producto no disponible')),$query)===false)continue;
        if($productId!=='' && (string)$row['producto_id']!==$productId)continue;
        if($warehouseId!=='' && (string)$row['almacen_id']!==$warehouseId)continue;
        if($state!=='' && $status['key']!==$state)continue;
        $row['_product']=$product; $row['_warehouse']=$warehouse; $row['_status']=$status; $result[]=$row;
    }
    usort($result,fn($a,$b)=>strcmp($a['_product']['nombre']??'', $b['_product']['nombre']??'') ?: strcmp($a['_warehouse']['nombre']??'', $b['_warehouse']['nombre']??''));
    return $result;
}
function inventory_company_summary($inventory, $products) {
    $totals=[]; $value=0.0;
    foreach($inventory as $row){$totals[$row['producto_id']]=($totals[$row['producto_id']]??0)+(float)($row['stock_actual']??0);$value+=(float)($row['valor_inventario']??0);}
    $summary=['disponible'=>0,'bajo'=>0,'sin_stock'=>0,'inconsistente'=>0,'valor'=>$value];
    foreach($products as $product){if(($product['estado']??'Activo')!=='Activo')continue;$status=inventory_row_status($totals[$product['id']]??0,$product['stock_minimo']??0);$summary[$status['key']]++;}
    return $summary;
}
