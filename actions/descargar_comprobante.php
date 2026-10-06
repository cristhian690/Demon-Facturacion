<?php
require_once '../config.php'; require_once '../includes/helpers.php'; require_once '../includes/invoice_pdf.php';
$temporary=null;
try {
    $sale=owned_record('ventas',$_GET['id']??'');
    $client=owned_record('clientes',(string)$sale['cliente_id']);
    $company=invoice_owner_company($sale,read_data('empresas'));
    $html=invoice_pdf_html($sale,$company,$client,get_data('productos'));
    $temporary=tempnam(sys_get_temp_dir(),'fact-kard-invoice-'); if($temporary===false)throw new RuntimeException('No se pudo preparar el PDF.');
    generate_invoice_pdf($html,$temporary);
    $filename=invoice_download_name($sale);
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.$filename.'"'); header('Content-Length: '.filesize($temporary)); header('X-Content-Type-Options: nosniff');
    readfile($temporary); unlink($temporary);
} catch(InvalidArgumentException $e) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Comprobante no disponible en la empresa activa.'; }
catch(Throwable $e) { if($temporary&&is_file($temporary))unlink($temporary); error_log($e->getMessage()); http_response_code(500); header('Content-Type: text/plain; charset=utf-8'); echo 'No se pudo generar el comprobante PDF.'; }
exit;
