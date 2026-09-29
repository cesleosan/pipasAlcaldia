import urllib.request,urllib.parse,http.cookiejar,re,json
from pathlib import Path
base='http://127.0.0.1:8089/index.php?route='
checks=[]
def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def call(c,route,data=None):
 try:
  res=c.open(base+route,urllib.parse.urlencode(data).encode() if data is not None else None)
 except urllib.error.HTTPError as e:res=e
 return res.status,res.read().decode(),res.geturl()
def token(html):return re.search(r'name="csrf" value="([a-f0-9]+)"',html).group(1)
def check(v,label):
 if not v:raise AssertionError(label)
 checks.append(label);print('PASS',label)
c=client();status,html,url=call(c,'padron');check('route=login' in url,'anonymous redirects to login')
status,html,_=call(c,'login',{'usuario':'pruebas','password':'Test-only-password','csrf':'bad'});check(status==419,'invalid CSRF rejected')
_,html,_=call(c,'login');status,html,url=call(c,'login',{'usuario':'pruebas','password':'Test-only-password','csrf':token(html)});check(status==200 and 'route=dashboard' in url,'ROOT login')
for route in ['padron','nuevo','detalle&id=1','editar&id=1','bloqueos','dotaciones','catalogos','usuarios','cuenta','operacion&id=1&action=dotacion','operacion&id=1&action=extraordinaria']:
 status,html,_=call(c,route);check(status==200 and 'Warning' not in html and 'Fatal error' not in html,'render '+route)
_,html,_=call(c,'nuevo');status,body,_=call(c,'geocode',{'csrf':token(html),'address':'Calle prueba 1'});check(status==503 and 'pendiente' in json.loads(body)['error'],'unconfigured geocoding explicit')
status,html,_=call(c,'logout');check(status==405,'logout requires POST')
a=client();_,html,_=call(a,'login');status,html,url=call(a,'login',{'usuario':'altas_test','password':'Test-only-altas-2026','csrf':token(html)});check('route=dashboard' in url,'ALTAS login')
for route in ['nuevo','bloqueos','dotaciones','catalogos','usuarios','operacion&id=1&action=bloquear']:
 status,html,_=call(a,route);check(status==403,'ALTAS denied '+route)
_,html,_=call(a,'editar&id=1');csrf=token(html);status,_,_=call(a,'operacion&id=1&action=extraordinaria',{'csrf':csrf,'version':7,'num_dotacion':1,'autorizo_id_autorizo':1,'justificacion':'No permitido'});check(status==403,'ALTAS POST denied')
status,html,_=call(a,'editar&id=1');check(status==200,'ALTAS can edit')
status,_,_=call(a,'no-existe');check(status==404,'unknown route 404')
status,_,_=call(a,'editar&id=99999999');check(status==422,'missing beneficiary handled')
print('TOTAL HTTP',len(checks))
Path('tmp/http-results.json').write_text(json.dumps(checks,ensure_ascii=False,indent=2),encoding='utf-8')
