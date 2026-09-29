<?php
declare(strict_types=1);
final class Validation {
    public static function curp(string $curp): bool {
        if (!preg_match('/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/D',$curp)) return false;
        $year=(ctype_digit($curp[16])?1900:2000)+(int)substr($curp,4,2);
        if (!checkdate((int)substr($curp,6,2),(int)substr($curp,8,2),$year) || sprintf('%04d-%s-%s',$year,substr($curp,6,2),substr($curp,8,2))>date('Y-m-d')) return false;
        $dictionary='0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';$sum=0;
        for($i=0;$i<17;$i++) $sum+=(int)mb_strpos($dictionary,$curp[$i])* (18-$i);
        return (10-$sum%10)%10===(int)$curp[17];
    }
    public static function text(array $input,string $key,int $max,bool $required=true): string {
        $v=trim(is_scalar($input[$key]??null)?(string)$input[$key]:'');
        if (($required && $v==='') || mb_strlen($v)>$max) throw new DomainException("Revisa el campo «".str_replace('_',' ',$key)."» (máximo $max caracteres).");
        return $v;
    }
    public static function integer(array $in,string $key,int $min,int $max): int {
        $v=filter_var($in[$key]??null,FILTER_VALIDATE_INT);
        if ($v===false || $v<$min || $v>$max) throw new DomainException("Revisa «".str_replace('_',' ',$key)."»: debe estar entre $min y $max.");return $v;
    }
    public static function beneficiary(array $in,bool $new): array {
        $d=[];
        foreach(['nombre'=>45,'paterno'=>45,'materno'=>45,'cotitular'=>300,'num_exp'=>11,'curp'=>18,'telefono'=>10,'email'=>100,'cp'=>5,'calle'=>100,'num_ext_mza'=>50,'num_int_lote'=>50,'observacion'=>300,'croquis'=>300,'tiempo_residencia'=>20] as $k=>$max) $d[$k]=self::text($in,$k,$max,in_array($k,['nombre','paterno','curp','calle','num_ext_mza']));
        $d['curp']=strtoupper($d['curp']);
        if (!self::curp($d['curp'])) throw new DomainException('La CURP no tiene una estructura, fecha o dígito verificador válido.');
        if ($d['telefono']!=='' && !preg_match('/^\d{10}$/D',$d['telefono'])) throw new DomainException('El teléfono debe tener 10 dígitos.');
        if ($d['email']!=='' && !filter_var($d['email'],FILTER_VALIDATE_EMAIL)) throw new DomainException('El correo electrónico no es válido.');
        if ($d['cp']!=='' && !preg_match('/^\d{5}$/D',$d['cp'])) throw new DomainException('El código postal debe tener 5 dígitos.');
        foreach(['tipo_padron_id_tipo_padron','id_caja','colonia_id_colonia'] as $k) $d[$k]=self::integer($in,$k,1,2147483647);
        foreach(['num_familias','num_habitantes'] as $k) $d[$k]=self::integer($in,$k,1,100000);
        if ($d['num_habitantes']<$d['num_familias']) throw new DomainException('Los habitantes no pueden ser menos que las familias.');
        $d['tamano_cisterna']=self::integer($in,'tamano_cisterna',1,10000000);
        $tarifa=self::text($in,'tarifa',12);
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/D',$tarifa)) throw new DomainException('La tarifa debe ser un importe positivo con máximo dos decimales.');
        $d['tarifa']=number_format((float)$tarifa,2,'.','');
        if($new) $d['dotacion']=self::integer($in,'dotacion',1,5);
        foreach(['latitud'=>90,'longitud'=>180] as $k=>$limit) {
            $v=self::text($in,$k,25,false);$d[$k]=$v===''?null:filter_var($v,FILTER_VALIDATE_FLOAT);
            if ($d[$k]===false || ($d[$k]!==null && abs($d[$k])>$limit)) throw new DomainException('Coordenadas inválidas.');
        }
        if (($d['latitud']===null)!==($d['longitud']===null)) throw new DomainException('Captura ambas coordenadas o deja ambas vacías.');
        $d['nombre_completo']=trim($d['paterno'].' '.$d['materno'].' '.$d['nombre']);
        $address=implode('|',[$d['colonia_id_colonia'],$d['calle'],$d['num_ext_mza'],$d['num_int_lote']]);
        $address=strtr(mb_strtoupper($address),['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U']);
        $d['direccion_hash']=hash('sha256',preg_replace('/[\s.,#]+/u','',$address));
        return $d;
    }
}
