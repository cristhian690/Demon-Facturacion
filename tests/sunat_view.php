<?php
$root=dirname(__DIR__);
$checks=0;
function sunat_check($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
$pages=['index.php','documentos.php','nota_credito.php','nota_debito.php','estado.php','configuracion.php'];
foreach($pages as $page){
 $path=$root.'/pages/sunat/'.$page;
 sunat_check(is_file($path),'SUNAT page exists: '.$page);
 $content=file_get_contents($path);
 sunat_check(stripos($content,'curl_')===false&&stripos($content,'file_put_contents')===false&&stripos($content,'save_data(')===false,'No writes or external calls: '.$page);
 sunat_check(stripos($content,'Aceptado</span>')===false&&stripos($content,'Rechazado</span>')===false,'No real response assigned: '.$page);
}
$documents=file_get_contents($root.'/pages/sunat/documentos.php');
sunat_check(strpos($documents,'disabled')!==false,'Future document actions disabled');
$table=file_get_contents($root.'/includes/sunat_documents_table.php');
sunat_check(strpos($table,'No enviado')!==false,'Documents remain not sent');
$config=file_get_contents($root.'/pages/sunat/configuracion.php');
sunat_check(substr_count($config,'disabled')>=7&&stripos($config,'password')===false,'Configuration disabled and no password requested');
$sale=file_get_contents($root.'/pages/ventas/detalle.php');
sunat_check(strpos($sale,'pages/sunat/documentos.php')!==false,'Sale links to SUNAT module');
$sidebar=file_get_contents($root.'/includes/sidebar.php');
sunat_check(strpos($sidebar,"sidebar_has('/sunat/')")!==false&&substr_count($sidebar,'pages/sunat/')>=6,'SUNAT sidebar navigation and active state');
$docs=file_get_contents($root.'/docs/sunat-futuro.md');
sunat_check(strpos($docs,'XmlGenerator')!==false&&strpos($docs,'variables de entorno')!==false&&strpos($docs,'Multiempresa')!==false,'Future architecture and security documented');
echo "OK: $checks verificaciones visuales SUNAT; sin envíos ni archivos tributarios.\n";

