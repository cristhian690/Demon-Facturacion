<?php
function invoice_owner_company($sale, $companies) {
    foreach ($companies as $company) if ((string)$company['id'] === (string)($sale['empresa_id'] ?? '')) return $company;
    throw new InvalidArgumentException('La empresa propietaria del comprobante no está disponible.');
}
function invoice_delivery_summary($sale) {
    $requested=0; $delivered=0;
    foreach ($sale['detalles'] ?? [] as $line) {
        $requested+=(float)($line['cantidad']??0);
        $delivered+=isset($sale['entrega'])?(float)($line['despachado']??0):(float)($line['cantidad']??0);
    }
    $pending=max(0,round($requested-$delivered,3));
    $status=$pending<=0?'Entrega completa':($delivered>0?'Entrega parcial':'Entrega pendiente');
    return ['tipo'=>($sale['entrega']??'inmediata')==='pendiente'?'Entrega pendiente':'Entrega inmediata','estado'=>$status,'solicitado'=>$requested,'entregado'=>$delivered,'pendiente'=>$pending];
}
function invoice_product_rows($sale, $products) {
    $byId=array_column($products,null,'id'); $rows=[];
    foreach ($sale['detalles']??[] as $line) {
        $product=$byId[$line['producto_id']]??['sku'=>'N/D','nombre'=>'Producto no disponible'];
        $rows[]=['codigo'=>$product['sku']??'N/D','descripcion'=>$product['nombre']??'Producto no disponible','unidad'=>$line['unidad_medida']??$product['unidad_medida']??'UN','cantidad'=>$line['cantidad']??0,'precio'=>$line['precio_unitario']??0,'descuento'=>$line['descuento']??0,'importe'=>$line['subtotal']??0];
    }
    return $rows;
}
function invoice_quantity($value) { return rtrim(rtrim(number_format((float)$value,3,'.',','),'0'),'.'); }
function invoice_integer_words($number) {
    $number=(int)$number;
    $units=['CERO','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE','DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE','VEINTE','VEINTIUNO','VEINTIDÓS','VEINTITRÉS','VEINTICUATRO','VEINTICINCO','VEINTISÉIS','VEINTISIETE','VEINTIOCHO','VEINTINUEVE'];
    if($number<30)return $units[$number];
    if($number<100){$tens=[3=>'TREINTA',4=>'CUARENTA',5=>'CINCUENTA',6=>'SESENTA',7=>'SETENTA',8=>'OCHENTA',9=>'NOVENTA'];$base=$tens[intdiv($number,10)];return $number%10?$base.' Y '.$units[$number%10]:$base;}
    if($number===100)return 'CIEN';
    if($number<1000){$hundreds=[1=>'CIENTO',2=>'DOSCIENTOS',3=>'TRESCIENTOS',4=>'CUATROCIENTOS',5=>'QUINIENTOS',6=>'SEISCIENTOS',7=>'SETECIENTOS',8=>'OCHOCIENTOS',9=>'NOVECIENTOS'];$base=$hundreds[intdiv($number,100)];return $number%100?$base.' '.invoice_integer_words($number%100):$base;}
    if($number<1000000){$thousands=intdiv($number,1000);$base=$thousands===1?'MIL':invoice_integer_words($thousands).' MIL';return $number%1000?$base.' '.invoice_integer_words($number%1000):$base;}
    if($number<1000000000){$millions=intdiv($number,1000000);$base=$millions===1?'UN MILLÓN':invoice_integer_words($millions).' MILLONES';return $number%1000000?$base.' '.invoice_integer_words($number%1000000):$base;}
    throw new InvalidArgumentException('El importe es demasiado grande para mostrarlo en letras.');
}
function invoice_amount_words($amount, $currency = 'PEN') {
    $rounded=round((float)$amount,2);$integer=(int)floor($rounded);$cents=(int)round(($rounded-$integer)*100);if($cents===100){$integer++;$cents=0;}
    $name = $currency === 'USD' ? 'DÓLARES ESTADOUNIDENSES' : 'SOLES';
    return invoice_integer_words($integer).' CON '.str_pad((string)$cents,2,'0',STR_PAD_LEFT).'/100 '.$name;
}
function invoice_money($amount, $currency = 'PEN') { return ($currency === 'USD' ? 'US$ ' : 'S/ ').number_format((float)$amount, 2, '.', ','); }
function invoice_emission_date($sale) { return $sale['fecha_emision'] ?? $sale['fecha'] ?? ''; }
function invoice_emission_time($sale) { return $sale['hora_emision'] ?? ''; }
function invoice_electronic_name($sale) { return strtoupper($sale['tipo_documento'] ?? 'Comprobante').' ELECTRÓNICA'; }
function invoice_download_name($sale) {
    $type = strtoupper($sale['tipo_documento'] ?? 'COMPROBANTE');
    $series = preg_replace('/[^A-Z0-9]/', '', strtoupper((string)($sale['serie'] ?? '')));
    $number = str_pad(preg_replace('/\D/', '', (string)($sale['numero'] ?? '')), 6, '0', STR_PAD_LEFT);
    return $type.'_'.$series.'-'.$number.'.pdf';
}
function invoice_sale_payments($sale,$payments) { return array_values(array_filter($payments,fn($row)=>(string)($row['venta_id']??'')===(string)$sale['id'])); }
function render_invoice_product_rows($rows, $currency = 'PEN') {
    ob_start(); foreach($rows as $row): ?>
    <tr><td class="text-end"><?php echo invoice_quantity($row['cantidad']); ?></td><td class="text-center"><?php echo htmlspecialchars($row['unidad']); ?></td><td><code><?php echo htmlspecialchars($row['codigo']); ?></code></td><td><?php echo htmlspecialchars($row['descripcion']); ?></td><td class="text-end"><?php echo invoice_money($row['precio'],$currency); ?></td><td class="text-end"><?php echo number_format((float)$row['descuento'],2); ?>%</td><td class="text-end fw-semibold"><?php echo invoice_money($row['importe'],$currency); ?></td></tr>
    <?php endforeach; return ob_get_clean();
}
