<?php
$temp=sys_get_temp_dir().'/company-logo-'.bin2hex(random_bytes(8));mkdir($temp);$uploads=$temp.'/uploads';mkdir($uploads);
define('DATA_PATH',$temp.'/data/');mkdir(DATA_PATH);define('BASE_URL','/');
$_SESSION=['empresa_id'=>1,'empresa_nombre'=>'Empresa Uno','form_token'=>'test'];
require __DIR__.'/../includes/helpers.php';require __DIR__.'/../includes/companies.php';require __DIR__.'/../includes/company_logos.php';
$checks=0;function logo_ok($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
function png_fixture($path){file_put_contents($path,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));}
function upload_fixture($path,$name='brand.png'){return ['name'=>$name,'tmp_name'=>$path,'size'=>filesize($path),'error'=>UPLOAD_ERR_OK];}
try{
 file_put_contents(DATA_PATH.'empresas.json',json_encode([['id'=>1,'razon_social'=>'Empresa Uno','nombre_comercial'=>'Uno','ruc'=>'20111111111','direccion'=>'A','telefono'=>'1','correo'=>'uno@example.com','logo'=>'','estado'=>'Activo'],['id'=>2,'razon_social'=>'Empresa Dos','nombre_comercial'=>'Dos','ruc'=>'20222222222','direccion'=>'B','telefono'=>'2','correo'=>'dos@example.com','logo'=>'','estado'=>'Activo']]));
 foreach(['clientes','proveedores','productos','almacenes','compras','ventas','inventario','kardex','devoluciones','pagos']as$t)file_put_contents(DATA_PATH.$t.'.json','[]');
 $base=['id'=>'1','razon_social'=>'Empresa Uno','nombre_comercial'=>'Uno','ruc'=>'20111111111','direccion'=>'A','telefono'=>'1','correo'=>'uno@example.com','estado'=>'Activo'];
 $result=save_company_with_logo($base,null,$uploads,false);logo_ok($result['record']['logo']==='','Empresa sin logo se conserva');
 $png1=$temp.'/first.png';png_fixture($png1);$result=save_company_with_logo($base,upload_fixture($png1),$uploads,false);$first=$result['record']['logo'];logo_ok(valid_company_logo_reference($first)&&is_file(company_logo_path($first,$uploads)),'Subida crea ruta válida');logo_ok(str_starts_with(basename($first),'company-1-'),'Archivo asociado a empresa 1');
 $result=save_company_with_logo($base,null,$uploads,false);logo_ok($result['record']['logo']===$first,'Editar otros datos conserva logo');
 $png2=$temp.'/second.png';png_fixture($png2);$result=save_company_with_logo($base,upload_fixture($png2,'replacement.PNG'),$uploads,false);$second=$result['record']['logo'];logo_ok($second!==$first&&is_file(company_logo_path($second,$uploads))&&!is_file(company_logo_path($first,$uploads)),'Reemplazo elimina archivo anterior después de guardar');
 $removed=save_company_with_logo($base+['quitar_logo'=>'1'],null,$uploads,false);logo_ok($removed['record']['logo']===''&&!is_file(company_logo_path($second,$uploads)),'Eliminación limpia referencia y archivo');
 $_SESSION['empresa_id']=2;$base2=['id'=>'2','razon_social'=>'Empresa Dos','nombre_comercial'=>'Dos','ruc'=>'20222222222','direccion'=>'B','telefono'=>'2','correo'=>'dos@example.com','estado'=>'Activo'];$png3=$temp.'/third.png';png_fixture($png3);$third=save_company_with_logo($base2,upload_fixture($png3),$uploads,false)['record']['logo'];logo_ok(str_starts_with(basename($third),'company-2-'),'Segunda empresa usa archivo propio');$_SESSION['empresa_id']=1;logo_ok((owned_record('empresas','1')['logo']??'')===''&&read_data('empresas')[1]['logo']===$third,'Logos no se mezclan entre empresas');
 $bad=$temp.'/bad.txt';file_put_contents($bad,'not an image');$rejected=false;try{save_company_with_logo($base,upload_fixture($bad,'attack.php'),$uploads,false);}catch(InvalidArgumentException $e){$rejected=true;}logo_ok($rejected,'Archivo y extensión inválidos rechazados');
 $large=$temp.'/large.png';$handle=fopen($large,'wb');fseek($handle,COMPANY_LOGO_MAX_BYTES);fwrite($handle,"x");fclose($handle);$rejected=false;try{save_company_with_logo($base,upload_fixture($large),$uploads,false);}catch(InvalidArgumentException $e){$rejected=true;}logo_ok($rejected,'Archivo superior a 2 MB rechazado');
 logo_ok(!valid_company_logo_reference('../logo.png')&&!valid_company_logo_reference('assets/uploads/logos/x.php'),'Rutas peligrosas rechazadas');
 $invoice=file_get_contents(__DIR__.'/../pages/ventas/documento.php');logo_ok(str_contains($invoice,'company-logo')&&str_contains($invoice,'company-logo-fallback')&&str_contains($invoice,'nombre_comercial'),'Factura contempla logo, fallback y nombre comercial');
 echo "LOGOS OK: $checks verificaciones con archivos temporales.\n";
}finally{foreach(glob($uploads.'/*')as$f)if(is_file($f))unlink($f);foreach(glob(DATA_PATH.'*')as$f)if(is_file($f))unlink($f);foreach(glob($temp.'/*')as$f)if(is_file($f))unlink($f);foreach([$uploads,DATA_PATH,$temp]as$d)if(is_dir($d))rmdir($d);}
