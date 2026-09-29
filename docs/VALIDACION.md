# Validación de la entrega

29 de septiembre de 2026. Entorno local: PHP 8.2.27 y MariaDB 11.8.6, instancia exclusiva en puerto 3308.

## Resultados

- **30 pruebas de integración de negocio correctas** (`tests/integration.php`).
- **27 comprobaciones HTTP correctas** (`tests/http_smoke.py`).
- **3 pruebas de importación correctas** (`tests/import_smoke.php`).
- Sintaxis PHP validada en app, public, scripts y tests; JavaScript validado con `node --check`.
- Alta real a través del formulario en navegador con datos ficticios: generó folio, ficha, domicilio y cuota de 2 viajes.
- Cambio de cuota desde la interfaz: pasó de 2 a 4 viajes y la pestaña Dotaciones mostró la anterior histórica y la nueva vigente.
- Validación de CURP en el navegador; envío y redirección tras guardar.
- Revisión móvil en 390 px: dashboard y ficha sin desbordamiento horizontal del documento; tablas con desplazamiento propio.
- Verificación de estructura de escritorio en 1440 px y revisión visual del panel accesible desde el navegador integrado.

Las pruebas se ejecutaron en una base `pipas_tlalpan_test_*`. El padrón `pipas_tlalpan` se mantiene vacío, salvo su usuario ROOT; no contiene beneficiarios ni catálogos de demostración.

## Cobertura relevante

CURP válida, dígito erróneo, fecha inexistente; duplicado por CURP; domicilio normalizado; alta atómica; edición; conflicto de versión; bloqueo; exclusión de bloqueados en indicador de cuota; rechazo de extraordinario a bloqueado; reactivación y fecha; cambio de cuota; restricción SQL de cuota vigente única; extra independiente de cuota; búsqueda por folio, nombre y recibo; parametrización SQL; cuota fuera de rango; reversión ante fallo del tercer INSERT; reversión del cambio de cuota ante fallo; permisos de ROOT/ALTAS.

HTTP: redirección de anónimos, rechazo CSRF, acceso ROOT, render de las rutas principales, respuesta explícita sin clave de mapas, cierre de sesión solo por POST, denegación de páginas y POST administrativos a ALTAS, edición permitida a ALTAS, ruta desconocida y beneficiario inexistente controlados.

Importación: fallo en una fila posterior revierte toda la carga; carga válida consultable por recibo; una repetición no crea recibos duplicados.

## Repetición en esta máquina

1. Iniciar la instancia local con `scripts/start-local.ps1`.
2. Ejecutar `php tests/integration.php` (crea un esquema nuevo, no borra ni modifica datos operativos).
3. Ejecutar `php tests/prepare-ui.php` para usuarios y catálogos ficticios del esquema recién creado.
4. Iniciar `scripts/start-test.ps1` y abrir puerto 8089. Usuarios de ensayo: `pruebas` / `Test-only-password` y `altas_test` / `Test-only-altas-2026`. Son exclusivos de la base de pruebas.
5. Ejecutar `python tests/http_smoke.py` y `php tests/import_smoke.php`.

Si el servidor de pruebas estaba abierto con una base anterior, detener solo ese servidor antes de repetir el paso 4. No usar los usuarios de ensayo ni la cuenta SQL administrativa en producción.

## Límites de la verificación

No se verificó una llamada real a Google porque no se proporcionó clave. Tampoco se probó una conexión al sistema heredado de caja/entregas, cuya estructura no fue entregada. No se ejecutó una migración de datos reales ni un despliegue público. El servidor integrado de PHP se usa solo para revisión local.

## Captcha de inicio de sesión (2026-09-29)

El acceso exige un código de cinco caracteres, con imagen PNG local, recarga mediante POST con CSRF, vencimiento de cinco minutos y consumo en cada intento. La imagen se genera con zlib sin requerir GD ni servicios externos. Hasta cinco desafíos por sesión permiten usar distintas pestañas. Se conserva el límite de intentos de autenticación.

Validación: `php tests/captcha.php` (12 comprobaciones) y `python tests/http_smoke.py` contra el servidor aislado de pruebas (33 comprobaciones, incluidos ROOT y ALTAS). `tests/captcha_fixture.php` solo funciona por CLI y únicamente modifica sesiones correspondientes a la base de pruebas indicada en `tmp/test-db-name.txt`; permite probar respuestas conocidas sin agregar excepciones a la autenticación de la aplicación. Se verificaron la imagen y el botón Cambiar código en el navegador local.

Despliegue: actualizar el código de PIPAS. No requiere migraciones, extensiones adicionales ni cambios a Nginx. El refresco utiliza `index.php?route=captcha`, compatible con la ruta publicada `/pipas/index.php`. CSS y JavaScript incluyen versión por fecha del archivo para renovar la caché.
