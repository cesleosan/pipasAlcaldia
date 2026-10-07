# Despliegue: turnos, garzas y mapa · 6 de octubre de 2026

## Alcance

Se agrega `usuario_asignacion` dentro de la base exclusiva de PIPAS. No se cambian perfiles, contraseñas ni permisos de los operadores existentes. ROOT puede crear y editar la asignación; ALTAS no administra usuarios. La garza y el turno son opcionales e independientes. Para asignar turno se requieren días y ambas horas. Los turnos que cruzan medianoche se indican con `(+1 día)`; son informativos y no bloquean acceso.

El mapa permite marcar, arrastrar, quitar el punto y editar coordenadas. No reemplaza colonia, calle ni números. Al editar recupera las coordenadas guardadas; sin coordenadas muestra Tlalpan sin colocar un marcador ficticio. Funciona sin clave de Google, con conexión a Internet para cargar la cartografía.

## Actualizar una instalación existente

Desde Git Bash en la computadora:

```bash
git add app public sql/002_usuario_asignacion.sql scripts/install.php tests README.md docs/DESPLIEGUE_TURNOS_MAPA.md docs/VALIDACION.md
git commit -m "Asignar turnos y garzas e integrar mapa de domicilio"
git push origin main
```

En el servidor, descargar primero el código para extraer la migración y aplicarla ANTES de activar el cambio:

```bash
cd /var/www/html/pipasTlalpan
git fetch origin main && git show origin/main:sql/002_usuario_asignacion.sql > /tmp/pipas-002-usuario-asignacion.sql
mariadb -u root -p pipas_tlalpan < /tmp/pipas-002-usuario-asignacion.sql && git pull --ff-only origin main
```

Ejecutar el segundo comando solo si el primero termina correctamente. La migración es aditiva y repetible: crea una tabla con claves foráneas y no elimina ni altera registros existentes. Usa la conexión administrativa únicamente para DDL. La cuenta de ejecución `pipas_app` conserva sus permisos DML sobre `pipas_tlalpan.*`.

No se requieren cambios de Nginx ni reinicios de PHP/MariaDB: Leaflet se sirve mediante las rutas autenticadas `index.php?route=map-js` y `index.php?route=map-css`, compatibles con la publicación actual `/pipas/index.php`. No tocar directorios ni bases de otros sistemas.

## Búsqueda automática por dirección

La instalación aún no tiene `google_maps_key` configurada. El mapa de selección funciona de forma independiente. El botón de búsqueda se muestra cuando existe la clave; hasta entonces se explica cómo ubicar manualmente el inmueble. Para habilitar la búsqueda, configurar una clave válida de Google Geocoding en `app/config.local.php` (campo `google_maps_key`) o en `PIPAS_GOOGLE_MAPS_KEY`, solo en el servidor. No guardar claves en Git. La búsqueda con una clave real queda pendiente de verificación; no se hicieron consultas de domicilios reales.

## Verificación realizada

- 30 comprobaciones de integración del padrón.
- 12 comprobaciones de asignaciones: persistencia, turnos nocturnos, eliminación de asignación, horarios inválidos y garzas inexistentes/inactivas.
- 5 comprobaciones de coordenadas: persistencia, conservación del domicilio, rechazo de valores inválidos o incompletos, eliminación del punto.
- 41 comprobaciones HTTP, incluyendo creación de operador con turno, edición, duplicados, CSRF y denegación de administración a ALTAS.
- Revisión visual con vistas generadas a partir de datos ficticios: operadores, resumen y domicilio; selección por clic y por centro, limpieza y sincronización por teclado. Revisión móvil a 390 px, incluida corrección del desbordamiento de etiquetas de tabla.

Las vistas estáticas de revisión de `tmp/ui-review` no permiten guardar; la aplicación funcional sigue en el puerto 8088. Las pruebas de escritura se hicieron mediante HTTP y PHP contra la base aislada del puerto 8089.

## Dependencias y cartografía

Leaflet 1.9.4 se distribuye localmente en `app/assets` junto con su licencia BSD de dos cláusulas. Fuente: https://github.com/Leaflet/Leaflet/tree/v1.9.4

Cartografía OpenStreetMap con atribución visible, caché normal del navegador y referente de origen. No se envían nombres, CURP ni domicilios escritos a OpenStreetMap ni se implementa descarga masiva. Política: https://operations.osmfoundation.org/policies/tiles/
