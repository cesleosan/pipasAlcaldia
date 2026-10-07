<?php
declare(strict_types=1);
final class UserAssignment {
    public const DAYS = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
    public static function validate(PDO $db,array $input):array {
        $garza=Validation::text($input,'id_caja',12,false);
        $id=$garza===''?null:Validation::integer($input,'id_caja',1,2147483647);
        if($id!==null&&!Database::query($db,'SELECT id_caja FROM caja WHERE id_caja=? AND activo=1',[$id])->fetchColumn())throw new DomainException('Selecciona una garza activa del catálogo.');
        $start=Validation::text($input,'hora_inicio',5,false);$end=Validation::text($input,'hora_fin',5,false);
        $days=$input['dias']??[];
        if(!is_array($days)||count($days)>7)throw new DomainException('Selecciona los días del turno.');
        foreach($days as $day)if(!is_scalar($day)||!preg_match('/^[1-7]$/D',(string)$day))throw new DomainException('Día de turno inválido.');
        $days=array_unique(array_map('intval',$days));sort($days);
        if($start!==''||$end!==''||$days){
            if(!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D',$start)||!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D',$end)||$start===$end||!$days)throw new DomainException('Para asignar un turno, indica los días y dos horas diferentes.');
        }
        return ['id_caja'=>$id,'dias'=>implode(',',$days),'hora_inicio'=>$start?:null,'hora_fin'=>$end?:null];
    }
    public static function save(PDO $db,int $id,array $data):void {
        Database::query($db,'INSERT INTO usuario_asignacion(id_usuario,id_caja,dias,hora_inicio,hora_fin) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE id_caja=VALUES(id_caja),dias=VALUES(dias),hora_inicio=VALUES(hora_inicio),hora_fin=VALUES(hora_fin)',[$id,$data['id_caja'],$data['dias'],$data['hora_inicio'],$data['hora_fin']]);
    }
    public static function summary(array $data):string {
        if(empty($data['hora_inicio']))return 'Sin turno asignado';
        $days=array_map(static fn($d)=>self::DAYS[(int)$d]??'',explode(',',$data['dias']));
        return implode(', ',$days).' · '.substr($data['hora_inicio'],0,5).'–'.substr($data['hora_fin'],0,5).($data['hora_fin']<$data['hora_inicio']?' (+1 día)':'');
    }
}
