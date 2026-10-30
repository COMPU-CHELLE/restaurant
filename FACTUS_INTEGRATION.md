# Integración Factus en Restaurant

Esta entrega integra la capa reusable de servicios Factus V1/V2 y abstracción EInvoice de Chellnova, registra historial técnico por tenant y añade una primera capa de Factus Pay (auth, crear/consultar recaudos QR). Se mantienen los IDs existentes de Restaurant; no se copian IDs autoincrementales de Chellnova.

## Migraciones

Ejecutar las migraciones de soporte y tenant mediante los comandos del proyecto Restaurant (no usar `php artisan migrate` a ciegas si el despliegue separa las conexiones):
- `2026_10_18_000002_add_factus_invoice_defaults.php` en soporte.
- `2026_10_18_000003_create_factus_pay_services_table.php` en soporte.
- `2026_10_18_000002_create_factus_document_history_tables.php` en cada tenant.

## Factus API

La tabla `factus_services` existente conserva sus credenciales OAuth y añade parámetros fiscales opcionales `numbering_range_id`, `municipality_code`, `payment_form`, `payment_method_code`, `unit_measure_id` y `tax_code`. Las credenciales deben permanecer cifradas y no deben imprimirse en logs. Servicios portados en `app/Services/Factus` y `app/Services/EInvoice`.

## Factus Pay

Crear un registro por empresa en `factus_pay_services` con `environment`, `base_url`, `email`, `password` y `active`. Usar `config('factus.pay_defaults')` para elegir el host oficial, nunca introducir un host arbitrario. Variable `FACTUS_PAY_ENVIRONMENT=sandbox|production`. La API de Pay autentica con `POST /auth`, crea con `POST /v1/collections` y consulta recaudos; referencia idempotente `REST-{companyId}-{orderId}`. El rango permitido por la documentación actual es $1.000–$12.100.000 COP.

## Importante antes de producción

- La emisión fiscal desde una orden pagada está disponible en `POST /operacion/ordenes/{order}/factus/emitir`; requiere cliente asociado, rango de numeración y código de municipio. El historial se guarda en `factus_documents_requests`. Revisar el payload fiscal con Factus sandbox antes de producción.

La API de Pay documenta `POST /auth` con email/contraseña y `POST /v1/collections`; confirmar la ruta exacta de detalle con la versión activa de documentación antes de habilitar consulta en producción.
- La creación de QR no registra un pago en Restaurant. Debe verificarse estado `paid`, luego registrar `payments` en transacción, validar saldo pendiente e idempotencia y cerrar orden/caja; esto no se hace automáticamente en esta entrega para evitar marcar órdenes pagadas por una respuesta no verificada.
- La emisión fiscal completa requiere configurar rangos de numeración y validar los campos obligatorios de adquiriente, impuestos, unidad, municipio y método de pago en cada empresa. No emitir facturas en producción hasta probar sandbox.
- No se importaron controladores de ERP ni seeders que dependen de módulos, permisos y tablas contables inexistentes en Restaurant. `SupportSecuritySeeder` mantiene sus IDs y el árbol actual.
- Revisar las dependencias de los servicios portados que referencian modelos ERP (compras, nómina, documento soporte) antes de activar esos flujos. Se portó la capa de servicios; no se copió el ERP completo.

## Catálogos geográficos (países, zonas y subzonas)

- Se añadió `SupportGeographySeeder`, invocado por `DatabaseSeeder`, que sincroniza los JSON de `storage/app/data/{countries,zones,subzones}` por código único y conserva los `codmov` existentes.
- Los IDs Factus de países se toman del catálogo. Las zonas no incluyen `factus_id` en el JSON fuente, así que se dejan sin inventar; los IDs de subzonas que llegan como `NULL` se normalizan a SQL NULL y no borran IDs válidos previamente sincronizados.
- Las relaciones Eloquent usan las claves foráneas enteras (`zones.codcoi -> countries.codmov` y `subzones.codzon -> zones.codmov`).
- Se añadieron vistas de consulta para soporte: `/soporte/paises`, `/soporte/zonas` y `/soporte/subzonas`.
- Antes de ejecutar el seeder en producción, hacer backup de la base de soporte y verificar que las migraciones de `countries`, `zones` y `subzones` ya estén aplicadas.

## Catálogos: una tabla por catálogo (estructura de Chellnova)

- Cada catálogo de `config/catalogs.php` tiene su propia tabla, con el nombre de la clave (`units`, `document_classes`, `payment_forms`…), en la base de soporte y en la de cada empresa (los exclusivos de la empresa, como `branches`, solo en la empresa). `catalog_items` desaparece.
- Columnas comunes: `codmov`, `code`, `name`, `detail`, `state`, `host`, `codope`, `basdat`, `timestamps`. Los catálogos de Factus suman `code_fe` (lo que se envía a V2) y `factus_id` (lo que se envía a V1). En las empresas, los importables suman `source_id` e `imported_at`.
- Ejemplo: `document_classes | code 11 | Registro civil | code_fe 11 | factus_id 1`.
- `'factus' => false` en la configuración excluye `code_fe`/`factus_id` (monedas, tarifas, cuentas, tipos de movimiento, PDU, CIIU).
- Builders V1 leen `->factus_id`; V2 leen `->code_fe`. `Tenant\Unit` apunta a `units` para `FactusItemMapper`.
- Los modelos `Support\CatalogRecord` y `Tenant\CatalogRecord` son genéricos (`forTable('units')`) y siempre se guardan con `forceFill()`.
- Para agregar un catálogo o una columna: editar `config/catalogs.php` y crear una migración.
- `CatalogSeeder` actualiza las filas existentes. Las unidades solo traen `UND` (factus_id 70, code_fe 94).
- Responsabilidades fiscales: `code_fe` = `O-15`; `factus_id` = `0-15` (forma V1 con cero).

## Factus Pay: soporte, administración y usuario

- **Soporte** `/soporte/factus-pay`: credenciales por empresa y ambiente (contraseña cifrada). El host sale siempre de `config('factus.pay_defaults')`.
- **Tenant**: tabla `factus_pay_collections` (migración `2026_10_19_000004`). Un recaudo `paid` NO registra el pago en caja; `payment_id` se llena al conciliar.
- **Admin** `/admin/recaudos`: listado con filtros, totales y actualización manual del estado.
- **Usuario**: `POST /operacion/factus-pay/recaudos` (`order_id`, `method` QR|BREB) y `GET /operacion/factus-pay/recaudos/{reference}`. El botón en la orden queda pendiente.
- Confirmar en sandbox los nombres de campos de la respuesta (`FactusPayService::normalize()`) y la ruta de consulta.
