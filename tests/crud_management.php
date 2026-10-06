<?php
// Fixtures are disposable: this test never reads or writes the project's data directory.
$temp=sys_get_temp_dir().'/crud-management-'.bin2hex(random_bytes(8)); mkdir($temp);
define('DATA_PATH',$temp.'/'); define('BASE_URL','/');
$_SESSION=['empresa_id'=>1,'empresa_nombre'=>'Empresa Uno','form_token'=>'test'];
require __DIR__.'/../includes/helpers.php'; require __DIR__.'/../includes/catalog.php'; require __DIR__.'/../includes/companies.php';
$checks=0;
function ok($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
function fixture_crud($name,$rows=[]){file_put_contents(DATA_PATH.$name.'.json',json_encode($rows));}
try {
    fixture_crud('empresas',[
        ['id'=>1,'razon_social'=>'Empresa Uno','nombre_comercial'=>'Uno','ruc'=>'20111111111','direccion'=>'A','telefono'=>'1','correo'=>'uno@example.com','logo'=>'','estado'=>'Activo'],
        ['id'=>2,'razon_social'=>'Empresa Dos','nombre_comercial'=>'Dos','ruc'=>'20222222222','direccion'=>'B','telefono'=>'2','correo'=>'dos@example.com','logo'=>'','estado'=>'Activo']
    ]);
    foreach(['clientes','proveedores','productos','almacenes','compras','ventas','inventario','kardex','devoluciones','pagos'] as $name) fixture_crud($name);
    $inputs=[
        'clientes'=>['tipo_documento'=>'DNI','numero_documento'=>'12345678','nombre'=>'Cliente CRUD','direccion'=>'A','telefono'=>'1','correo'=>'c@example.com','estado'=>'Activo'],
        'proveedores'=>['tipo_documento'=>'RUC','numero_documento'=>'20999999991','nombre'=>'Proveedor CRUD','direccion'=>'B','telefono'=>'2','correo'=>'p@example.com','estado'=>'Activo'],
        'productos'=>['sku'=>'CRUD-1','nombre'=>'Producto CRUD','descripcion'=>'D','categoria'=>'C','marca'=>'M','unidad_medida'=>'UN','stock_minimo'=>'2','estado'=>'Activo'],
        'almacenes'=>['nombre'=>'Almacén CRUD','ubicacion'=>'Lima','estado'=>'Activo']
    ];
    $ids=[];
    foreach($inputs as $table=>$input){
        $created=save_catalog($table,$input)['record']; $ids[$table]=$created['id'];
        ok($created['empresa_id']===1 && $created['estado']==='Activo',"$table crear y empresa");
        ok(owned_record($table,(string)$created['id'])['id']===$created['id'],"$table ver");
        $edit=$input; $edit['id']=(string)$created['id']; $edit['nombre']=$input['nombre'].' editado';
        $updated=save_catalog($table,$edit)['record'];
        ok($updated['id']===$created['id'] && $updated['nombre']===$edit['nombre'],"$table editar sin cambiar ID");
        set_catalog_status($table,(string)$created['id'],'Inactivo');
        ok(count(get_data($table))===1 && get_data($table)[0]['estado']==='Inactivo',"$table desactivar conserva registro");
        set_catalog_status($table,(string)$created['id'],'Activo');
        ok(get_data($table)[0]['estado']==='Activo',"$table reactivar");
    }
    // Add relationships and prove a later deactivation never deletes or orphans the master row.
    fixture_crud('ventas',[['id'=>1,'empresa_id'=>1,'cliente_id'=>$ids['clientes'],'almacen_id'=>$ids['almacenes']]]);
    fixture_crud('compras',[['id'=>1,'empresa_id'=>1,'proveedor_id'=>$ids['proveedores'],'almacen_id'=>$ids['almacenes']]]);
    fixture_crud('inventario',[['empresa_id'=>1,'producto_id'=>$ids['productos'],'almacen_id'=>$ids['almacenes'],'stock_actual'=>1,'cpp'=>10,'valor_inventario'=>10]]);
    fixture_crud('kardex',[['id'=>1,'empresa_id'=>1,'producto_id'=>$ids['productos'],'almacen_id'=>$ids['almacenes']]]);
    foreach($ids as $table=>$id){set_catalog_status($table,(string)$id,'Inactivo');ok(count(get_data($table))===1 && catalog_history_count($table,$id)>0,"$table historial conservado sin huérfanos");}
    $newCompany=['razon_social'=>'Empresa CRUD','nombre_comercial'=>'CRUD','ruc'=>'20333333333','direccion'=>'C','telefono'=>'3','correo'=>'crud@example.com','logo'=>'','estado'=>'Activo'];
    $company=save_company($newCompany)['record']; ok($company['id']===3,'empresa crear y ver');
    $edit=$newCompany; $edit['id']='3'; $edit['razon_social']='Empresa CRUD editada'; $updated=save_company($edit)['record']; ok($updated['id']===3 && $updated['razon_social']===$edit['razon_social'],'empresa editar sin cambiar ID');
    $clients=read_data('clientes'); $clients[]=['id'=>99,'empresa_id'=>3,'nombre'=>'Cliente histórico','estado'=>'Activo']; file_put_contents(DATA_PATH.'clientes.json',json_encode($clients));
    set_company_status('3','Inactivo'); ok(count(read_data('empresas'))===3 && read_data('empresas')[2]['estado']==='Inactivo' && company_history_count(3)>0,'empresa con historial se desactiva sin borrar ni dejar huérfanos');
    set_company_status('3','Activo'); ok(read_data('empresas')[2]['estado']==='Activo','empresa reactivar');
    $_SESSION['empresa_id']=2; ok(count(get_data('clientes'))===0,'multiempresa oculta registros ajenos');
    $rejected=false; try{owned_record('clientes',(string)$ids['clientes']);}catch(InvalidArgumentException $e){$rejected=true;} ok($rejected,'multiempresa rechaza acceso directo');
    echo "CRUD OK: $checks verificaciones; datos temporales exclusivamente.\n";
} finally { foreach(glob(DATA_PATH.'*') as $file) if(is_file($file)) unlink($file); if(is_file(DATA_PATH.'.write.lock'))unlink(DATA_PATH.'.write.lock'); rmdir($temp); }
