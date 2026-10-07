<?php
$values=$_SERVER['REQUEST_METHOD']==='POST'?$_POST:($editing??[]);
$value=static fn($key)=>is_scalar($values[$key]??null)?(string)$values[$key]:'';
$days=$values['dias']??[];if(is_string($days))$days=explode(',',$days);if(!is_array($days))$days=[];
?>
<section class="page-heading"><div><span class="eyebrow">PIPAS / ADMINISTRACIÓN</span><h1>Equipo y asignaciones</h1><p>Organiza los accesos, las garzas y los turnos del personal.</p></div><span class="count-pill"><?= count($users) ?> operadores</span></section>
<?php if($error):?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif;?>
<div class="team-layout">
<section class="card team-list"><header class="card-heading"><div><h2><?= icon('users') ?> Usuarios del sistema</h2><p>Consulta las funciones y la asignación de cada operador.</p></div></header>
<div class="table-wrap"><table><thead><tr><th>Operador / perfil</th><th>Asignación operativa</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
<?php foreach($users as $u):?><tr><td><strong><?= e($u['nombre']) ?></strong><small class="cell-secondary"><?= e($u['usuario']) ?> · <?= (int)$u['perfil']===1?'ROOT':'ALTAS' ?></small></td><td><span class="garza-label"><?= icon('drop') ?> <?= e($u['garza']??'Sin garza asignada') ?></span><small class="cell-secondary"><?= e(UserAssignment::summary($u)) ?></small></td><td><span class="badge <?= $u['activo']?'green':'orange' ?>"><?= $u['activo']?'Activo':'Inactivo' ?></span></td><td><a class="button secondary tiny" href="<?= e(url('usuarios',['editar'=>$u['id_usuario']])) ?>">Asignar<span class="sr-only"> a <?= e($u['nombre']) ?></span></a></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="card form-section operator-panel"><header class="section-title"><span class="section-number"><?= icon($editing?'settings':'plus') ?></span><div><h2><?= $editing?'Editar asignación':'Nuevo operador' ?></h2><p><?= $editing?e($editing['nombre']):'Datos de acceso y organización del turno.' ?></p></div></header>
<form method="post" class="stack-form" action="<?= e($editing?url('usuarios',['editar'=>$editing['id_usuario']]):url('usuarios')) ?>"><?= csrf() ?>
<?php if(!$editing):?>
<label>Nombre completo<input name="nombre" required maxlength="100" autocomplete="off" value="<?= e($value('nombre')) ?>"></label>
<div class="form-grid two"><label>Usuario<input name="usuario" required maxlength="60" minlength="3" pattern="[a-zA-Z0-9._-]+" autocomplete="off" value="<?= e($value('usuario')) ?>"></label><label>Perfil<select name="perfil"><option value="11" <?= $value('perfil')!=='1'?'selected':'' ?>>ALTAS · Consulta y edición</option><option value="1" <?= $value('perfil')==='1'?'selected':'' ?>>ROOT · Administración</option></select></label></div>
<label>Contraseña inicial<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"><small>Al menos 12 caracteres; máximo 72 bytes.</small></label>
<?php endif;?>
<fieldset class="assignment-fields"><legend><?= icon('drop') ?> Asignación operativa</legend>
<label>Garza<select name="id_caja"><option value="">Sin asignar</option><?php foreach($catalogs['caja'] as $garza):?><option value="<?= e($garza['id']) ?>" <?= $value('id_caja')===(string)$garza['id']?'selected':'' ?>><?= e($garza['nombre']) ?></option><?php endforeach;?></select></label>
<?php if(!$catalogs['caja']):?><p class="field-note">Agrega las garzas en <a href="<?= e(url('catalogos')) ?>">Catálogos</a> para poder asignarlas.</p><?php endif;?>
<fieldset class="week-picker"><legend>Días del turno</legend><div><?php foreach(UserAssignment::DAYS as $day=>$name):?><label><input type="checkbox" name="dias[]" value="<?= $day ?>" <?= in_array((string)$day,array_map('strval',array_filter($days,'is_scalar')),true)?'checked':'' ?>><span><?= $name ?></span></label><?php endforeach;?></div></fieldset>
<div class="form-grid two"><label>Hora de entrada<input type="time" name="hora_inicio" value="<?= e(substr($value('hora_inicio'),0,5)) ?>"></label><label>Hora de salida<input type="time" name="hora_fin" value="<?= e(substr($value('hora_fin'),0,5)) ?>"></label></div>
<p class="field-note">Horario de Ciudad de México. Si la salida es anterior a la entrada, el turno termina al día siguiente. El turno no restringe el acceso.</p>
</fieldset>
<button type="submit" class="button primary full"><?= $editing?'Guardar asignación':'Crear usuario' ?> <?= icon('check') ?></button>
<?php if($editing):?><a class="button secondary full" href="<?= e(url('usuarios')) ?>">Cancelar</a><?php endif;?>
</form></section></div>
