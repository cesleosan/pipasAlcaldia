<header class="government-header">
 <img src="<?= e(asset('logos/alcaldia-tlalpan-guinda.png')) ?>" alt="Alcaldía Tlalpan">
 <div><strong>Alcaldía Tlalpan</strong><span>Sistema de distribución de agua</span></div>
 <span class="government-tag">PLATAFORMA INSTITUCIONAL</span>
</header>
<main class="login-shell" id="main">
 <section class="login-story" aria-labelledby="service-title">
  <div class="service-label"><?= icon('drop') ?><span>GESTIÓN DEL AGUA · TLALPAN</span></div>
  <div class="login-story-content">
   <span class="eyebrow light">PADRÓN DE BENEFICIARIOS</span>
   <h1 id="service-title">Agua para los hogares.<br><em>Gestión cercana<br> a la comunidad.</em></h1>
   <p>Una plataforma para registrar, consultar y dar seguimiento al servicio de pipas en Tlalpan.</p>
   <div class="service-features">
    <div><?= icon('users') ?><span><strong>Padrón centralizado</strong><small>Beneficiarios y domicilios en un solo lugar.</small></span></div>
    <div><?= icon('drop') ?><span><strong>Seguimiento del servicio</strong><small>Consulta de dotaciones y viajes autorizados.</small></span></div>
    <div><?= icon('lock') ?><span><strong>Acceso por perfil</strong><small>Herramientas según las funciones del personal.</small></span></div>
   </div>
  </div>
  <div class="service-signature"><span>ALCALDÍA TLALPAN</span><strong>Gestión de Pipas</strong><span class="signature-rule"></span></div>
 </section>
 <section class="login-panel" aria-labelledby="access-title">
  <div class="login-form">
   <div class="access-heading"><span class="brand-mark"><?= icon('lock') ?></span><span class="eyebrow">ACCESO INSTITUCIONAL</span></div>
   <h2 id="access-title">Bienvenido a PIPAS</h2>
   <p class="muted">Ingresa con tu cuenta para continuar al padrón.</p>
<?php if($error):?><div class="alert danger" role="alert"><?= e($error) ?></div><?php endif;?><form method="post" action="<?= e(url('login')) ?>"><?= csrf() ?><label>Usuario<input name="usuario" autocomplete="username" required maxlength="60" value="<?= e(is_string($_POST['usuario']??null)?$_POST['usuario']:'') ?>" placeholder="Nombre de usuario"></label><label>Contraseña<div class="password-field"><input id="password" type="password" name="password" autocomplete="current-password" required maxlength="200" placeholder="Tu contraseña"><button type="button" class="password-toggle" data-password="password">Mostrar</button></div></label><fieldset class="captcha-box"><legend>Código de seguridad</legend><div class="captcha-row"><button type="button" class="captcha-reload" id="captcha-reload" data-url="<?= e(url('captcha')) ?>" title="Generar otro código" aria-label="Generar otro código de seguridad"><img id="captcha-image" src="<?= e($captcha['image']) ?>" alt="Imagen con cinco caracteres para verificar el acceso" width="180" height="60"><span>↻ Cambiar código</span></button><label for="captcha-input">Escribe el código<input id="captcha-input" name="captcha_input" required minlength="5" maxlength="5" pattern="[2-9A-Ha-h]{5}" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Código" aria-describedby="captcha-status"></label></div><input type="hidden" id="captcha-id" name="captcha_id" value="<?= e($captcha['id']) ?>"><p id="captcha-status" role="status" aria-live="polite">Válido durante 5 minutos. Si no se distingue, cambia el código.</p></fieldset><button class="button primary full" type="submit">Ingresar al sistema <?= icon('arrow') ?></button></form>
   <div class="login-help"><?= icon('lock') ?><span><strong>Uso exclusivo del personal autorizado</strong><br>¿Necesitas una cuenta o ayuda para ingresar?<br>Contacta al administrador del sistema.</span></div>
  </div>
 </section>
</main>
<footer class="government-footer"><span>Alcaldía Tlalpan · Sistema de Pipas</span><span>Padrón de beneficiarios · Uso institucional</span></footer>
