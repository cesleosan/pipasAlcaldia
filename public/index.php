<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
$route=is_string($_GET['route']??null)?$_GET['route']:'dashboard';
$error=null;$duplicateId=null;
try {$db=Database::connect($config);$model=new Padron($db);}catch(Throwable $e){http_response_code(503);view('setup',['title'=>'Preparar PIPAS']);exit;}
$method=$_SERVER['REQUEST_METHOD'];
if($method==='POST' && (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'],$_POST['csrf']))) {http_response_code(419);view('error',['title'=>'Sesión de formulario vencida','message'=>'Actualiza la página e intenta de nuevo.']);exit;}
if(isset($_SESSION['user'])){
    if(time()-($_SESSION['last_activity']??0)>3600){unset($_SESSION['user']);flash('Tu sesión expiró. Inicia sesión otra vez.','info');}
    else {$fresh=Database::query($db,'SELECT id_usuario,nombre,usuario,perfil FROM usuario WHERE id_usuario=? AND activo=1',[$_SESSION['user']['id_usuario']])->fetch();if(!$fresh)unset($_SESSION['user']);else $_SESSION['user']=$fresh;}
}
if($route==='login'){
    if($method==='POST'){
        $name=trim(is_string($_POST['usuario']??null)?$_POST['usuario']:'');
        $password=is_string($_POST['password']??null)?$_POST['password']:'';
        $key=hash('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.mb_strtolower($name));
        Database::query($db,"INSERT INTO login_intento(clave,intentos,ventana) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE intentos=IF(ventana<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,intentos+1),ventana=IF(ventana<DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),ventana)",[$key]);
        $attempt=(int)Database::query($db,'SELECT intentos FROM login_intento WHERE clave=?',[$key])->fetchColumn();
        $u=Database::query($db,'SELECT * FROM usuario WHERE usuario=? AND activo=1',[$name])->fetch();
        if($attempt>8){http_response_code(429);$error='Demasiados intentos. Espera 15 minutos antes de intentar de nuevo.';}
        elseif($u && password_verify($password,$u['password_hash'])){session_regenerate_id(true);unset($u['password_hash']);$_SESSION['user']=$u;$_SESSION['last_activity']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));Database::query($db,'DELETE FROM login_intento WHERE clave=?',[$key]);redirect('dashboard');}
        else {$error='Usuario o contraseña incorrectos.';http_response_code(422);}
    }
    if(isset($_SESSION['user']))redirect('dashboard');view('login',['title'=>'Bienvenido','error'=>$error]);exit;
}
if(!isset($_SESSION['user']))redirect('login');
$_SESSION['last_activity']=time();$userId=(int)$_SESSION['user']['id_usuario'];
if(!can('read')){http_response_code(403);view('error',['title'=>'Acceso restringido','message'=>'Tu perfil no tiene acceso al módulo Padrón.']);exit;}
try {
    if($route==='logout'){
        if($method!=='POST'){http_response_code(405);throw new DomainException('Usa el botón Cerrar sesión.');}
        $_SESSION=[];session_destroy();redirect('login');
    }
    if($route==='geocode'){
        authorize('edit');header('Content-Type: application/json; charset=utf-8');
        if($method!=='POST'){http_response_code(405);echo json_encode(['error'=>'Método no permitido.']);exit;}
        if(!$config['google_maps_key']){http_response_code(503);echo json_encode(['error'=>'Geocodificación pendiente de configurar. Puedes capturar las coordenadas manualmente.']);exit;}
        $address=Validation::text($_POST,'address',350);
        $ch=curl_init('https://maps.googleapis.com/maps/api/geocode/json?'.http_build_query(['address'=>$address.', Tlalpan, Ciudad de México, México','key'=>$config['google_maps_key'],'region'=>'mx','language'=>'es']));
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12]);$response=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        $json=$response?json_decode($response,true):null;
        if($status!==200 || ($json['status']??'')!=='OK'){http_response_code(422);echo json_encode(['error'=>'No se pudo ubicar la dirección. Revisa los datos o captura las coordenadas.']);exit;}
        echo json_encode(['location'=>$json['results'][0]['geometry']['location'],'address'=>$json['results'][0]['formatted_address'],'partial_match'=>$json['results'][0]['partial_match']??false]);exit;
    }
    if($route==='dashboard'){
        $recent=Database::query($db,'SELECT a.accion,a.creado_en,p.folio,u.nombre AS operador,a.padron_id_padron FROM auditoria a LEFT JOIN padron p ON p.id_padron=a.padron_id_padron JOIN usuario u ON u.id_usuario=a.id_usuario ORDER BY id_auditoria DESC LIMIT 6')->fetchAll();
        view('dashboard',['title'=>'Resumen del padrón','stats'=>$model->stats(),'recent'=>$recent,'latest'=>$model->search([])['rows'],'catalogs'=>$model->catalogs()]);exit;
    }
    if(in_array($route,['padron','bloqueos','dotaciones'],true)){
        if($route!=='padron')authorize('manage');
        view('listing',['title'=>['padron'=>'Padrón de beneficiarios','bloqueos'=>'Bloqueo y reactivación','dotaciones'=>'Dotación y viajes extraordinarios'][$route],'result'=>$model->search($_GET),'catalogs'=>$model->catalogs()]);exit;
    }
    if(in_array($route,['nuevo','editar'],true)){
        authorize($route==='nuevo'?'create':'edit');$id=$route==='editar'?Validation::integer($_GET,'id',1,2147483647):null;
        $record=$id?$model->get($id):[];
        if($method==='POST'){
            try {$saved=$model->save($_POST,$userId,$id);flash($id?'Datos actualizados correctamente.':'Beneficiario registrado correctamente.');redirect('detalle',['id'=>$saved]);}
            catch(DuplicateBeneficiary $e){$error=$e->getMessage();$duplicateId=$e->beneficiaryId;http_response_code(422);}
            catch(DomainException $e){$error=$e->getMessage();http_response_code(422);}
            $record=array_replace($record,array_intersect_key($_POST,array_flip(['nombre','paterno','materno','cotitular','num_exp','curp','telefono','email','cp','calle','num_ext_mza','num_int_lote','observacion','croquis','tiempo_residencia','tipo_padron_id_tipo_padron','id_caja','colonia_id_colonia','num_familias','num_habitantes','tamano_cisterna','tarifa','dotacion','latitud','longitud','version'])));
        }
        view('form',['title'=>$id?'Editar beneficiario':'Alta de beneficiario','record'=>$record,'id'=>$id,'catalogs'=>$model->catalogs(),'error'=>$error,'duplicateId'=>$duplicateId]);exit;
    }
    if($route==='detalle'){
        $id=Validation::integer($_GET,'id',1,2147483647);$record=$model->get($id);
        view('detail',['title'=>'Ficha del beneficiario','record'=>$record,'history'=>$model->history($id),'catalogs'=>$model->catalogs()]);exit;
    }
    if($route==='operacion'){
        authorize('manage');$id=Validation::integer($_GET,'id',1,2147483647);$action=Validation::text($_GET,'action',25);
        $titles=['bloquear'=>'Bloquear beneficiario','reactivar'=>'Reactivar beneficiario','dotacion'=>'Cambiar dotación','extraordinaria'=>'Autorizar viaje extraordinario'];
        if(!isset($titles[$action]))throw new DomainException('Operación desconocida.');
        if($method==='POST')try{$model->operate($id,$action,$_POST,$userId);flash('Operación registrada correctamente.');redirect('detalle',['id'=>$id]);}catch(DomainException $e){$error=$e->getMessage();http_response_code(422);}
        view('operation',['title'=>$titles[$action],'record'=>$model->get($id),'history'=>$model->history($id),'action'=>$action,'catalogs'=>$model->catalogs(),'error'=>$error]);exit;
    }
    if($route==='catalogos'){
        authorize('manage');
        if($method==='POST')try{
            $type=Validation::text($_POST,'catalogo',20);if(!isset(Padron::CATALOGS[$type]))throw new DomainException('Catálogo inválido.');
            $name=Validation::text($_POST,'nombre',120);
            Database::transaction($db,function()use($db,$type,$name,$model,$userId){Database::query($db,"INSERT INTO $type (nombre) VALUES (?)",[$name]);$model->audit($userId,null,'Alta de catálogo',['catalogo'=>$type,'nombre'=>$name]);});
            flash('Elemento agregado al catálogo.');redirect('catalogos');
        }catch(DomainException $e){$error=$e->getMessage();}catch(PDOException $e){if(($e->errorInfo[1]??0)!==1062)throw $e;$error='Ese elemento ya existe en el catálogo.';}
        view('catalogs',['title'=>'Catálogos operativos','catalogs'=>$model->catalogs(),'error'=>$error]);exit;
    }
    if($route==='usuarios'){
        authorize('manage');
        if($method==='POST')try{
            $name=Validation::text($_POST,'nombre',100);$login=Validation::text($_POST,'usuario',60);$password=Validation::text($_POST,'password',200);
            $role=Validation::integer($_POST,'perfil',1,11);if(!in_array($role,[1,11],true))throw new DomainException('Perfil inválido.');
            if(mb_strlen($password)<12 || strlen($password)>72)throw new DomainException('La contraseña debe tener al menos 12 caracteres y máximo 72 bytes.');
            if(!preg_match('/^[a-zA-Z0-9._-]{3,60}$/D',$login))throw new DomainException('El usuario debe tener 3 a 60 letras, números, puntos o guiones.');
            Database::transaction($db,function()use($db,$name,$login,$password,$role,$model,$userId){Database::query($db,'INSERT INTO usuario(nombre,usuario,password_hash,perfil) VALUES (?,?,?,?)',[$name,$login,password_hash($password,PASSWORD_DEFAULT),$role]);$model->audit($userId,null,'Alta de operador',['usuario'=>$login,'perfil'=>$role]);});
            flash('Operador creado.');redirect('usuarios');
        }catch(DomainException $e){$error=$e->getMessage();}catch(PDOException $e){if(($e->errorInfo[1]??0)!==1062)throw $e;$error='El nombre de usuario ya está ocupado.';}
        view('users',['title'=>'Usuarios del sistema','users'=>Database::query($db,'SELECT id_usuario,nombre,usuario,perfil,activo FROM usuario ORDER BY nombre')->fetchAll(),'error'=>$error]);exit;
    }
    if($route==='cuenta'){
        if($method==='POST')try{
            $current=Validation::text($_POST,'actual',200);$new=Validation::text($_POST,'nueva',200);$confirm=Validation::text($_POST,'confirmacion',200);
            $hash=Database::query($db,'SELECT password_hash FROM usuario WHERE id_usuario=?',[$userId])->fetchColumn();
            if(!password_verify($current,$hash))throw new DomainException('La contraseña actual no es correcta.');
            if(mb_strlen($new)<12 || strlen($new)>72 || $new!==$confirm)throw new DomainException('Usa al menos 12 caracteres, máximo 72 bytes y confirma la misma contraseña.');
            Database::query($db,'UPDATE usuario SET password_hash=? WHERE id_usuario=?',[password_hash($new,PASSWORD_DEFAULT),$userId]);session_regenerate_id(true);flash('Contraseña actualizada.');redirect('cuenta');
        }catch(DomainException $e){$error=$e->getMessage();}
        view('account',['title'=>'Mi cuenta','error'=>$error]);exit;
    }
    http_response_code(404);view('error',['title'=>'Página no encontrada','message'=>'La dirección solicitada no existe. Vuelve al resumen del padrón.']);
}catch(DomainException $e){if(http_response_code()===200)http_response_code(422);view('error',['title'=>'No se pudo completar la operación','message'=>$e->getMessage()]);}
catch(Throwable $e){error_log('PIPAS: '.$e->getMessage());http_response_code(500);view('error',['title'=>'No se pudo completar la operación','message'=>'Ocurrió un error interno. Los cambios pendientes se cancelaron; intenta nuevamente.']);}
