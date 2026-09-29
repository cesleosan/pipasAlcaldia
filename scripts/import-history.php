<?php
// Importa el contrato de historial sin modificar caja ni entregas del sistema anterior.
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$file=$argv[1]??'';
if(!$file || !is_readable($file)){fwrite(STDERR,"Uso: php scripts/import-history.php archivo.csv\nEncabezados: folio,recibo,fecha,importe,viajes,estado,origen\n");exit(1);}
try{
 $db=Database::connect($config);$fp=fopen($file,'r');$header=fgetcsv($fp,0,',','"','');
 if(!$header)throw new DomainException('Archivo vacío.');$header[0]=ltrim($header[0],"\xEF\xBB\xBF");
 if($header!==['folio','recibo','fecha','importe','viajes','estado','origen'])throw new DomainException('Encabezados incorrectos.');
 $count=Database::transaction($db,function()use($db,$fp){$count=0;$line=1;while(($row=fgetcsv($fp,0,',','"',''))!==false){$line++;if($row===[null])continue;if(count($row)!==7)throw new DomainException("Fila $line: columnas incompletas.");$d=array_combine(['folio','recibo','fecha','importe','viajes','estado','origen'],$row);
  $person=Database::query($db,'SELECT id_padron FROM padron WHERE folio=?',[$d['folio']])->fetchColumn();if(!$person)throw new DomainException("Fila $line: folio inexistente.");
  $receipt=Validation::text($d,'recibo',40);$source=Validation::text($d,'origen',100);$trips=Validation::integer($d,'viajes',1,1000);
  $date=DateTimeImmutable::createFromFormat('!Y-m-d',$d['fecha']);if(!$date||$date->format('Y-m-d')!==$d['fecha'])throw new DomainException("Fila $line: fecha inválida.");
  if(!preg_match('/^\d{1,7}(\.\d{1,2})?$/D',$d['importe']))throw new DomainException("Fila $line: importe inválido.");
  if(!in_array($d['estado'],['Entregado','Sin papelería','No se entregó','Fuera de tiempo','Pendiente'],true))throw new DomainException("Fila $line: estado de entrega inválido.");
  Database::query($db,'INSERT INTO servicio_historial(padron_id_padron,recibo,fecha,importe,viajes,estado,origen) VALUES (?,?,?,?,?,?,?)',[$person,$receipt,$d['fecha'],$d['importe'],$trips,$d['estado'],$source]);$count++;
 }return $count;});fclose($fp);echo "$count servicios importados.\n";
}catch(Throwable $e){fwrite(STDERR,'Importación cancelada; no se guardaron filas parciales. '.($e instanceof DomainException?$e->getMessage():'Revisa la conexión o recibos duplicados.')."\n");exit(1);}
