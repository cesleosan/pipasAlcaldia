'use strict';
const menu=document.querySelector('#menu-toggle'),sidebar=document.querySelector('#sidebar'),backdrop=document.querySelector('#backdrop');
function closeMenu(){sidebar?.classList.remove('open');if(backdrop)backdrop.hidden=true;menu?.setAttribute('aria-expanded','false');}
menu?.addEventListener('click',()=>{const open=sidebar.classList.toggle('open');backdrop.hidden=!open;menu.setAttribute('aria-expanded',String(open));});
backdrop?.addEventListener('click',closeMenu);document.addEventListener('keydown',e=>{if(e.key==='Escape')closeMenu();});
document.querySelectorAll('[data-password]').forEach(button=>button.addEventListener('click',()=>{const input=document.getElementById(button.dataset.password);const show=input.type==='password';input.type=show?'text':'password';button.textContent=show?'Ocultar':'Mostrar';}));
const tabs=[...document.querySelectorAll('[data-tab]')];
function activateTab(tab){tabs.forEach(t=>{const selected=t===tab;t.setAttribute('aria-selected',String(selected));t.tabIndex=selected?0:-1;document.getElementById('panel-'+t.dataset.tab).hidden=!selected;});}
tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>activateTab(tab));tab.addEventListener('keydown',e=>{let next;if(e.key==='ArrowRight')next=(index+1)%tabs.length;if(e.key==='ArrowLeft')next=(index+tabs.length-1)%tabs.length;if(e.key==='Home')next=0;if(e.key==='End')next=tabs.length-1;if(next!==undefined){e.preventDefault();activateTab(tabs[next]);tabs[next].focus();}});});
function validCurp(value){
 const pattern=/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/;
 if(!pattern.test(value))return false;
 const year=(/\d/.test(value[16])?1900:2000)+Number(value.slice(4,6)),month=Number(value.slice(6,8)),day=Number(value.slice(8,10));
 const date=new Date(year,month-1,day);if(date.getFullYear()!==year||date.getMonth()!==month-1||date.getDate()!==day||date>new Date())return false;
 const dictionary='0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';let sum=0;for(let i=0;i<17;i++)sum+=dictionary.indexOf(value[i])*(18-i);return (10-sum%10)%10===Number(value[17]);
}
const curp=document.querySelector('#curp');
function checkCurp(){curp.value=curp.value.toUpperCase().trim();const valid=validCurp(curp.value);curp.setCustomValidity(curp.value&&!valid?'Revisa la estructura, fecha y dígito verificador de la CURP.':'');const help=document.querySelector('#curp-help');help.textContent=curp.value?(valid?'CURP válida.':'Revisa la estructura, fecha y dígito verificador.'):'18 caracteres, con dígito verificador.';help.className=curp.value?(valid?'valid':'invalid'):'';}
curp?.addEventListener('input',checkCurp);if(curp?.value)checkCurp();
const form=document.querySelector('#beneficiary-form'),geo=document.querySelector('#geocode');
geo?.addEventListener('click',async()=>{
 const status=document.querySelector('#geocode-status');const street=document.querySelector('#calle').value.trim(),number=document.querySelector('#num_ext_mza').value.trim(),colony=document.querySelector('#colonia_id_colonia');
 if(!street||!number||!colony.value){status.textContent='Captura calle, número exterior y colonia para localizar el domicilio.';return;}
 geo.disabled=true;status.textContent='Buscando dirección…';
 try{const body=new FormData();body.append('csrf',form.querySelector('[name=csrf]').value);body.append('address',[street,number,colony.selectedOptions[0].textContent,document.querySelector('#cp').value].filter(Boolean).join(', '));const res=await fetch(form.dataset.geocodeUrl,{method:'POST',body,headers:{Accept:'application/json'}});const data=await res.json();if(!res.ok||data.error)throw Error(data.error||'No fue posible localizar la dirección.');document.querySelector('#latitud').value=Number(data.location.lat).toFixed(7);document.querySelector('#longitud').value=Number(data.location.lng).toFixed(7);document.querySelector('#latitud').dispatchEvent(new Event('change'));status.textContent=(data.partial_match?'Coincidencia parcial: ':'Ubicación sugerida: ')+data.address+'. Revisa las coordenadas antes de guardar.';}
 catch(error){status.textContent=error.message||'Servicio no disponible. Captura las coordenadas manualmente.';}finally{geo.disabled=false;}
});
document.querySelectorAll('form[method=post]').forEach(f=>f.addEventListener('submit',()=>{const submit=f.querySelector('button[type=submit]');if(submit){submit.disabled=true;submit.dataset.original=submit.textContent;submit.textContent=f.closest('.login-form')?'Verificando acceso…':'Guardando…';}}));
window.addEventListener('pageshow',()=>document.querySelectorAll('button[data-original]').forEach(b=>{b.disabled=false;b.textContent=b.dataset.original;}));
const captchaReload=document.querySelector('#captcha-reload');
captchaReload?.addEventListener('click',async()=>{
 const login=captchaReload.closest('form'),status=document.querySelector('#captcha-status'),input=document.querySelector('#captcha-input'),id=document.querySelector('#captcha-id'),submit=login.querySelector('button[type=submit]');
 captchaReload.disabled=true;submit.disabled=true;status.textContent='Generando otro código…';
 try{
  const body=new FormData();body.append('csrf',login.querySelector('[name=csrf]').value);body.append('captcha_id',id.value);
  const response=await fetch(captchaReload.dataset.url,{method:'POST',body,headers:{Accept:'application/json'}});
  if(!response.ok)throw Error('No se pudo cambiar el código. Actualiza la página e intenta de nuevo.');
  const data=await response.json();document.querySelector('#captcha-image').src=data.image;id.value=data.id;input.value='';input.focus();status.textContent='Nuevo código generado. Válido durante 5 minutos.';
 }catch(error){status.textContent=error.message||'No se pudo cambiar el código. Intenta nuevamente.';}
 finally{captchaReload.disabled=false;submit.disabled=false;}
});
document.querySelector('#captcha-input')?.addEventListener('input',event=>{event.target.value=event.target.value.toUpperCase();});

