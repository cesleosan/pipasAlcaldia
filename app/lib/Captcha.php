<?php
declare(strict_types=1);

/** Captcha local: PNG rasterizado sin dependencias de GD ni servicios externos. */
final class Captcha {
    private const GLYPHS = [
        '2'=>['01110','10001','00001','00010','00100','01000','11111'],
        '3'=>['11110','00001','00001','01110','00001','00001','11110'],
        '4'=>['00010','00110','01010','10010','11111','00010','00010'],
        '5'=>['11111','10000','10000','11110','00001','00001','11110'],
        '6'=>['01110','10000','10000','11110','10001','10001','01110'],
        '7'=>['11111','00001','00010','00100','01000','01000','01000'],
        '8'=>['01110','10001','10001','01110','10001','10001','01110'],
        '9'=>['01110','10001','10001','01111','00001','00001','01110'],
        'A'=>['01110','10001','10001','11111','10001','10001','10001'],
        'B'=>['11110','10001','10001','11110','10001','10001','11110'],
        'C'=>['01111','10000','10000','10000','10000','10000','01111'],
        'D'=>['11110','10001','10001','10001','10001','10001','11110'],
        'E'=>['11111','10000','10000','11110','10000','10000','11111'],
        'F'=>['11111','10000','10000','11110','10000','10000','10000'],
        'G'=>['01111','10000','10000','10111','10001','10001','01111'],
        'H'=>['10001','10001','10001','11111','10001','10001','10001'],
    ];

    public static function issue(array &$session, mixed $previous = null): array {
        $challenges = $session['login_captchas'] ?? [];
        foreach ($challenges as $id=>$challenge) {
            if (($challenge['expires'] ?? 0) <= time() || (is_string($previous) && $id === $previous)) unset($challenges[$id]);
        }
        while (count($challenges) >= 5) array_shift($challenges);
        $alphabet = implode('', array_keys(self::GLYPHS)); $code = '';
        for ($i=0; $i<5; $i++) $code .= $alphabet[random_int(0, strlen($alphabet)-1)];
        $id = bin2hex(random_bytes(16));
        $png = self::png($code);
        $challenges[$id] = ['hash'=>hash('sha256', $code), 'expires'=>time()+300];
        $session['login_captchas'] = $challenges;
        return ['id'=>$id, 'image'=>'data:image/png;base64,'.base64_encode($png)];
    }

    public static function verify(array &$session, mixed $id, mixed $answer): bool {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D', $id)) return false;
        $challenge = $session['login_captchas'][$id] ?? null;
        unset($session['login_captchas'][$id]); // Consumir también los intentos fallidos.
        if (!$challenge || ($challenge['expires'] ?? 0) <= time() || !is_string($answer)) return false;
        $answer = strtoupper(trim($answer));
        return preg_match('/^[2-9A-H]{5}$/D', $answer) === 1
            && hash_equals($challenge['hash'], hash('sha256', $answer));
    }

    private static function png(string $code): string {
        $width=180; $height=60;
        $rows=array_fill(0,$height,str_repeat("\xf8\xf3\xf6",$width));
        $pixel=static function(int $x,int $y,string $color)use(&$rows,$width,$height):void{
            if($x<0||$x>=$width||$y<0||$y>=$height)return;
            for($c=0;$c<3;$c++)$rows[$y][$x*3+$c]=$color[$c];
        };
        for($i=0;$i<9;$i++){
            $y=random_int(0,59);$slope=random_int(-20,20)/180;
            for($x=0;$x<$width;$x++)$pixel($x,(int)round($y+$x*$slope),"\xd9\xc9\xd2");
        }
        for($i=0;$i<5;$i++){
            $originX=15+$i*32+random_int(-2,2);$originY=random_int(12,19);
            foreach(self::GLYPHS[$code[$i]] as $y=>$line)for($x=0;$x<5;$x++)if($line[$x]==='1'){
                for($dy=0;$dy<4;$dy++)for($dx=0;$dx<4;$dx++)$pixel($originX+$x*4+$dx,$originY+$y*4+$dy,"\x77\x33\x57");
            }
        }
        $raw='';foreach($rows as $row)$raw.="\0".$row;
        $chunk=static fn(string $type,string $data):string=>pack('N',strlen($data)).$type.$data.pack('N',crc32($type.$data));
        return "\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',$width,$height,8,2,0,0,0)).$chunk('IDAT',gzcompress($raw)).$chunk('IEND','');
    }
}
