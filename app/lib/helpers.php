<?php
declare(strict_types=1);
function e(mixed $v): string { return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function url(string $route='dashboard',array $params=[]): string { global $config; return $config['base_path'].'/index.php?'.http_build_query(['route'=>$route]+$params); }
function asset(string $path): string { global $config; return $config['base_path'].'/'.$path; }
function redirect(string $route,array $params=[]): never { header('Location: '.url($route,$params),true,303); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function can(string $action): bool { $role=(int)($_SESSION['user']['perfil']??0);return $role===1 || ($role===11 && in_array($action,['read','edit'])); }
function authorize(string $action): void { if(!can($action)) {http_response_code(403);throw new DomainException('No tienes permiso para realizar esta operación.');} }
function flash(string $message,string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function icon(string $name): string {
    $paths=['grid'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z','users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0','plus'=>'M12 5v14 M5 12h14','search'=>'M21 21l-5-5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0','drop'=>'M12 3C9 7 5 11 5 15a7 7 0 0 0 14 0c0-4-4-8-7-12z','lock'=>'M5 11h14v10H5z M8 11V7a4 4 0 0 1 8 0v4','arrow'=>'M5 12h14 M13 6l6 6-6 6','clock'=>'M12 8v5l3 2 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0','settings'=>'M4 7h16 M4 17h16 M8 4v6 M16 14v6','logout'=>'M9 21H3V3h6 M9 12h12 M17 8l4 4-4 4','check'=>'M5 12l4 4L19 6','pin'=>'M12 22s8-8 8-14a8 8 0 0 0-16 0c0 6 8 14 8 14z M15 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0','edit'=>'M15 5l4 4 M4 20l4-1L21 6l-4-4L4 15z','menu'=>'M4 6h16 M4 12h16 M4 18h16'];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="'.($paths[$name]??$paths['grid']).'"/></svg>';
}
function statusLabel(mixed $s): string { return ['0'=>'Sin clasificar','1'=>'Activo','2'=>'Bloqueado','3'=>'Baja','4'=>'Estado 4 · por homologar','5'=>'Estado 5 · por homologar'][(string)$s]??'Sin clasificar'; }
function badge(mixed $s): string { return '<span class="badge status-'.e($s).'">'.statusLabel($s).'</span>'; }
function money(mixed $n): string { return '$'.number_format((float)$n,2); }
function view(string $template,array $data=[]): void { global $config; extract($data,EXTR_SKIP); ob_start();require __DIR__.'/../views/'.$template.'.php';$content=ob_get_clean();require __DIR__.'/../views/layout.php'; }
