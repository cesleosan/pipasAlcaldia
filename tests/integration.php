<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$admin=json_decode(file_get_contents(__DIR__.'/../storage/dev-db-admin.json'),true);
$c=array_replace($config,['db_user'=>'root','db_pass'=>$admin['password'],'db_name'=>'pipas_tlalpan_test_'.date('YmdHis')]);
$db=Database::connect($c,false);$db->exec('CREATE DATABASE `'.$c['db_name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$db->exec('USE `'.$c['db_name'].'`');$db->exec(file_get_contents(__DIR__.'/../sql/001_padron.sql'));$db->exec(file_get_contents(__DIR__.'/../sql/002_usuario_asignacion.sql'));$m=new Padron($db);
$checks=0;
function expect(bool $v,string $label):void{global $checks;if(!$v)throw new RuntimeException('FAIL: '.$label);$checks++;echo 'PASS '.$label."\n";}
function rejects(callable $fn,string $label):void{try{$fn();}catch(DomainException|PDOException $e){expect(true,$label);return;}throw new RuntimeException('FAIL no rechazó: '.$label);}
function testCurp(string $prefix):string{$dictionary='0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';$sum=0;for($i=0;$i<17;$i++)$sum+=(int)mb_strpos($dictionary,$prefix[$i])*(18-$i);return $prefix.((10-$sum%10)%10);}
Database::query($db,'INSERT INTO usuario(nombre,usuario,password_hash,perfil) VALUES (?,?,?,1)',['Pruebas','pruebas',password_hash('Test-only-password',PASSWORD_DEFAULT)]);
foreach(Padron::CATALOGS as $t=>$pk)Database::query($db,"INSERT INTO $t(nombre) VALUES (?)",['Catálogo de prueba']);
$input=['nombre'=>'PRUEBA','paterno'=>'EJEMPLO','materno'=>'SISTEMA','curp'=>testCurp('EISP900101HDFJRS0'),'calle'=>'Calle de pruebas','num_ext_mza'=>'1','num_int_lote'=>'','colonia_id_colonia'=>1,'tipo_padron_id_tipo_padron'=>1,'id_caja'=>1,'num_familias'=>2,'num_habitantes'=>5,'tamano_cisterna'=>10000,'tarifa'=>'125.50','dotacion'=>2];
expect(Validation::curp($input['curp']),'CURP válida');expect(!Validation::curp(substr($input['curp'],0,17).((int)$input['curp'][17]+1)%10),'rechazar dígito CURP incorrecto');expect(!Validation::curp(testCurp('EISP900231HDFJRS0')),'rechazar fecha inexistente');
$id=$m->save($input,1);$r=$m->get($id);expect($r['folio']==='0000001' && $r['status_padron']==='1','alta con folio y estado');expect($m->stats()['viajes']===2,'indicador de viajes');
rejects(fn()=>$m->save($input,1),'CURP duplicada');$copy=$input;$copy['curp']=testCurp('EISP900102HDFJRS0');$copy['calle']=' CALLE  de Pruebas. ';rejects(fn()=>$m->save($copy,1),'domicilio duplicado normalizado');
expect((int)$db->query('SELECT COUNT(*) FROM padron')->fetchColumn()===1,'duplicados sin registros parciales');
$input['version']=$r['version'];$input['telefono']='5512345678';$m->save($input,1,$id);expect($m->get($id)['telefono']==='5512345678','edición con persistencia');rejects(fn()=>$m->save($input,1,$id),'rechazar edición concurrente obsoleta');
$r=$m->get($id);$m->operate($id,'bloquear',['version'=>$r['version'],'motivo'=>1,'justificacion'=>'Prueba de bloqueo'],1);expect($m->get($id)['status_padron']==='2','bloqueo');expect($m->stats()['viajes']===0,'excluir bloqueados de cuota operable');
$r=$m->get($id);rejects(fn()=>$m->operate($id,'extraordinaria',['version'=>$r['version'],'num_dotacion'=>1,'autorizo_id_autorizo'=>1,'justificacion'=>'No debe autorizar'],1),'impedir extra a bloqueado');
$m->operate($id,'reactivar',['version'=>$r['version'],'justificacion'=>'Prueba de reactivación'],1);expect($m->get($id)['status_padron']==='1','reactivación');expect($m->history($id)['bloqueos'][0]['fecha_padron_desbloqueo']!==null,'fecha de cierre de bloqueo');
$r=$m->get($id);$m->operate($id,'dotacion',['version'=>$r['version'],'dotacion'=>5,'justificacion'=>'Ajuste probado'],1);$r=$m->get($id);expect((int)$r['dotacion']===5 && count($m->history($id)['dotaciones'])===2,'cambio de cuota conserva historial');
rejects(fn()=>Database::query($db,"INSERT INTO dotacion(domicilio_id_domicilio,dotacion,tamano_cisterna,tarifa) VALUES (?,1,1000,10)",[$r['id_domicilio']]),'índice impide dos cuotas activas');
$m->operate($id,'extraordinaria',['version'=>$r['version'],'num_dotacion'=>2,'autorizo_id_autorizo'=>1,'justificacion'=>'Prueba extraordinaria'],1);expect((int)$m->get($id)['dotacion']===5 && count($m->history($id)['extras'])===1,'extra sin modificar cuota regular');
Database::query($db,"INSERT INTO servicio_historial(padron_id_padron,recibo,fecha,importe,estado,origen) VALUES (?,'TEST-RECIBO',CURDATE(),125.50,'Entregado','pruebas')",[$id]);
expect($m->search(['by'=>'recibo','q'=>'TEST-RECIBO'])['total']===1,'búsqueda por recibo');expect($m->search(['by'=>'nombre','q'=>'EJEMPLO'])['total']===1,'búsqueda por nombre');expect($m->search(['by'=>'folio','q'=>'1'])['total']===1,'búsqueda por folio');expect($m->search(['q'=>"' OR 1=1 --"])['total']===0,'búsqueda parametrizada');
$r=$m->get($id);$before=(int)$db->query('SELECT COUNT(*) FROM auditoria')->fetchColumn();rejects(fn()=>$m->operate($id,'dotacion',['version'=>$r['version'],'dotacion'=>11,'justificacion'=>'Fuera del rango'],1),'rango de cuota');expect((int)$db->query('SELECT COUNT(*) FROM auditoria')->fetchColumn()===$before,'rechazo no genera auditoría falsa');
$db->exec("CREATE TRIGGER test_fail BEFORE INSERT ON dotacion FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
$copy=$input;$copy['curp']=testCurp('EISP900103HDFJRS0');$copy['num_ext_mza']='55';rejects(fn()=>$m->save($copy,1),'rollback ante fallo del tercer INSERT');expect((int)$db->query('SELECT COUNT(*) FROM padron')->fetchColumn()===1 && (int)$db->query('SELECT COUNT(*) FROM domicilio')->fetchColumn()===1,'sin beneficiario ni domicilio huérfanos');
rejects(fn()=>$m->operate($id,'dotacion',['version'=>$r['version'],'dotacion'=>3,'justificacion'=>'Simular rollback'],1),'rollback de cambio de dotación');expect((int)$m->get($id)['dotacion']===5,'cuota vigente restaurada tras fallo');
$db->exec('DROP TRIGGER test_fail');
$_SESSION['user']=['perfil'=>11];expect(can('read')&&can('edit')&&!can('create')&&!can('manage'),'permisos ALTAS');$_SESSION['user']=['perfil'=>1];expect(can('manage')&&can('create'),'permisos ROOT');
echo "TOTAL: $checks verificaciones correctas. Base de prueba: {$c['db_name']}\n";
file_put_contents(__DIR__.'/../tmp/test-db-name.txt',$c['db_name']);