// El domicilio se conserva separado del punto elegido por el operador.
const mapContainer=document.querySelector('#address-map');
if(mapContainer){
 const lat=document.querySelector('#latitud'),lng=document.querySelector('#longitud'),selection=document.querySelector('#map-selection'),loadStatus=document.querySelector('#map-load-status');
 const centerButton=document.querySelector('#map-center'),clearButton=document.querySelector('#map-clear');
 const validPoint=()=>lat.value.trim()!==''&&lng.value.trim()!==''&&Number.isFinite(Number(lat.value))&&Number.isFinite(Number(lng.value))&&Math.abs(Number(lat.value))<=90&&Math.abs(Number(lng.value))<=180;
 if(!window.L){loadStatus.textContent='No fue posible cargar el mapa. Puedes capturar las coordenadas manualmente.';centerButton.disabled=true;clearButton.disabled=true;}
 else{
  const map=L.map(mapContainer,{scrollWheelZoom:false,minZoom:3,maxZoom:19}).setView([19.2879,-99.1677],13);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'}).addTo(map).on('tileerror',()=>{loadStatus.textContent='No se pudo cargar parte del mapa. Revisa tu conexión; las coordenadas capturadas se conservan.';});
  let marker=null;
  const pin=L.divIcon({className:'pipas-map-pin',html:'<span></span>',iconSize:[28,36],iconAnchor:[14,36]});
  function place(latitude,longitude,focus=false){
   lat.value=Number(latitude).toFixed(7);lng.value=Number(longitude).toFixed(7);
   if(!marker){marker=L.marker([latitude,longitude],{icon:pin,draggable:true,autoPan:true,title:'Ubicación del inmueble: arrastra para ajustar'}).addTo(map);marker.on('dragend',()=>{const p=marker.getLatLng();place(p.lat,p.lng);});}
   else marker.setLatLng([latitude,longitude]);
   if(focus)map.setView([latitude,longitude],17);
   selection.textContent='Punto seleccionado · '+lat.value+', '+lng.value;selection.classList.add('is-selected');clearButton.disabled=false;
  }
  function sync(){if(validPoint())place(Number(lat.value),Number(lng.value),true);else{if(marker){map.removeLayer(marker);marker=null;}selection.textContent=lat.value||lng.value?'Completa ambas coordenadas válidas':'Sin punto seleccionado';selection.classList.remove('is-selected');clearButton.disabled=!lat.value&&!lng.value;}}
  map.on('click',e=>place(e.latlng.lat,e.latlng.lng));
  centerButton.addEventListener('click',()=>{const p=map.getCenter();place(p.lat,p.lng);});
  clearButton.addEventListener('click',()=>{lat.value='';lng.value='';sync();});
  lat.addEventListener('change',sync);lng.addEventListener('change',sync);sync();
  new ResizeObserver(()=>map.invalidateSize()).observe(mapContainer);
 }
 ['calle','num_ext_mza','colonia_id_colonia','cp'].forEach(id=>document.getElementById(id)?.addEventListener('change',()=>{if(validPoint())document.querySelector('#geocode-status').textContent='Cambió el domicilio. Revisa que el punto del mapa corresponda a la nueva dirección.';}));
}
