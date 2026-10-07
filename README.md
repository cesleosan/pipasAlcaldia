# PIPAS · Alcaldía Tlalpan

Módulo Padrón en PHP 8.2 y MariaDB/MySQL. Sigue la identidad institucional y los patrones de jefesFamilia, ventanillaUnica y encuestaAlcaldia: guinda `#773357`, dorado `#b08b4f`, navegación lateral, tarjetas claras, formularios por secciones y adaptación móvil.

## Abrir la instalación local

- Dirección: **http://127.0.0.1:8088**.
- Usuario inicial: `root`. Contraseña generada en `storage/acceso-local.txt` (no versionado).
- Reiniciar servicios: `powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1`.
- MariaDB de desarrollo corre en **127.0.0.1:3308** con datos en `storage/mariadb/`. Es una instancia separada; no modifica el servicio MariaDB existente ni las otras aplicaciones.
- La configuración autorizada de jefesFamilia se intentó, pero el servicio existente exige GSSAPI y rechazó la autenticación de la sesión. Por eso se preparó esta instancia de desarrollo independiente.
- El usuario SQL de la aplicación solo tiene permisos DML en `pipas_tlalpan`; sus credenciales están en `app/config.local.php` (ignorado por Git).

Al iniciar por primera vez, agrega **colonias, garzas/cajas, tipos de padrón y autoridades** en Catálogos. El padrón operativo se entrega vacío; los ensayos usan una base distinta y datos ficticios.

## Implementado

- Inicio y cierre de sesión, caducidad, contraseñas con hash y cambio de contraseña.
- ROOT (perfil 1): alta, consulta, edición, bloqueo/reactivación, dotación y autorizaciones extraordinarias, catálogos y alta de operadores.
- ALTAS (perfil 11): consulta y edición. Los permisos también se validan en el servidor.
- Alta transaccional de beneficiario, domicilio y dotación. Folio único derivado del ID asignado por MariaDB.
- CURP: formato, fecha de nacimiento y dígito verificador en navegador y servidor. Esto no consulta RENAPO ni acredita identidad.
- Búsqueda paginada por nombre, folio, CURP y recibo; filtros por estado y colonia.
- Prevención de duplicados de CURP y domicilio normalizado, con acceso a la ficha existente.
- Edición con control de versión para evitar sobrescrituras concurrentes.
- Bloqueo/reactivación con motivo, justificación, operador y fechas.
- Cuota inicial de 1 a 5 viajes; ajustes de 1 a 10, con historial e índice de una sola cuota vigente.
- Autorizaciones extraordinarias sin alterar la cuota regular; autoridad y justificación obligatorias.
- Historial de compras/entregas, dotaciones, bloqueos, extraordinarios y bitácora.
- Mapa interactivo Leaflet con OpenStreetMap: seleccionar, arrastrar o quitar el punto y sincronizar coordenadas. Geocodificación Google configurable; enlace al mapa desde la ficha.
- Asignación de garza y turno semanal a operadores nuevos y existentes, con auditoría. El turno es informativo, admite cruce de medianoche y no modifica perfiles ni restringe el acceso.
- CSS e iconos locales, sin depender de CDN para usar la interfaz.

## Instalación en otro servidor

1. PHP 8.2+ con `pdo_mysql`, `mbstring`, `curl`; MariaDB 10.5+ o MySQL 8.0.16+.
2. Copiar `app/config.example.php` a `app/config.local.php` y configurar credenciales. También se aceptan variables `PIPAS_DB_HOST`, `PIPAS_DB_PORT`, `PIPAS_DB_NAME`, `PIPAS_DB_USER`, `PIPAS_DB_PASS`, `PIPAS_BASE_PATH`, `PIPAS_ENVIRONMENT`, `PIPAS_GOOGLE_MAPS_KEY`.
3. Ejecutar `php scripts/install.php` con una conexión que pueda crear el esquema y una variable `PIPAS_INITIAL_PASSWORD` de al menos 12 caracteres. No se sobrescriben operadores existentes.
4. Cambiar la conexión de ejecución a un usuario SQL con SELECT, INSERT, UPDATE, DELETE solamente sobre esta base.
5. Configurar **public/** como raíz del servidor web. Para desarrollo: `php -S 127.0.0.1:8088 -t public public/router.php`.
6. Usar HTTPS para operación real. `base_path` debe ser vacío para raíz o, por ejemplo, `/pipas` al publicar en un subdirectorio. No debe terminar en `/`.

El instalador no migra una base heredada: crea un esquema nuevo. No ejecutar sobre el sistema legado sin un proceso de migración diseñado y respaldado.

## Integraciones pendientes de datos externos

- **Catálogos oficiales:** se capturan desde administración; no se inventaron colonias, garzas ni personas autorizantes.
- **Google Geocoding:** proporcionar `PIPAS_GOOGLE_MAPS_KEY`. Sin clave, se muestra una explicación y se permite captura manual; no se inventan coordenadas. Referencia: https://developers.google.com/maps/documentation/geocoding/guides-v3/requests-geocoding
- **Caja/entregas:** los PDF no proporcionan el esquema completo de recibo, cobro_caja y entrega_servicio. Se entrega un contrato de importación de solo lectura para la interfaz: `servicio_historial`. No hay cobros, consumo de permisos extraordinarios ni despacho de viajes implementados fuera de Padrón.
- Importar historial: `php scripts/import-history.php archivo.csv`. Columnas exactas: `folio,recibo,fecha,importe,viajes,estado,origen`. Fechas ISO `YYYY-MM-DD`, importe sin separadores de miles; estados: `Entregado`, `Sin papelería`, `No se entregó`, `Fuera de tiempo`, `Pendiente`. La importación completa revierte ante cualquier fila inválida o recibo duplicado. No altera registros existentes.

## Verificación

`php tests/integration.php` prueba transacciones, CURP, duplicados, estados, cuota única, historial, búsquedas y permisos en un esquema nuevo cuyo nombre comienza por `pipas_tlalpan_test_`. En esta máquina usa las credenciales administrativas de la instancia aislada en `storage/dev-db-admin.json`; no ejecuta las pruebas sobre el padrón operativo.

`tests/http_smoke.py` comprueba rutas, sesión, CSRF y permisos contra el servidor de pruebas de puerto 8089. Requiere la base y usuarios de prueba descritos en `docs/VALIDACION.md`.

Consultar `docs/REQUISITOS_Y_DECISIONES.md` para trazabilidad y diferencias documentales, y `docs/VALIDACION.md` para resultados y límites verificados.


## Actualización de turnos y mapa (2026-10-06)

Consulta `docs/DESPLIEGUE_TURNOS_MAPA.md`. Las instalaciones existentes deben aplicar `sql/002_usuario_asignacion.sql` con un usuario de mantenimiento antes de usar Usuarios. El instalador nuevo ya incluye esa tabla.
