<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$name=trim(file_get_contents(__DIR__.'/../tmp/test-db-name.txt'));
if(!preg_match('/^pipas_tlalpan_test_[0-9]+$/D',$name))exit(1);
$config['db_name']=$name;putenv('PIPAS_DB_NAME='.$name);$db=Database::connect($config);
$prefix='IMPORT-'.bin2hex(random_bytes(4));$file=__DIR__.'/../tmp/import-test.csv';$cols=['folio','recibo','fecha','importe','viajes','estado','origen'];
$run=function()use($file){$p=proc_open([PHP_BINARY,__DIR__.'/../scripts/import-history.php',$file],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return proc_close($p);};
$write=function($rows)use($file,$cols){$f=fopen($file,'w');fputcsv($f,$cols,',','"','');foreach($rows as $r)fputcsv($f,$r,',','"','');fclose($f);};
$valid=['0000001',$prefix,date('Y-m-d'),'200.00',1,'Entregado','Prueba de integración'];$invalid=$valid;$invalid[0]='99999999999';$invalid[1].='-B';
$write([$valid,$invalid]);if($run()===0)throw new RuntimeException('Aceptó fila inválida');
$count=(int)Database::query($db,'SELECT COUNT(*) FROM servicio_historial WHERE recibo=?',[$prefix])->fetchColumn();if($count!==0)throw new RuntimeException('No revirtió importación');echo "PASS importación inválida revierte todas las filas\n";
$write([$valid]);if($run()!==0)throw new RuntimeException('Falló importación válida');$m=new Padron($db);if($m->search(['by'=>'recibo','q'=>$prefix])['total']!==1)throw new RuntimeException('Recibo no consultable');echo "PASS importación válida consultable por recibo\n";
if($run()===0)throw new RuntimeException('Aceptó recibo duplicado');$count=(int)Database::query($db,'SELECT COUNT(*) FROM servicio_historial WHERE recibo=?',[$prefix])->fetchColumn();if($count!==1)throw new RuntimeException('Duplicó recibo');echo "PASS importación repetida no duplica recibos\n";
