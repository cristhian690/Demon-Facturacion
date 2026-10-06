<?php
require_once __DIR__.'/invoice_view.php';
require_once __DIR__.'/company_logos.php';

function invoice_pdf_escape($value) { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function invoice_pdf_file_url($path) {
    $path = str_replace('\\', '/', realpath($path) ?: $path);
    return 'file:///'.str_replace(['%2F','%3A'], ['/',':'], rawurlencode($path));
}
function invoice_pdf_html($sale, $company, $client, $products) {
    $currency=$sale['moneda']??'PEN'; $rows=invoice_product_rows($sale,$products); $logo=company_logo_path($company['logo']??'');
    if (!$logo || !is_file($logo)) $logo='';
    $type=invoice_electronic_name($sale); $number=($sale['serie']??'').'-'.str_pad((string)($sale['numero']??''),6,'0',STR_PAD_LEFT);
    $date=invoice_emission_date($sale); $time=invoice_emission_time($sale); $money=fn($value)=>invoice_money($value,$currency);
    $detail=''; foreach($rows as $row) $detail.='<tr><td class="c">'.invoice_pdf_escape(invoice_quantity($row['cantidad'])).'</td><td class="c">'.invoice_pdf_escape($row['unidad']).'</td><td>'.invoice_pdf_escape($row['codigo']).'</td><td>'.invoice_pdf_escape($row['descripcion']).'</td><td class="r">'.$money($row['precio']).'</td><td class="r">'.number_format((float)$row['descuento'],2).'%</td><td class="r"><b>'.$money($row['importe']).'</b></td></tr>';
    $logoHtml=$logo?'<img class="logo" src="'.invoice_pdf_escape(invoice_pdf_file_url($logo)).'">':'<div class="logo-empty">FACT-KARD</div>';
    $exchange=$currency==='USD'?'<div><b>Tipo de cambio:</b> '.number_format((float)$sale['tipo_cambio'],3).'</div>':'';
    $due=($sale['condicion_pago']??'contado')==='credito'&&!empty($sale['fecha_vencimiento'])?'<div><b>Vencimiento:</b> '.date('d/m/Y',strtotime($sale['fecha_vencimiento'])).'</div>':'';
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
    @page{size:A4;margin:12mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#172033;font-size:10px;margin:0}.header{display:grid;grid-template-columns:105px 1fr 215px;gap:14px;align-items:center}.logo{width:100px;height:70px;object-fit:contain}.logo-empty{width:95px;height:60px;border:1px solid #d6deea;border-radius:8px;display:grid;place-items:center;color:#64748b;font-weight:bold}.company h1{font-size:15px;margin:0 0 5px;color:#1d4ed8}.company p{margin:2px 0;color:#475569}.identity{border:2px solid #172033;border-radius:11px;padding:12px;text-align:center}.identity h2{font-size:17px;margin:6px 0}.identity .number{font-size:16px;font-weight:bold;border-top:1px solid #ccd5e2;padding-top:6px}.grid{display:grid;grid-template-columns:1.25fr .75fr;gap:10px;margin-top:14px}.box{border:1px solid #cfd8e5;border-radius:9px;padding:11px;line-height:1.55}.box h3{font-size:10px;text-transform:uppercase;letter-spacing:.06em;margin:0 0 7px;color:#475569}.line{display:grid;grid-template-columns:90px 1fr;gap:6px}.line b{color:#64748b}table{width:100%;border-collapse:collapse;margin-top:14px}th{background:#e8edf4;text-transform:uppercase;font-size:8px;padding:7px 5px;border-bottom:2px solid #172033}td{padding:7px 5px;border-bottom:1px solid #dce3ed;vertical-align:top}.r{text-align:right}.c{text-align:center}.words{margin-top:10px;padding:9px;border:1px solid #cfd8e5;border-radius:7px;font-weight:bold}.summary{display:grid;grid-template-columns:1fr 240px;gap:18px;margin-top:12px}.total div{display:flex;justify-content:space-between;padding:6px 9px}.total .grand{background:#172033;color:white;font-size:14px;font-weight:bold}.payment{margin-top:12px;border:1px solid #cfd8e5;border-radius:8px;padding:10px}.footer{margin-top:18px;padding-top:9px;border-top:1px solid #dce3ed;text-align:center;color:#64748b;font-size:9px}.prototype{margin-top:4px;font-weight:bold;color:#475569}</style></head><body>
    <header class="header">'.$logoHtml.'<div class="company"><h1>'.invoice_pdf_escape($company['razon_social']??'').'</h1>'.(!empty($company['nombre_comercial'])?'<p><b>'.invoice_pdf_escape($company['nombre_comercial']).'</b></p>':'').'<p>'.invoice_pdf_escape($company['direccion']??'').'</p><p>'.invoice_pdf_escape($company['telefono']??'').' · '.invoice_pdf_escape($company['correo']??'').'</p></div><div class="identity"><b>RUC '.invoice_pdf_escape($company['ruc']??'').'</b><h2>'.invoice_pdf_escape($type).'</h2><div class="number">'.invoice_pdf_escape($number).'</div></div></header>
    <section class="grid"><div class="box"><h3>Datos del cliente</h3><div class="line"><b>'.invoice_pdf_escape($client['tipo_documento']??'Documento').'</b><span>'.invoice_pdf_escape($client['numero_documento']??'-').'</span></div><div class="line"><b>Cliente</b><span>'.invoice_pdf_escape($client['nombre']??'').'</span></div><div class="line"><b>Dirección</b><span>'.invoice_pdf_escape($client['direccion']??'-').'</span></div></div><div class="box"><h3>Emisión</h3><div><b>Fecha:</b> '.($date?date('d/m/Y',strtotime($date)):'-').'</div><div><b>Hora:</b> '.invoice_pdf_escape($time?:'-').'</div>'.$due.'<div><b>Moneda:</b> '.($currency==='USD'?'USD - Dólares estadounidenses':'PEN - Soles').'</div>'.$exchange.'</div></section>
    <table><thead><tr><th>Cant.</th><th>U.M.</th><th>Código</th><th>Descripción</th><th>P. unit.</th><th>Dto.</th><th>Total</th></tr></thead><tbody>'.$detail.'</tbody></table>
    <div class="words">SON: '.invoice_pdf_escape(invoice_amount_words($sale['total']??0,$currency)).'</div><section class="summary"><div><b>Vendedor:</b> '.invoice_pdf_escape($sale['vendedor']??'Administrador').'<div class="payment"><b>Condición de pago:</b> '.(($sale['condicion_pago']??'contado')==='credito'?'Crédito':'Contado').'</div></div><div class="total"><div><span>Op. gravadas</span><b>'.$money($sale['subtotal']??0).'</b></div><div><span>IGV 18%</span><b>'.$money($sale['igv']??0).'</b></div><div class="grand"><span>Total a pagar</span><span>'.$money($sale['total']??0).'</span></div></div></section>
    <footer class="footer">Representación del comprobante generado por Fact-Kard.<div class="prototype">Documento generado por prototipo - sin envío SUNAT</div></footer></body></html>';
}
function invoice_pdf_browser() {
    $paths=['C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe','C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe','C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe','C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'];
    foreach($paths as $path) if(is_file($path)) return $path;
    throw new RuntimeException('No se encontró un navegador compatible para generar el PDF.');
}
function invoice_pdf_remove_directory($directory) { if(!is_dir($directory))return; foreach(scandir($directory)?:[] as$item)if($item!=='.'&&$item!=='..'){$path=$directory.DIRECTORY_SEPARATOR.$item;is_dir($path)?invoice_pdf_remove_directory($path):@unlink($path);}@rmdir($directory); }
function generate_invoice_pdf($html,$destination) {
    if(!function_exists('proc_open'))throw new RuntimeException('La generación PDF no está disponible.');
    $work=sys_get_temp_dir().DIRECTORY_SEPARATOR.'fact-kard-pdf-'.bin2hex(random_bytes(6)); if(!mkdir($work,0700,true))throw new RuntimeException('No se pudo preparar el PDF.');
    $source=$work.DIRECTORY_SEPARATOR.'invoice.html'; $profile=$work.DIRECTORY_SEPARATOR.'profile'; mkdir($profile); file_put_contents($source,$html,LOCK_EX);
    $command=[invoice_pdf_browser(),'--headless','--no-sandbox','--disable-gpu','--disable-software-rasterizer','--no-pdf-header-footer','--allow-file-access-from-files','--user-data-dir='.$profile,'--print-to-pdf='.$destination,invoice_pdf_file_url($source)];
    $process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes); if(!is_resource($process)){invoice_pdf_remove_directory($work);throw new RuntimeException('No se pudo iniciar el generador PDF.');}
    fclose($pipes[0]); $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process); invoice_pdf_remove_directory($work);
    if($code!==0||!is_file($destination)||filesize($destination)<1000)throw new RuntimeException('No se pudo generar el PDF. '.$stderr.$stdout);
    return $destination;
}
