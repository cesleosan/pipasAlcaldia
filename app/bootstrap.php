<?php
declare(strict_types=1);
date_default_timezone_set('America/Mexico_City');
$config = ['db_host'=>'127.0.0.1','db_port'=>'3306','db_name'=>'pipas_tlalpan','db_user'=>'pipas_app','db_pass'=>'','base_path'=>'','environment'=>'production','google_maps_key'=>''];
if (is_file(__DIR__.'/config.local.php')) $config = array_replace($config, require __DIR__.'/config.local.php');
foreach (array_keys($config) as $key) { $value = getenv('PIPAS_'.strtoupper($key)); if ($value !== false) $config[$key] = $value; }
require_once __DIR__.'/lib/Database.php';
require_once __DIR__.'/lib/Validation.php';
require_once __DIR__.'/lib/UserAssignment.php';
require_once __DIR__.'/lib/Captcha.php';
require_once __DIR__.'/lib/Padron.php';
require_once __DIR__.'/lib/helpers.php';
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('session.use_strict_mode', '1');
    session_name('PIPAS_TLALPAN_'.strtoupper(substr(hash('sha256',$config['db_name'].'|'.$config['base_path']),0,12)));
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>($config['base_path'] ?: '').'/']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://tile.openstreetmap.org; style-src 'self'; script-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    header('Cache-Control: no-store');
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
