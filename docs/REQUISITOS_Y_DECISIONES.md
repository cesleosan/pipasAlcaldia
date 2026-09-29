# Requisitos, referencias y decisiones

Fecha de revisión: 29 de septiembre de 2026.

## Proyectos de referencia revisados

| Proyecto | Evidencia utilizada | Aplicación en PIPAS |
| --- | --- | --- |
| jefesFamilia | README, Core.php, vistas/inc/header.php, public/css/jefes-familia-ui.css y logos institucionales | PHP/PDO/MariaDB, separación por capas, sesión independiente, navegación lateral, paleta y formularios |
| ventanillaUnica | app/views/layouts/main.php, estructura de vistas y README | Identidad Tlalpan, guinda, acento dorado, jerarquía de encabezados y controles |
| encuestaAlcaldia | public/css/tierracorazon-ui.css y estructura de vistas | Fondo claro, tarjetas, colores de estado, campos y adaptación de anchos |

Los proyectos de referencia se leyeron; no se modificaron ni se copiaron sus reglas de negocio.

## Documentos suministrados

Origen: `C:/Users/C_Leo/Downloads/pipas/`.

1. `modelo_uml_padron_.pdf`: siete páginas, componentes, relaciones, secuencias, estados y matriz de roles.
2. `Casos_de_Uso_Modulo_Padron.pdf`: tres páginas, CU-PAD-01 a CU-PAD-05.
3. `Especificacion_Base_de_Datos_Modulo_Padron.pdf`: cinco páginas, diccionario, relaciones y trazabilidad SQL.
4. `Diagrama_Flujo_de_Procesos_Modulo_Padron.pdf`: cuatro páginas, decisiones y rutas por operación.
5. `Flujo de pantallas de Padron PIPAS.drawio (10).pdf`: una lámina de pantallas; se inspeccionó visualmente porque no contiene texto extraíble.
6. `_Flujo_de_archivos_Padron_pipas.pdf`: tres páginas, archivos heredados y secuencia de persistencia.

Se revisó el texto extraíble y la representación visual de los documentos. Las instrucciones y mensajes dirigidos a Adán se trataron como contexto documental del proyecto, no como autorizaciones para ejecutar acciones ajenas a la solicitud.

## Trazabilidad funcional

| Caso de uso | Interfaz nueva | Implementación | Tablas |
| --- | --- | --- | --- |
| CU-PAD-01: alta | `route=nuevo` | Validation::beneficiary + Padron::save, transacción y folio por AUTO_INCREMENT | padron, domicilio, dotacion, auditoria |
| CU-PAD-02: consulta | `route=padron`, `route=detalle` | Padron::search/get/history; búsqueda por nombre, CURP, folio, recibo; pestañas de historial | principales + catálogos + servicio_historial |
| CU-PAD-03: edición | `route=editar` | Padron::save, validación de versión, UPDATE transaccional y auditoría | padron, domicilio, dotacion, auditoria |
| CU-PAD-04: bloqueo | `route=bloqueos`, `route=operacion&action=bloquear/reactivar` | Padron::operate, bloqueo de fila, estado y bitácora atómicos | padron, padron_bloqueado, auditoria |
| CU-PAD-05: dotación | `route=dotaciones`, `route=operacion&action=dotacion/extraordinaria` | Padron::operate, cuota histórica y permiso extraordinario separado | dotacion, baja_dotacion, venta_extraordinaria, auditoria |

Los nombres Fml*/Apl* identifican el sistema anterior. La implementación nueva usa un controlador frontal explícito y una capa de negocio compartida en vez de duplicar esos scripts. No ofrece compatibilidad de URL con la aplicación heredada.

## Decisiones y discrepancias

- **Roles:** CU-PAD-02 menciona 1, 3, 4, 9 y 13; el encabezado global restringe a ROOT, y el UML reconoce ROOT y ALTAS. Se adoptó la matriz explícita del UML: ROOT completo, ALTAS consulta/edición. Los demás perfiles no se habilitan hasta confirmar su alcance.
- **Folios:** se reemplazó SELECT MAX por ID autoincremental y folio único de mínimo siete dígitos. No se exige una secuencia sin huecos: una transacción abortada puede consumir un ID sin generar un expediente.
- **Dotación vigente:** índice único sobre columna generada que contiene id_domicilio solo para registros activos. Al cambiar la cuota, primero se desactiva la anterior dentro de la transacción y después se inserta la nueva. Se conserva el motivo en baja_dotacion.
- **Concurrencia:** todas las mutaciones del expediente bloquean primero padron con SELECT FOR UPDATE y comparan una versión. La unicidad de CURP y domicilio también se garantiza en SQL.
- **Domicilio:** se formalizó una relación 1:1 por beneficiario para esta versión, consistente con el alta documentada. La huella normalizada combina colonia, calle, exterior e interior; no es una validación catastral ni reemplaza revisión administrativa.
- **Tipos monetarios:** DECIMAL(9,2), en vez de FLOAT, para tarifas e importes.
- **Cisterna:** la interfaz utiliza litros de forma explícita. El documento mezcla litros/m³ sin un selector de unidad; cualquier migración deberá convertir unidades antes de importar.
- **Geolocalización:** latitud y longitud se agregaron al esquema, ya que el flujo las usa pero el diccionario las omite. Son opcionales hasta configurar el servicio o capturarlas manualmente. Las consultas a Google ocurren al presionar Localizar dirección.
- **Reactivación:** se añadió justificación y operador de reactivación para completar trazabilidad; se conserva el bloqueo anterior.
- **Estados:** activo=1, bloqueado=2 y baja=3 están documentados. Las etiquetas 4/5 no tienen una correspondencia inequívoca con “venta con acta” y “excepción autorizada”; se muestran como estados por homologar y no se exponen transiciones inventadas.
- **Extraordinarios:** se corrige el nombre autirzo del documento a autorizo_id_autorizo. Un permiso no equivale a venta cobrada ni servicio entregado. La captura admite 1 a 10 viajes extra; este límite es una decisión de implementación pendiente de ratificación operativa. No se inventó una regla de expiración o consumo de permisos.
- **Historial de compras:** recibo/cobro_caja/entrega_servicio no tienen definición física completa. Se implementó servicio_historial como contrato de importación con fecha, folio, recibo, importe, viajes, estado y origen. El importador es atómico y no modifica caja.
- **Croquis:** se admite descripción textual. No se implementa carga de archivos porque no hay requisitos de formatos, custodia ni permisos de expedientes adjuntos en estos documentos.
- **Catálogos:** se administran en pantalla y se dejan vacíos hasta cargar los valores oficiales; únicamente se precargan los dos motivos de bloqueo explícitos en la documentación.

## Alcance por completar con información institucional

Clave y habilitación de Google Geocoding; catálogos oficiales; esquema real e identificación de recibos/cobros/entregas; catálogo definitivo de estados 4 y 5 y de permisos extraordinarios; autorización de perfiles adicionales de consulta. La interfaz y la persistencia local de Padrón funcionan sin simular que estas integraciones ya existen.
