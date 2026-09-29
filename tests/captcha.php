<?php
declare(strict_types=1);
require __DIR__.'/../app/lib/Captcha.php';
function check(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
$id=str_repeat('a',32);
$seed=static fn()=>['login_captchas'=>[$id=>['hash'=>hash('sha256','ABC23'),'expires'=>time()+300]]];
$s=$seed();check(Captcha::verify($s,$id,' abc23 '),'normalización de respuesta');check(!Captcha::verify($s,$id,'ABC23'),'no permite reutilizar');
$s=$seed();check(!Captcha::verify($s,$id,'ABC24'),'rechaza respuesta incorrecta');check(!Captcha::verify($s,$id,'ABC23'),'consume intento incorrecto');
$s=$seed();$s['login_captchas'][$id]['expires']=time()-1;check(!Captcha::verify($s,$id,'ABC23'),'rechaza código vencido');
$s=[];check(!Captcha::verify($s,$id,'ABC23'),'rechaza otra sesión');check(!Captcha::verify($s,[],[]),'rechaza parámetros no escalares');
$s=$seed();check(!Captcha::verify($s,$id,null),'rechaza respuesta ausente');
$s=$seed();$new=Captcha::issue($s,$id);check(!isset($s['login_captchas'][$id]),'recarga invalida anterior');
$png=base64_decode(substr($new['image'],22),true);$size=getimagesizefromstring($png);check($size[0]===180&&$size[1]===60&&$size['mime']==='image/png','PNG válido');
$other=Captcha::issue($s);check(isset($s['login_captchas'][$new['id']]),'pestañas independientes');
for($i=0;$i<8;$i++)Captcha::issue($s);check(count($s['login_captchas'])===5,'límite de desafíos por sesión');
echo "TOTAL CAPTCHA 12\n";
