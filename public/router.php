<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath(__DIR__.$path);
if ($path!=='/' && $file && str_starts_with($file,__DIR__.DIRECTORY_SEPARATOR) && is_file($file) && in_array(pathinfo($file,PATHINFO_EXTENSION),['css','js','png','svg','ico'],true)) return false;
require __DIR__.'/index.php';
