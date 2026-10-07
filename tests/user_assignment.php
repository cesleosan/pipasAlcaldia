<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$name=trim(file_get_contents(__DIR__.'/../tmp/test-db-name.txt'));
if(!preg_match('/^pipas_tlalpan_test_[0-9]+$/D',$name))throw new RuntimeException('Solo pruebas');
$db=Database::connect(array_replace($config,['db_name'=>$name]));$checks=0;
function test(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo "PASS $label\n";}
function reject(callable $fn,string $label):void{try{$fn();}catch(DomainException $e){test(true,$label);return;}throw new RuntimeException($label);}
$db->beginTransaction();
try{
 $valid=['id_caja'=>'1','dias'=>['1','2','3','4','5'],'hora_inicio'=>'08:00','hora_fin'=>'16:00'];
 $data=UserAssignment::validate($db,$valid);UserAssignment::save($db,1,$data);
 $read=Database::query($db,'SELECT * FROM usuario_asignacion WHERE id_usuario=1')->fetch();test($read['id_caja']===1&&$read['dias']==='1,2,3,4,5'&&$read['hora_inicio']==='08:00:00','persistencia de garza y turno');
 $night=UserAssignment::validate($db,array_replace($valid,['hora_inicio'=>'22:00','hora_fin'=>'06:00']));test(str_contains(UserAssignment::summary($night),'(+1 día)'),'turno nocturno');
 reject(fn()=>UserAssignment::validate($db,array_replace($valid,['hora_inicio'=>'25:00'])),'hora inválida');
 reject(fn()=>UserAssignment::validate($db,array_replace($valid,['hora_fin'=>'08:00'])),'horas iguales');
 reject(fn()=>UserAssignment::validate($db,array_replace($valid,['dias'=>[]])),'horario sin días');
 reject(fn()=>UserAssignment::validate($db,array_replace($valid,['dias'=>['8']])),'día fuera de rango');
 reject(fn()=>UserAssignment::validate($db,array_replace($valid,['dias'=>[['1']]])),'parámetros anidados');
 reject(fn()=>UserAssignment::validate($db,array_replace($valid,['id_caja'=>'999999'])),'garza inexistente');
 $db->exec('UPDATE caja SET activo=0 WHERE id_caja=1');reject(fn()=>UserAssignment::validate($db,$valid),'garza inactiva');$db->exec('UPDATE caja SET activo=1 WHERE id_caja=1');
 $empty=UserAssignment::validate($db,[]);UserAssignment::save($db,1,$empty);$read=Database::query($db,'SELECT * FROM usuario_asignacion WHERE id_usuario=1')->fetch();test($read['hora_inicio']===null&&$read['id_caja']===null,'quitar asignación');
 test(UserAssignment::validate($db,['id_caja'=>'1'])['id_caja']===1,'garza sin turno');
 test(UserAssignment::validate($db,array_replace($valid,['id_caja'=>'']))['id_caja']===null,'turno sin garza');
}finally{$db->rollBack();}
echo "TOTAL ASIGNACIÓN $checks\n";
