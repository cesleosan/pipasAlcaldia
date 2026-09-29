<?php
require __DIR__.'/../app/bootstrap.php';
$admin=json_decode(file_get_contents(__DIR__.'/../storage/dev-db-admin.json'),true);
$c=array_replace($config,['db_user'=>'root','db_pass'=>$admin['password']]);$db=Database::connect($c,false);
$name=trim(file_get_contents(__DIR__.'/../tmp/test-db-name.txt'));
if(!preg_match('/^pipas_tlalpan_test_[0-9]+$/D',$name))exit(1);
$db->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON `$name`.* TO 'pipas_app'@'127.0.0.1'");
$db->exec("USE `$name`");
Database::query($db,'INSERT INTO usuario(nombre,usuario,password_hash,perfil) VALUES (?,?,?,11)',['ALTAS de prueba','altas_test',password_hash('Test-only-altas-2026',PASSWORD_DEFAULT)]);
Database::query($db,"UPDATE colonia SET nombre='Colonia de demostración' WHERE id_colonia=1");Database::query($db,"UPDATE caja SET nombre='Garza de demostración' WHERE id_caja=1");Database::query($db,"UPDATE tipo_padron SET nombre='Doméstico de prueba' WHERE id_tipo_padron=1");Database::query($db,"UPDATE autorizo SET nombre='Autoridad de prueba' WHERE id_autorizo=1");
echo "Base de pruebas preparada para validación de interfaz.\n";
