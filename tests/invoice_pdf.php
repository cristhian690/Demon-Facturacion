<?php
$temp=sys_get_temp_dir().'/invoice-pdf-test-'.bin2hex(random_bytes(6));mkdir($temp);
define('DATA_PATH',$temp.'/');define('BASE_URL','/');$_SESSION=['empresa_id'=>1];
require __DIR__.'/../includes/helpers.php';require __DIR__.'/../includes/invoice_pdf.php';
$checks=0;function pdf_ok($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
try {
    $sale=['id'=>1,'empresa_id'=>1,'cliente_id'=>1,'tipo_documento'=>'Factura','serie'=>'F001','numero'=>'7','fecha'=>'2026-10-06','fecha_emision'=>'2026-10-05','hora_emision'=>'14:22:31','moneda'=>'USD','tipo_cambio'=>3.742,'condicion_pago'=>'credito','fecha_vencimiento'=>'2026-11-05','vendedor'=>'Administrador','subtotal'=>100,'igv'=>18,'total'=>118,'detalles'=>[['producto_id'=>1,'unidad_medida'=>'KG','cantidad'=>2.5,'precio_unitario'=>40,'descuento'=>0,'subtotal'=>100]]];
    $company=['id'=>1,'razon_social'=>'Empresa PDF S.A.C.','nombre_comercial'=>'Empresa PDF','ruc'=>'20123456789','direccion'=>'Lima','telefono'=>'555','correo'=>'pdf@example.com','logo'=>''];
    $client=['id'=>1,'tipo_documento'=>'RUC','numero_documento'=>'20999999991','nombre'=>'Cliente PDF','direccion'=>'Callao'];
    $products=[['id'=>1,'sku'=>'P-001','nombre'=>'Arena fina','unidad_medida'=>'KG']];
    $html=invoice_pdf_html($sale,$company,$client,$products);
    foreach(['FACTURA ELECTRÓNICA','F001-000007','05/10/2026','14:22:31','USD - Dólares estadounidenses','3.742','Cliente PDF','Arena fina','Documento generado por prototipo - sin envío SUNAT'] as $marker)pdf_ok(str_contains($html,$marker),'PDF HTML contains '.$marker);
    pdf_ok(!str_contains($html,'Aceptado por SUNAT'),'No false SUNAT status');
    pdf_ok(invoice_download_name($sale)==='FACTURA_F001-000007.pdf','Factura filename');
    $boleta=$sale;$boleta['tipo_documento']='Boleta';$boleta['serie']='B001';$boleta['numero']='1';pdf_ok(invoice_download_name($boleta)==='BOLETA_B001-000001.pdf','Boleta filename');
    pdf_ok(invoice_amount_words(118,'USD')==='CIENTO DIECIOCHO CON 00/100 DÓLARES ESTADOUNIDENSES','USD amount in words');
    $output=$temp.'/invoice.pdf';generate_invoice_pdf($html,$output);$bytes=file_get_contents($output);
    pdf_ok(str_starts_with($bytes,'%PDF-')&&strlen($bytes)>5000,'Browser generated a real PDF');
    echo "PDF OK: $checks verificaciones; comprobante temporal generado.\n";
} finally {foreach(glob($temp.'/*')as$file){if(is_dir($file))invoice_pdf_remove_directory($file);else unlink($file);}rmdir($temp);}
