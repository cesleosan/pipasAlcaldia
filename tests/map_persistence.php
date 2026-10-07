<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$name=trim(file_get_contents(__DIR__.'/../tmp/test-db-name.txt'));
if(!preg_match('/^pipas_tlalpan_test_[0-9]+$/D',$name))throw new RuntimeException('Solo pruebas');
$db=Database::connect(array_replace($config,['db_name'=>$name]));$m=new Padron($db);$original=$m->get(1);$checks=0;
function verify(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo "PASS $label\n";}
try{
 $input=$original;$input['latitud']='19.288';$input['longitud']='-99.1677';$m->save($input,1,1);$read=$m->get(1);
 verify(abs((float)$read['latitud']-19.288)<0.0000001&&abs((float)$read['longitud']+99.1677)<0.0000001,'persistencia del punto');
 verify($read['calle']===$original['calle']&&$read['num_ext_mza']===$original['num_ext_mza'],'marcar punto no reemplaza domicilio');
 foreach([['latitud'=>'91','longitud'=>'-99'],['latitud'=>'19','longitud'=>'']] as $bad){$input=array_replace($read,$bad);try{$m->save($input,1,1);throw new RuntimeException('Coordenadas inválidas aceptadas');}catch(DomainException $e){verify(true,'rechazo de punto inválido o incompleto');}}
 $input=$read;$input['latitud']='';$input['longitud']='';$m->save($input,1,1);$read=$m->get(1);verify($read['latitud']===null&&$read['longitud']===null,'quitar punto persiste sin coordenadas');
}finally{$original['version']=$m->get(1)['version'];$m->save($original,1,1);}
echo "TOTAL MAPA $checks\n";
