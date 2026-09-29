<?php
declare(strict_types=1);
// Fixture exclusiva de CLI para la base aislada de pruebas; nunca se publica como ruta web.
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$database=trim(file_get_contents(__DIR__.'/../tmp/test-db-name.txt'));
if(!preg_match('/^pipas_tlalpan_test_[0-9]+$/D',$database))throw new RuntimeException('Base de pruebas inválida');
$expected='PIPAS_TLALPAN_'.strtoupper(substr(hash('sha256',$database.'|'.$config['base_path']),0,12));
if(($argv[1]??'')!==$expected||!preg_match('/^[a-zA-Z0-9,-]{16,128}$/D',$argv[2]??'')||!preg_match('/^[a-f0-9]{32}$/D',$argv[3]??''))throw new RuntimeException('Sesión de pruebas inválida');
session_name($expected);session_id($argv[2]);session_start();
if(!isset($_SESSION['login_captchas'][$argv[3]]))throw new RuntimeException('Desafío inexistente');
$_SESSION['login_captchas'][$argv[3]]=['hash'=>hash('sha256','ABC23'),'expires'=>time()+300];
session_write_close();
