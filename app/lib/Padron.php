<?php
declare(strict_types=1);
final class DuplicateBeneficiary extends DomainException {
    public function __construct(public readonly int $beneficiaryId) {parent::__construct('Ya existe un beneficiario con esa CURP o domicilio. Revisa la ficha existente.');}
}
final class Padron {
    public const CATALOGS=['colonia'=>'id_colonia','caja'=>'id_caja','tipo_padron'=>'id_tipo_padron','autorizo'=>'id_autorizo'];
    public function __construct(public readonly PDO $db) {}
    private function q(string $sql,array $p=[]): PDOStatement { return Database::query($this->db,$sql,$p); }
    public function catalogs(): array {
        $result=[];foreach(self::CATALOGS as $t=>$pk) $result[$t]=$this->q("SELECT $pk AS id,nombre FROM $t WHERE activo=1 ORDER BY nombre")->fetchAll();return $result;
    }
    private function checkCatalogs(array $d): void {
        foreach(['colonia'=>'colonia_id_colonia','caja'=>'id_caja','tipo_padron'=>'tipo_padron_id_tipo_padron'] as $t=>$key) {
            $pk=self::CATALOGS[$t];if(!$this->q("SELECT $pk FROM $t WHERE $pk=? AND activo=1",[$d[$key]])->fetch()) throw new DomainException('Selecciona un valor vigente en '.str_replace('_',' ',$t).'.');
        }
    }
    private function insert(string $table,array $data): int {
        $cols=implode(',',array_keys($data));$values=implode(',',array_fill(0,count($data),'?'));
        $this->q("INSERT INTO $table ($cols) VALUES ($values)",array_values($data));return (int)$this->db->lastInsertId();
    }
    private function update(string $table,array $data,string $pk,int $id): void {
        $set=implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));$this->q("UPDATE $table SET $set WHERE $pk=?",[...array_values($data),$id]);
    }
    private function fields(array $d,array $keys): array { return array_intersect_key($d,array_flip($keys)); }
    public function audit(int $user,?int $id,string $action,array $detail): void {
        $this->insert('auditoria',['padron_id_padron'=>$id,'id_usuario'=>$user,'accion'=>$action,'detalle'=>json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
    private function duplicate(array $d,int $except=0): void {
        $found=$this->q('SELECT p.id_padron FROM padron p LEFT JOIN domicilio d ON d.padron_id_padron=p.id_padron WHERE (p.curp=? OR d.direccion_hash=?) AND p.id_padron<>? LIMIT 1',[$d['curp'],$d['direccion_hash'],$except])->fetch();
        if($found) throw new DuplicateBeneficiary((int)$found['id_padron']);
    }
    public function save(array $input,int $user,?int $id=null): int {
        $d=Validation::beneficiary($input,$id===null);$this->checkCatalogs($d);
        try {return Database::transaction($this->db,function()use($d,$input,$user,$id){
            $old=$id?$this->get($id,true):null;
            if($old && (int)($input['version']??0)!==(int)$old['version']) throw new DomainException('El expediente cambió desde que lo abriste. Recarga la ficha antes de guardar.');
            $this->duplicate($d,$id??0);
            $person=$this->fields($d,['nombre','paterno','materno','nombre_completo','cotitular','num_exp','curp','telefono','email','tipo_padron_id_tipo_padron','id_caja']);
            $address=$this->fields($d,['colonia_id_colonia','cp','calle','num_ext_mza','num_int_lote','num_familias','num_habitantes','observacion','croquis','tiempo_residencia','latitud','longitud','direccion_hash']);
            $quota=$this->fields($d,['dotacion','tamano_cisterna','tarifa']);
            if(!$id){
                $id=$this->insert('padron',$person+['fecha_alta'=>date('Y-m-d'),'creado_por'=>$user]);
                $this->update('padron',['folio'=>str_pad((string)$id,7,'0',STR_PAD_LEFT)],'id_padron',$id);
                $dom=$this->insert('domicilio',$address+['padron_id_padron'=>$id]);
                $this->insert('dotacion',$quota+['domicilio_id_domicilio'=>$dom]);
                $this->audit($user,$id,'Alta de beneficiario',['folio'=>str_pad((string)$id,7,'0',STR_PAD_LEFT)]);
            }else{
                $this->update('padron',$person+['version'=>(int)$old['version']+1,'actualizado_en'=>date('Y-m-d H:i:s')],'id_padron',$id);
                $this->update('domicilio',$address,'id_domicilio',(int)$old['id_domicilio']);
                $this->update('dotacion',$quota,'id_dotacion',(int)$old['id_dotacion']);
                $changes=[];foreach($d as $k=>$v) if((string)($old[$k]??'')!==(string)$v && !in_array($k,['direccion_hash','nombre_completo'])) $changes[$k]=['antes'=>$old[$k]??null,'despues'=>$v];
                $this->audit($user,$id,'Edición de beneficiario',$changes);
            }return $id;
        });}catch(PDOException $e){if(($e->errorInfo[1]??0)===1062){$this->duplicate($d,$id??0);throw new DomainException('El registro ya existe. Actualiza la consulta.');}throw $e;}
    }
    private function base(): string {
        return 'SELECT p.*,d.*,t.*,c.nombre AS colonia_nombre,cj.nombre AS caja_nombre,tp.nombre AS tipo_nombre FROM padron p JOIN domicilio d ON d.padron_id_padron=p.id_padron JOIN dotacion t ON t.domicilio_id_domicilio=d.id_domicilio AND t.status_dotacion=\'1\' JOIN colonia c ON c.id_colonia=d.colonia_id_colonia JOIN caja cj ON cj.id_caja=p.id_caja JOIN tipo_padron tp ON tp.id_tipo_padron=p.tipo_padron_id_tipo_padron';
    }
    public function get(int $id,bool $lock=false): array {
        // Lock the parent first. Every write follows this same locking order.
        if($lock) $this->q('SELECT id_padron FROM padron WHERE id_padron=? FOR UPDATE',[$id]);
        $r=$this->q($this->base().' WHERE p.id_padron=?',[$id])->fetch();if(!$r) throw new DomainException('No se encontró el beneficiario.');return $r;
    }
    public function search(array $f): array {
        $where=[];$p=[];$term=trim((string)($f['q']??''));$by=$f['by']??'nombre';
        if($term!=='') {
            if($by==='folio'){$where[]='(p.folio=? OR p.id_padron=?)';$p[]=$term;$p[]=ctype_digit($term)?(int)$term:0;}
            elseif($by==='recibo'){$where[]='EXISTS (SELECT 1 FROM servicio_historial h WHERE h.padron_id_padron=p.id_padron AND h.recibo=?)';$p[]=$term;}
            elseif($by==='curp'){$where[]='p.curp=?';$p[]=strtoupper($term);}
            else{$where[]='p.nombre_completo LIKE ?';$p[]='%'.str_replace(['!','%','_'],['!!','!%','!_'],$term).'%';$where[count($where)-1].=" ESCAPE '!'";}
        }
        if(in_array($f['status']??'',['0','1','2','3','4','5'],true)){$where[]='p.status_padron=?';$p[]=$f['status'];}
        if((int)($f['colonia']??0)>0){$where[]='d.colonia_id_colonia=?';$p[]=(int)$f['colonia'];}
        $sql=$this->base().($where?' WHERE '.implode(' AND ',$where):'');
        $total=(int)$this->q('SELECT COUNT(*) FROM ('.$sql.') results_count',$p)->fetchColumn();
        $page=max(1,min((int)($f['page']??1),max(1,(int)ceil($total/15))));$offset=($page-1)*15;
        return ['rows'=>$this->q($sql." ORDER BY p.id_padron DESC LIMIT 15 OFFSET $offset",$p)->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/15))];
    }
    public function stats(): array {
        return ['total'=>(int)$this->q('SELECT COUNT(*) FROM padron')->fetchColumn(),'activos'=>(int)$this->q("SELECT COUNT(*) FROM padron WHERE status_padron='1'")->fetchColumn(),'bloqueados'=>(int)$this->q("SELECT COUNT(*) FROM padron WHERE status_padron='2'")->fetchColumn(),'viajes'=>(int)$this->q("SELECT COALESCE(SUM(t.dotacion),0) FROM dotacion t JOIN domicilio d ON d.id_domicilio=t.domicilio_id_domicilio JOIN padron p ON p.id_padron=d.padron_id_padron WHERE t.status_dotacion='1' AND p.status_padron='1'")->fetchColumn()];
    }
    public function history(int $id): array {
        return ['servicios'=>$this->q('SELECT * FROM servicio_historial WHERE padron_id_padron=? ORDER BY fecha DESC,id_servicio DESC',[$id])->fetchAll(),
        'bloqueos'=>$this->q('SELECT b.*,t.nombre AS motivo,u.nombre AS operador FROM padron_bloqueado b JOIN tipo_bloqueo t ON t.id_tipo_bloqueo=b.tipo_bloqueo_id_tipo_bloqueo JOIN usuario u ON u.id_usuario=b.id_usuario WHERE b.padron_id_padron=? ORDER BY b.id_padron_bloqueado DESC',[$id])->fetchAll(),
        'dotaciones'=>$this->q('SELECT t.*,b.desc_baja_dotacion,b.fecha_baja_dotacion FROM dotacion t JOIN domicilio d ON d.id_domicilio=t.domicilio_id_domicilio LEFT JOIN baja_dotacion b ON b.dotacion_id_dotacion=t.id_dotacion WHERE d.padron_id_padron=? ORDER BY t.id_dotacion DESC',[$id])->fetchAll(),
        'extras'=>$this->q('SELECT v.*,a.nombre AS autoridad FROM venta_extraordinaria v JOIN autorizo a ON a.id_autorizo=v.autorizo_id_autorizo JOIN dotacion t ON t.id_dotacion=v.dotacion_id_dotacion JOIN domicilio d ON d.id_domicilio=t.domicilio_id_domicilio WHERE d.padron_id_padron=? ORDER BY v.id_venta_extraordinaria DESC',[$id])->fetchAll(),
        'auditoria'=>$this->q('SELECT a.*,u.nombre AS operador FROM auditoria a JOIN usuario u ON u.id_usuario=a.id_usuario WHERE a.padron_id_padron=? ORDER BY id_auditoria DESC',[$id])->fetchAll()];
    }
    public function operate(int $id,string $action,array $in,int $user): void {
        $reason=Validation::text($in,'justificacion',500);
        Database::transaction($this->db,function()use($id,$action,$in,$user,$reason){
            $r=$this->get($id,true);
            if((int)($in['version']??0)!==(int)$r['version']) throw new DomainException('La ficha cambió. Recárgala y revisa la operación.');
            if($action==='reactivar'){
                if($r['status_padron']!=='2')throw new DomainException('Solo se puede reactivar un beneficiario bloqueado.');
                $s=$this->q("UPDATE padron_bloqueado SET status_padron_bloqueo='2',fecha_padron_desbloqueo=NOW(),reactivado_por=?,justificacion_reactivacion=? WHERE padron_id_padron=? AND status_padron_bloqueo='1'",[$user,$reason,$id]);
                if($s->rowCount()!==1)throw new DomainException('No existe un bloqueo vigente consistente. Revisa el expediente.');
                $this->q("UPDATE padron SET status_padron='1' WHERE id_padron=?",[$id]);
            }else{
                if($r['status_padron']!=='1') throw new DomainException('Esta operación requiere un beneficiario activo.');
                if($action==='bloquear'){
                    $motivo=Validation::integer($in,'motivo',1,2);
                    $this->insert('padron_bloqueado',['padron_id_padron'=>$id,'tipo_bloqueo_id_tipo_bloqueo'=>$motivo,'desc_padron_bloqueado'=>$reason,'id_usuario'=>$user]);
                    $this->q("UPDATE padron SET status_padron='2' WHERE id_padron=?",[$id]);
                }elseif($action==='dotacion'){
                    $n=Validation::integer($in,'dotacion',1,10);
                    if($n===(int)$r['dotacion'])throw new DomainException('La nueva dotación debe ser diferente de la actual.');
                    $this->q("UPDATE dotacion SET status_dotacion='2' WHERE id_dotacion=?",[$r['id_dotacion']]);
                    $this->insert('baja_dotacion',['dotacion_id_dotacion'=>$r['id_dotacion'],'desc_baja_dotacion'=>$reason,'id_usuario'=>$user]);
                    $this->insert('dotacion',['domicilio_id_domicilio'=>$r['id_domicilio'],'dotacion'=>$n,'tamano_cisterna'=>$r['tamano_cisterna'],'tarifa'=>$r['tarifa']]);
                }elseif($action==='extraordinaria'){
                    $authority=Validation::integer($in,'autorizo_id_autorizo',1,2147483647);
                    if(!$this->q('SELECT id_autorizo FROM autorizo WHERE id_autorizo=? AND activo=1',[$authority])->fetch())throw new DomainException('Selecciona una autoridad vigente.');
                    $this->insert('venta_extraordinaria',['dotacion_id_dotacion'=>$r['id_dotacion'],'autorizo_id_autorizo'=>$authority,'num_dotacion'=>Validation::integer($in,'num_dotacion',1,10),'justificacion_venta'=>$reason,'id_usuario'=>$user]);
                }else throw new DomainException('Operación desconocida.');
            }
            $this->q('UPDATE padron SET version=version+1,actualizado_en=NOW() WHERE id_padron=?',[$id]);
            $this->audit($user,$id,['bloquear'=>'Bloqueo','reactivar'=>'Reactivación','dotacion'=>'Cambio de dotación','extraordinaria'=>'Viaje extraordinario'][$action],['justificacion'=>$reason,'dotacion_anterior'=>$r['dotacion'],'dotacion_nueva'=>$in['dotacion']??null,'viajes_extra'=>$in['num_dotacion']??null]);
        });
    }
}
