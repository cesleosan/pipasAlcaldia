<?php
// Instalador CLI para una base nueva; nunca elimina tablas ni reinicia usuarios.
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
try{
 $db=Database::connect($config,false);
 $db->exec('CREATE DATABASE IF NOT EXISTS `'.$config['db_name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 $db->exec('USE `'.$config['db_name'].'`');
 $db->exec(file_get_contents(__DIR__.'/../sql/001_padron.sql'));
 $db->exec(file_get_contents(__DIR__.'/../sql/002_usuario_asignacion.sql'));
 if((int)$db->query('SELECT COUNT(*) FROM usuario')->fetchColumn()===0){
  $password=getenv('PIPAS_INITIAL_PASSWORD');
  if(!$password || strlen($password)<12 || strlen($password)>72){fwrite(STDERR,"Esquema creado. Define PIPAS_INITIAL_PASSWORD (mínimo 12 caracteres) y repite para crear ROOT.\n");exit(1);}
  Database::query($db,'INSERT INTO usuario(nombre,usuario,password_hash,perfil) VALUES (?,?,?,1)',['Administrador PIPAS','root',password_hash($password,PASSWORD_DEFAULT)]);
 }
 echo "Esquema listo. Los datos existentes se conservaron.\n";
}catch(Throwable $e){fwrite(STDERR,"No se pudo instalar. Revisa permisos de creación y conexión: ".$e->getCode()."\n");exit(1);}
