<?php

namespace Database\Seeders;

use App\Models\Support\Soflog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos de Factus:
 *  - factus_id   => id de la API v1 (en métodos/formas de pago v1 el "id" es el mismo código)
 *  - factus_code => código DIAN que usa la API v2
 *
 * Es idempotente: si el registro ya existe solo sincroniza las columnas factus_*.
 */
class CatalogSeeder extends Seeder
{
    private array $audit = [
        'host'   => 'SYSTEM',
        'codope' => 'SOPORTE',
        'basdat' => 'chellnova',
    ];

    public function run(): void
    {
        $this->seedDocumentClasses();
        $this->seedUnits();
        $this->seedPaymentForms();
        $this->seedEconomicActivities();
        $this->seedCatalogItems();
        $this->seedTypes();
    }

    // ------------------------------------------------------------------
    // Tipos de documento de identidad
    // ------------------------------------------------------------------
    private function seedDocumentClasses(): void
    {
        $rows = [
            // code local, factus_code (v2), factus_id (v1), name
            ['RC',      '11', '1',  'Registro civil'],
            ['TI',      '12', '2',  'Tarjeta de identidad'],
            ['CC',      '13', '3',  'Cédula de ciudadanía'],
            ['TE',      '21', '4',  'Tarjeta de extranjería'],
            ['CE',      '22', '5',  'Cédula de extranjería'],
            ['NIT',     '31', '6',  'NIT'],
            ['PAS',     '41', '7',  'Pasaporte'],
            ['DE',      '42', '8',  'Documento de identificación extranjero'],
            ['PEP',     '47', '9',  'PEP'],
            ['PPT',     '48', null, 'Permiso Protección Temporal'], // solo existe en v2
            ['NIT_EXT', '50', '10', 'NIT otro país'],
            ['NUIP',    '91', '11', 'NUIP'],
        ];

        $count = 0;
        foreach ($rows as [$code, $fCode, $fId, $name]) {
            $this->upsert(
                'document_classes',
                ['code' => $code],
                [
                    'factus_code' => $fCode,
                    'factus_id'   => $fId,
                    'name'        => $name,
                    'detail'      => mb_strtoupper($name),
                    'state'       => 1,
                ],
                ['factus_code', 'factus_id'],
                "clase de documento: {$name}"
            );
            $count++;
        }

        $this->command->info("✅ Clases de documentos procesadas: {$count}");
    }

    // ------------------------------------------------------------------
    // Unidades de medida
    // ------------------------------------------------------------------
    private function seedUnits(): void
    {
        // [code local, factus_code (v2), factus_id (v1), name, parent factus_code, factor]
        // Los padres deben ir antes que sus hijas.
        $rows = [
            ['UND', '94',  '70',  'Unidad',        null,  1],
            ['KG',  'KGM', '414', 'Kilogramo',     null,  1],
            ['LB',  'LBR', '449', 'Libra',         'KGM', 0.4536],
            ['M',   'MTR', '512', 'Metro',         null,  1],
            ['GAL', 'GLL', '874', 'Galón',         null,  1],
            ['GR',  'GRM', null,  'Gramo',         'KGM', 0.001],
            ['TON', 'TNE', null,  'Tonelada',      'KGM', 1000],
            ['CM',  'CMT', null,  'Centímetro',    'MTR', 0.01],
            ['MM',  'MMT', null,  'Milímetro',     'MTR', 0.001],
            ['KM',  'KTM', null,  'Kilómetro',     'MTR', 1000],
            ['DOC', 'DZN', null,  'Docena',        '94',  12],
            ['PAR', 'PR',  null,  'Par',           '94',  2],
            ['LT',  'LTR', null,  'Litro',         null,  1],
            ['M2',  'MTK', null,  'Metro cuadrado',null,  1],
            ['M3',  'MTQ', null,  'Metro cúbico',  null,  1],
            ['HR',  'HUR', null,  'Hora',          null,  1],
            ['MIN', 'MIN', null,  'Minuto',        null,  1],
            ['DIA', 'DAY', null,  'Día',           null,  1],
            ['SEM', 'WEE', null,  'Semana',        null,  1],
            ['KWH', 'KWH', null,  'Kilovatios hora', null, 1],
            ['CJ',  'BX',  null,  'Caja',          null,  1],
            ['PQT', 'PA',  null,  'Paquete',       null,  1],
            ['SET', 'SET', null,  'Conjunto',      null,  1],
            ['RM',  'RM',  null,  'Resma',         null,  1],
        ];

        $count = 0;
        foreach ($rows as [$code, $fCode, $fId, $name, $parent, $factor]) {
            $parentId = $parent
                ? DB::connection('mysql')->table('units')->where('factus_code', $parent)->value('codmov')
                : null;

            $this->upsert(
                'units',
                ['factus_code' => $fCode],
                [
                    'code'           => $code,
                    'factus_id'      => $fId,
                    'name'           => $name,
                    'detail'         => mb_strtoupper($name),
                    'parent_unit_id' => $parentId,
                    'factor'         => $factor,
                    'state'          => 1,
                ],
                ['factus_id'],
                "unidad: {$name}"
            );
            $count++;
        }

        // Catálogo completo v2 (unitMeasures): database/data/factus_unit_measures.txt  (formato: codigo|nombre)
        $file = storage_path('app/data/factus_unit_measures.txt');
        $bulk = 0;

        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                [$fCode, $name] = array_map('trim', explode('|', $line, 2));

                $res = $this->upsert(
                    'units',
                    ['factus_code' => $fCode],
                    [
                        'code'   => $fCode,
                        'name'   => mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1),
                        'detail' => mb_strtoupper($name),
                        'state'  => 1,
                    ],
                    [],          // si ya existe (curadas) no se toca
                    "unidad: {$name}",
                    false        // sin Soflog por fila; abajo va un solo registro resumen
                );
                $bulk += $res === 'created' ? 1 : 0;
            }

            if ($bulk) {
                $this->log('CREATE', "Se han cargado {$bulk} unidades de medida del catálogo Factus", null, ['total' => $bulk]);
            }
            $this->command->info("   + unidades Factus v2 cargadas: {$bulk}");
        } else {
            $this->command->warn("   ! No existe {$file}; solo se cargaron las unidades curadas.");
        }

        $this->command->info("✅ Unidades de medida procesadas: {$count}");
    }

    // ------------------------------------------------------------------
    // Actividades económicas (CIIU)
    // ------------------------------------------------------------------
    private function seedEconomicActivities(): void
    {
        // database/data/economic_activities.txt  (formato: codigo_ciiu|nombre)
        $file = storage_path('app/data/economic_activities.txt');

        if (!is_file($file)) {
            $this->command->warn("   ! No existe {$file}; se omiten las actividades económicas.");
            return;
        }

        $created = 0;
        $total   = 0;

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            [$ciiu, $name] = array_map('trim', explode('|', $line, 2));

            $res = $this->upsert(
                'economic_activity',
                ['code' => 'CIIU_' . $ciiu],
                [
                    'code_fe' => $ciiu,
                    'name'    => $name,
                    'detail'  => mb_strtoupper($name),
                    'state'   => 1,
                ],
                [],      // si ya existe no se toca (puede tener porcentajes/cuentas configuradas)
                "actividad económica: {$name}",
                false    // un solo Soflog resumen al final
            );

            $total++;
            $created += $res === 'created' ? 1 : 0;
        }

        if ($created) {
            $this->log('CREATE', "Se han cargado {$created} actividades económicas CIIU", null, ['total' => $created]);
        }

        $this->command->info("✅ Actividades económicas procesadas: {$total} (nuevas: {$created})");
    }

    // ------------------------------------------------------------------
    // Formas y métodos de pago
    // ------------------------------------------------------------------
    private function seedPaymentForms(): void
    {
        // forpay 1 = método de pago | 0 = forma de pago (contado / crédito)
        $rows = [
            ['FP001', 'EFECTIVO',                'Pago en efectivo',              '10',  1],
            ['FP002', 'CONSIGNACION',            'Pago por consignacion',         '42',  1],
            ['FP003', 'CHEQUE',                  'Pago con cheque',               '20',  1],
            ['FP004', 'TRANSFERENCIA',           'Pago por transferencia bancaria','47', 1],
            ['FP005', 'TARJETA CREDITO',         'Pago con tarjeta credito',      '48',  1],
            ['FP006', 'TARJETA DEBITO',          'Pago con tarjeta debito',       '49',  1],
            ['FP007', 'BONOS',                   'Pago con bonos',                '71',  1],
            ['FP008', 'VALES',                   'Pago con vales',                '72',  1],
            ['FP009', 'MEDIO DE PAGO NO DEFINIDO','Medio de pago no definido',    '1',   1],
            ['FP010', 'OTRO',                    'Otro medio de pago',            'ZZZ', 1],
            ['MP001', 'CONTADO',                 'Pago Contado',                  '1',   0],
            ['MP002', 'CREDITO',                 'Pago Crédito',                  '2',   0],
        ];

        $count = 0;
        foreach ($rows as [$code, $name, $detail, $factus, $forpay]) {
            $this->upsert(
                'payment_forms',
                ['code' => $code],
                [
                    'name'        => $name,
                    'detail'      => $detail,
                    'factus_code' => $factus,
                    'factus_id'   => $factus, // en v1 el id de pago coincide con el código
                    'forpay'      => $forpay,
                    'state'       => 1,
                ],
                ['factus_code', 'factus_id'],
                "forma de pago: {$name}"
            );
            $count++;
        }

        $this->command->info("✅ Formas de pago procesadas: {$count}");
    }

    // ------------------------------------------------------------------
    // Catálogo genérico: todas las tablas de referencia de Factus (v1 + v2)
    // ------------------------------------------------------------------
    private function seedCatalogItems(): void
    {
        // type => [ [code v2, name, id v1|null], ... ]
        // En v1 las tablas "por código" usan el código como id, por eso se repite.
        $catalogs = [
            'legal_organization' => [['1','Persona Jurídica','1'], ['2','Persona Natural','2']],
            'customer_tribute'   => [['01','IVA','18'], ['ZZ','No aplica','21']],

            'invoice_document_type' => [
                ['01','Factura electrónica de venta','01'],
                ['03','Instrumento electrónico de transmisión - tipo 03','03'],
            ],
            'operation_type' => [
                ['10','Estándar','10'], ['11','Mandatos','11'], ['12','Transporte de carga','12'],
                ['SS-CUFE','Sector Salud - CUFE',null], ['SS-Reporte','Sector Salud - Reporte',null],
                ['SS-SinAporte','Sector Salud - Sin Aporte',null],
                ['SS-Recaudo','Sector Salud - Recaudo de cuotas moderadoras o copagos',null],
            ],
            'credit_note_correction' => [
                ['1','Devolución parcial de los bienes y/o no aceptación parcial del servicio','1'],
                ['2','Anulación de factura electrónica','2'],
                ['3','Rebaja o descuento parcial o total','3'],
                ['4','Ajuste de precio','4'],
                ['5','Descuento comercial por pronto pago','5'],
                ['6','Descuento comercial por volumen de ventas','6'],
            ],
            'credit_note_operation' => [
                ['20','Nota Crédito que referencia una factura electrónica','20'],
                ['22','Nota Crédito sin referencia a una factura electrónica','22'],
            ],
            'debit_note_correction' => [
                ['1','Intereses',null], ['2','Gastos por cobrar',null], ['3','Cambio del valor',null], ['4','Otros',null],
            ],
            'debit_note_operation' => [
                ['30','Nota Débito que referencia una factura electrónica',null],
                ['32','Nota Débito sin referencia a una factura electrónica',null],
            ],
            'product_standard' => [
                ['999','Estándar de adopción del contribuyente','1'], ['001','UNSPSC','2'],
                ['020','Partida Arancelaria','3'], ['010','GTIN','4'],
            ],
            'claim_concept' => [
                ['01','Documento con inconsistencias','01'], ['02','Mercancía no entregada totalmente','02'],
                ['03','Mercancía no entregada parcialmente','03'], ['04','Servicio no prestado','04'],
            ],
            'event' => [
                ['030','Acuse de recibo de Factura Electrónica de Venta','030'],
                ['031','Reclamo de la Factura Electrónica de Venta','031'],
                ['032','Recibo del bien y/o prestación del servicio','032'],
                ['033','Aceptación expresa','033'], ['034','Aceptación tácita','034'],
            ],
            'numbering_document_type' => [
                ['21','Factura de Venta','21'], ['22','Nota Crédito','22'], ['23','Nota Débito','23'],
                ['24','Documento Soporte','24'], ['25','Nota de Ajuste Documento Soporte','25'],
                ['26','Nómina Electrónica','26'], ['27','Nota de ajuste Nómina Electrónica','27'],
                ['28','Nota de eliminación de nómina','28'], // solo v1
                ['30','Factura de talonario y de papel','30'],
            ],
            // Subconjunto de tipos de documento de identidad válido para Documento Soporte / Notas de ajuste
            'support_document_identity' => [
                ['21','Tarjeta de extranjería','4'], ['22','Cédula de extranjería','5'], ['31','NIT','6'],
                ['41','Pasaporte','7'], ['42','Documento de identificación extranjero','8'],
                ['47','PEP','9'], ['50','NIT otro país','10'],
            ],
            'adjustment_reason' => [
                ['1','Devolución parcial de los bienes y/o no aceptación parcial del servicio','1'],
                ['2','Anulación del documento soporte en adquisiciones a sujetos no obligados a facturar','2'],
                ['3','Rebaja o descuento parcial o total','3'],
                ['4','Ajuste de precio','4'], ['5','Otros','5'],
            ],
            'charge_discount' => [
                ['00','Descuento no condicionado','00'], ['01','Descuento condicionado','01'],
                ['02','Recargo no condicionado','02'], ['03','Recargo condicionado','03'],
            ],
            'fiscal_responsibility' => [
                ['O-13','Gran contribuyente','O-13'], ['O-15','Autorretenedor','O-15'],
                ['O-23','Agente de retención de IVA','O-23'], ['O-47','Régimen simple de tributación','O-47'],
                ['R-99-PN','No responsable','R-99-PN'],
            ],
            'withholding_tax' => [['05','Retención sobre el IVA','5'], ['06','Retención sobre renta','6']],

            // Impuestos: code v2 / id v1 (v1 repite el código 24; se omite el id 13 duplicado)
            'tax' => [
                ['01','IVA - Impuesto sobre las Ventas','1'],
                ['02','IC - Impuesto al Consumo Departamental Nominal','2'],
                ['03','ICA - Impuesto de Industria, Comercio y Aviso','3'],
                ['04','INC - Impuesto Nacional al Consumo','4'],
                ['05','ReteIVA - Retención sobre el IVA','5'],
                ['06','ReteRenta - Retención sobre renta','6'],
                ['07','ReteICA - Retención sobre el ICA','7'],
                ['08','IC Porcentual - Impuesto al Consumo Departamental Porcentual','8'],
                ['20','FtoHorticultura - Cuota de Fomento Hortifrutícula','9'],
                ['21','Timbre - Impuesto de Timbre','10'],
                ['22','INC Bolsas - Impuesto Nacional al Consumo de Bolsa Plástica','11'],
                ['23','INCarbono - Impuesto Nacional del Carbono','12'],
                ['24','INCombustibles - Impuesto Nacional a los Combustibles','14'],
                ['25','Sobretasa Combustibles','15'],
                ['26','Sordicom - Contribución minoristas (Combustibles)','16'],
                ['30','IC Datos - Impuesto al Consumo de Datos','17'],
                ['35','Impuesto a productos comestibles ultraprocesados',null], // solo v2
            ],

            // Sector salud (solo v2)
            'health_coverage_plan' => [
                ['01','Plan de beneficios en salud financiado con UPC',null], ['02','Presupuesto máximo',null],
                ['03','Prima EPS / EOC, no asegurados SOAT',null], ['04','Cobertura Póliza SOAT',null],
                ['05','Cobertura ARL',null], ['06','Cobertura ADRES',null], ['07','Cobertura Salud Pública',null],
                ['08','Cobertura entidad territorial, recursos de oferta',null],
                ['09','Urgencias población migrante',null], ['10','Plan complementario en salud',null],
                ['11','Plan medicina prepagada',null], ['12','Otras pólizas en salud',null],
                ['13','Cobertura Régimen Especial o Excepción',null],
                ['14','Cobertura Fondo Nacional de Salud de las Personas Privadas de la Libertad',null],
                ['15','Particular',null],
                ['16','Plan de beneficios en salud financiado con UPC régimen contributivo',null],
                ['17','Plan de beneficios en salud financiado con UPC régimen subsidiado',null],
            ],
            'health_identity_document' => [
                ['CC','Cédula de ciudadanía',null], ['CE','Cédula de extranjería',null], ['CD','Carné diplomático',null],
                ['PA','Pasaporte',null], ['SC','Salvoconducto',null], ['PE','Permiso especial de permanencia',null],
                ['RC','Registro civil de nacimiento',null], ['TI','Tarjeta de identidad',null],
                ['CN','Certificado de nacido vivo',null], ['AS','Adulto sin identificar',null],
                ['MS','Menor sin identificar',null], ['DE','Documento extranjero',null],
                ['SI','Sin identificación',null], ['PT','Permiso por protección temporal',null],
            ],
            'health_payment_method' => [
                ['01','Pago individual por caso / Conjunto integral de atenciones / Paquete / Canasta',null],
                ['02','Pago global prospectivo',null], ['03','Pago por capitación',null], ['04','Pago por evento',null],
            ],
            'health_advance_concept' => [
                ['01','Copago',null], ['02','Cuota moderadora',null],
                ['03','Pagos compartidos en planes voluntarios de salud',null],
                ['04','Anticipo',null], ['05','No aplica',null],
            ],
            'health_no_contract_cause' => [
                ['01','Atención de urgencias',null],
                ['02','Atención a cargo de ADRES o de Aseguradora SOAT, Planes voluntarios de Salud',null],
                ['03','Atención en salud por fallos de tutela/órdenes judiciales',null],
                ['04','Atención en salud por Portabilidad o asignación masiva de afiliados',null],
                ['05','Atención en salud en casos excepcionales por cotizaciones o autorizaciones sin contrato',null],
                ['06','Gestión recuperación de órganos para trasplante (ley estatutaria 1751 de 2015)',null],
                ['07','Atención a pacientes particulares',null],
            ],
        ];

        $count = 0;
        foreach ($catalogs as $type => $rows) {
            foreach ($rows as [$code, $name, $fId]) {
                $this->upsert(
                    'catalog_items',
                    ['type' => $type, 'code' => $code, 'name' => $name],
                    [
                        'detail' => mb_strtoupper($name),
                        'extra'  => json_encode(['factus_code' => $code, 'factus_id' => $fId], JSON_UNESCAPED_UNICODE),
                        'state'  => 1,
                    ],
                    ['extra'],
                    "item de catálogo [{$type}]: {$name}"
                );
                $count++;
            }
        }

        $this->command->info("✅ Items de catálogo procesados: {$count} en " . count($catalogs) . " catálogos");
    }

    // ------------------------------------------------------------------
    // Catálogo Cuentas y Movimientos
    // ------------------------------------------------------------------
    private function seedTypes(): void
    {
        $typesinv = [

            // ENTRADAS
            [
                'code'   => '001',
                'name'   => 'Entrada por Compra',
                'detail' => 'Ingreso inventario por compra',
                'codmod' => 3,
                'costo'  => 1,
                'trasla' => 0,
                'movimi' => 1
            ],
            [
                'code'   => '002',
                'name'   => 'Devolución Cliente',
                'detail' => 'Cliente devuelve mercancía',
                'codmod' => 2,
                'costo'  => 1,
                'trasla' => 0,
                'movimi' => 1
            ],

            // SALIDAS
            [
                'code'   => '003',
                'name'   => 'Salida por Venta',
                'detail' => 'Salida inventario por venta',
                'codmod' => 2,
                'costo'  => 1,
                'trasla' => 0,
                'movimi' => 2
            ],
            [
                'code'   => '004',
                'name'   => 'Devolución Proveedor',
                'detail' => 'Salida por devolución a proveedor',
                'codmod' => 3,
                'costo'  => 1,
                'trasla' => 0,
                'movimi' => 2
            ],

            // TRASLADO
            [
                'code'   => '005',
                'name'   => 'Traslado Interno',
                'detail' => 'Movimiento entre bodegas',
                'codmod' => 5,
                'costo'  => 0,
                'trasla' => 1,
                'movimi' => 3
            ],

            // AJUSTES INVENTARIO
            [
                'code'   => '006',
                'name'   => 'Ajuste Positivo',
                'detail' => 'Aumento manual inventario',
                'codmod' => 5,
                'costo'  => 1,
                'trasla' => 0,
                'movimi' => 1
            ],
            [
                'code'   => '007',
                'name'   => 'Ajuste Negativo',
                'detail' => 'Disminución manual inventario',
                'codmod' => 5,
                'costo'  => 1,
                'trasla' => 0,
                'movimi' => 2
            ],

            // TESORERÍA
            [
                'code'   => '008',
                'name'   => 'Ingreso de Caja',
                'detail' => 'Ingreso de dinero caja',
                'codmod' => 2,
                'state'  => 1,
                'movimi' => 1,
            ],
            [
                'code'   => '009',
                'name'   => 'Egreso de Caja',
                'detail' => 'Egreso de dinero caja',
                'codmod' => 3,
                'movimi' => 2,
                'state'  => 1,
            ],
            [
                'code'   => '010',
                'name'   => 'Recibo de Caja',
                'detail' => 'Ingreso de dinero con cruce a cartera',
                'codmod' => 4,
                'costo'  => 0,
                'trasla' => 0,
                'movimi' => 1,
            ],
            [
                'code'   => '011',
                'name'   => 'Traslado Tesorería',
                'detail' => 'Movimiento entre caja y banco',
                'codmod' => 5,
                'costo'  => 0,
                'trasla' => 1,
                'movimi' => 3,
            ],
            [
                'code'   => '012',
                'name'   => 'Anticipo Cliente',
                'detail' => 'Dinero recibido sin factura',
                'codmod' => 4,
                'costo'  => 0,
                'trasla' => 0,
                'movimi' => 1,
            ],
            [
                'code'   => '013',
                'name'   => 'Anticipo Proveedor',
                'detail' => 'Pago anticipado a proveedor',
                'codmod' => 4,
                'costo'  => 0,
                'trasla' => 0,
                'movimi' => 2,
            ],
            [
                'code'   => '014',
                'name'   => 'Ajuste Positivo Caja',
                'detail' => 'Ajuste manual ingreso',
                'codmod' => 4,
                'costo'  => 0,
                'trasla' => 0,
                'movimi' => 1,
            ],
            [
                'code'   => '015',
                'name'   => 'Ajuste Negativo Caja',
                'detail' => 'Ajuste manual egreso',
                'codmod' => 4,
                'costo'  => 0,
                'trasla' => 0,
                'movimi' => 2,
            ],
        ];

        $counttypesinv = 0;

        foreach ($typesinv as $type) {
            DB::connection('mysql')->table('inventory_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, [
                    'state'      => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'host'  => 'SYSTEM',
                    'codope'=> 'SOPORTE',
                    'basdat'=> 'chellnova',
                ])
            );
            $counttypesinv++;

            Soflog::create([
                'tipeve' => 'CREATE',
                'detail' => 'Se ha registrado el tipo de inventario: ' . $type['name'],
                'codmod' => 'SYSTEM',
                'period' => date('Y-m'),
                'anopro' => date('Y'),
                'mespro' => date('m'),
                'diapro' => date('d'),
                'fecope' => date('Y-m-d H:i:s'),
                'old_values' => null,
                'new_values' => json_encode($type),
                'host'  => 'SYSTEM',
                'codope'=> 'SOPORTE',
                'basdat'=> 'chellnova',
            ]);
        }

        $this->command->info("✅ Tipos de inventario procesados: {$counttypesinv} creados correctamente.");

        $typesaccount = [

            // VENTAS
            [
                'code'   => '001',
                'name'   => 'Venta',
                'detail' => 'Documento de venta',
                'codmod' => 1,
                'nature' => 'operativo'
            ],
            [
                'code'   => '002',
                'name'   => 'Nota Crédito Venta',
                'detail' => 'Devolución venta',
                'codmod' => 1,
                'nature' => 'operativo'
            ],

            // COMPRAS
            [
                'code'   => '003',
                'name'   => 'Compra',
                'detail' => 'Documento de compra',
                'codmod' => 3,
                'nature' => 'operativo'
            ],
            [
                'code'   => '004',
                'name'   => 'Nota Crédito Compra',
                'detail' => 'Devolución compra',
                'codmod' => 3,
                'nature' => 'operativo'
            ],

            // CARTERA
            [
                'code'   => '005',
                'name'   => 'Cobro',
                'detail' => 'Ingreso de dinero cliente',
                'codmod' => 4,
                'nature' => 'financiero'
            ],
            [
                'code'   => '006',
                'name'   => 'Pago',
                'detail' => 'Salida de dinero proveedor',
                'codmod' => 4,
                'nature' => 'financiero'
            ],

            // CONTABILIDAD
            [
                'code'   => '007',
                'name'   => 'Asiento Manual',
                'detail' => 'Asiento contable manual',
                'codmod' => 7,
                'nature' => 'contable'
            ],
            [
                'code'   => '008',
                'name'   => 'Cierre Contable',
                'detail' => 'Cierre mensual o anual',
                'codmod' => 7,
                'nature' => 'contable'
            ],
        ];

        $counttypesaccount = 0;

        foreach ($typesaccount as $type) {
            DB::connection('mysql')->table('account_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, [
                    'state'     => 1,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                    'host'  => 'SYSTEM',
                    'codope'=> 'SOPORTE',
                    'basdat'=> 'chellnova',
                ])
            );
            $counttypesaccount++;

            Soflog::create([
                'tipeve' => 'CREATE',
                'detail' => 'Se ha registrado el tipo de cuenta: ' . $type['name'],
                'codmod' => 'SYSTEM',
                'period' => date('Y-m'),
                'anopro' => date('Y'),
                'mespro' => date('m'),
                'diapro' => date('d'),
                'fecope' => date('Y-m-d H:i:s'),
                'old_values' => null,
                'new_values' => json_encode($type),
                'host'  => 'SYSTEM',
                'codope'=> 'SOPORTE',
                'basdat'=> 'chellnova',
            ]);
        }

        $pduMap = DB::connection('mysql')->table('account_pdu')->get(['codmov', 'code'])->keyBy('code');

        $this->command->info("✅ Tipos de cuenta iniciales {$counttypesaccount} creados correctamente.");

        $AccountPdu = [
            ['code'=>'1','name'=>'Activo','detail'=>'Recursos y bienes','nature'=>'D'],
            ['code'=>'2','name'=>'Pasivo','detail'=>'Obligaciones','nature'=>'C'],
            ['code'=>'3','name'=>'Patrimonio','detail'=>'Capital y utilidades','nature'=>'C'],
            ['code'=>'4','name'=>'Ingresos','detail'=>'Ingresos operacionales','nature'=>'C'],
            ['code'=>'5','name'=>'Gastos','detail'=>'Gastos operacionales','nature'=>'D'],
            ['code'=>'6','name'=>'Costos','detail'=>'Costos de venta o producción','nature'=>'D'],
            ['code'=>'7','name'=>'Cuentas de Orden Deudoras','detail'=>'Cuentas informativas deudoras','nature'=>'D'],
            ['code'=>'8','name'=>'Cuentas de Orden Acreedoras','detail'=>'Cuentas informativas acreedoras','nature'=>'C'],
            ['code'=>'9','name'=>'Cuentas de Orden de Control','detail'=>'Cuentas de control interno','nature'=>'D'],
        ];
        $countaccountpdu = 0;

        foreach ($AccountPdu as $type) {
            DB::connection('mysql')->table('account_pdu')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, [
                    'state'     => 1,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                    'host'  => 'SYSTEM',
                    'codope'=> 'SOPORTE',
                    'basdat'=> 'chellnova',
                ])
            );
            $countaccountpdu++;

            Soflog::create([
                'tipeve' => 'CREATE',
                'detail' => 'Se ha registrado el tipo de cuenta: ' . $type['name'],
                'codmod' => 'SYSTEM',
                'period' => date('Y-m'),
                'anopro' => date('Y'),
                'mespro' => date('m'),
                'diapro' => date('d'),
                'fecope' => date('Y-m-d H:i:s'),
                'old_values' => null,
                'new_values' => json_encode($type),
                'host'  => 'SYSTEM',
                'codope'=> 'SOPORTE',
                'basdat'=> 'chellnova',
            ]);
        }

        $this->command->info("✅ Tipos de cuenta iniciales {$countaccountpdu} creados correctamente.");

        $accounts = [
            // =========================
            // ACTIVO (1)
            // =========================
            ['code'=>'1','name'=>'ACTIVO','nature'=>'D','pdu'=> 1],

            // DISPONIBLE
            ['code'=>'11','name'=>'DISPONIBLE','nature'=>'D','pdu'=> 1],
            ['code'=>'1105','name'=>'CAJA','nature'=>'D','pdu'=> 1],
            ['code'=>'110505','name'=>'CAJA GENERAL','nature'=>'D','pdu'=> 1],
            ['code'=>'110510','name'=>'CAJA PRINCIPAL','nature'=>'D','pdu'=> 1],
            ['code'=>'110515','name'=>'CAJA MENOR','nature'=>'D','pdu'=> 1],
            ['code'=>'1110','name'=>'BANCOS','nature'=>'D','pdu'=> 1],
            ['code'=>'111005','name'=>'BANCOLOMBIA','nature'=>'D','pdu'=> 1],

            // CUENTAS POR COBRAR
            ['code'=>'13','name'=>'DEUDORES','nature'=>'D','pdu'=> 1],
            ['code'=>'1305','name'=>'CLIENTES','nature'=>'D','pdu'=> 1],
            ['code'=>'130505','name'=>'CLIENTES NACIONALES','nature'=>'D','pdu'=> 1],

            // CUENTAS POR COBRAR A EMPLEADOS (incapacidades EPS, anticipos)
            ['code'=>'1355','name'=>'CUENTAS POR COBRAR A EPS','nature'=>'D','pdu'=> 1],
            ['code'=>'135505','name'=>'INCAPACIDADES POR COBRAR A EPS','nature'=>'D','pdu'=> 1],
            ['code'=>'1370','name'=>'ANTICIPOS Y AVANCES','nature'=>'D','pdu'=> 1],
            ['code'=>'137005','name'=>'ANTICIPOS A EMPLEADOS','nature'=>'D','pdu'=> 1],

            // INVENTARIOS
            ['code'=>'14','name'=>'INVENTARIOS','nature'=>'D','pdu'=> 1],
            ['code'=>'1435','name'=>'MERCANCIAS NO FABRICADAS','nature'=>'D','pdu'=> 1],
            ['code'=>'143505','name'=>'INVENTARIO 19%','nature'=>'D','pdu'=> 1],
            ['code'=>'143510','name'=>'INVENTARIO 5%','nature'=>'D','pdu'=> 1],

            // IVA DESCONTABLE (ACTIVO)
            ['code'=>'240805','name'=>'IVA DESCONTABLE 19%','nature'=>'D','pdu'=> 1],
            ['code'=>'240806','name'=>'IVA DESCONTABLE 5%','nature'=>'D','pdu'=> 1],

            // =========================
            // PASIVO (2)
            // =========================
            ['code'=>'2','name'=>'PASIVO','nature'=>'C','pdu'=> 2],

            // PROVEEDORES
            ['code'=>'22','name'=>'PROVEEDORES','nature'=>'C','pdu'=> 2],
            ['code'=>'2205','name'=>'PROVEEDORES NACIONALES','nature'=>'C','pdu'=> 2 ],
            ['code'=>'220505','name'=>'PROVEEDORES VARIOS','nature'=>'C','pdu'=> 2],

            // IMPUESTOS
            ['code'=>'23','name'=>'IMPUESTOS','nature'=>'C','pdu'=> 2],

            ['code' => '2370',   'name' => 'RETENCIONES Y APORTES DE NOMINA',    'nature' => 'C', 'pdu' => 2],
            ['code' => '237005', 'name' => 'APORTES SALUD EMPLEADO',             'nature' => 'C', 'pdu' => 2],
            ['code' => '237010', 'name' => 'APORTES PENSION EMPLEADO',           'nature' => 'C', 'pdu' => 2],
            ['code' => '237015', 'name' => 'APORTE FONDO DE SOLIDARIDAD PENSIONAL', 'nature' => 'C', 'pdu' => 2],

            // OTRAS RETENCIONES DE NÓMINA (AFC)
            ['code' => '2360', 'name' => 'CUENTAS AFC', 'nature' => 'C', 'pdu' => 2],
            ['code' => '236010', 'name' => 'CUENTAS AFC EMPLEADOS', 'nature' => 'C', 'pdu' => 2],

            // APORTES VOLUNTARIOS DE PENSIÓN
            ['code' => '2380', 'name' => 'APORTES VOLUNTARIOS DE PENSIÓN', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238010', 'name' => 'APORTES VOLUNTARIOS DE PENSIÓN EMPLEADOS', 'nature' => 'C', 'pdu' => 2],

            // ACREEDORES VARIOS - DESCUENTOS DE NÓMINA
            ['code' => '2385', 'name' => 'ACREEDORES VARIOS - DESCUENTOS DE NÓMINA', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238525', 'name' => 'CUOTAS SINDICALES', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238530', 'name' => 'LIBRANZAS', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238535', 'name' => 'PAGOS A TERCEROS', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238540', 'name' => 'OTRAS DEDUCCIONES', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238545', 'name' => 'APORTES COOPERATIVA', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238550', 'name' => 'EMBARGOS FISCALES', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238555', 'name' => 'PLAN COMPLEMENTARIO DE SALUD', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238560', 'name' => 'EDUCACIÓN', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238565', 'name' => 'DEUDAS CON LA EMPRESA', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238570', 'name' => 'FONDO DE SUBSISTENCIA', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238575', 'name' => 'CUOTA ALIMENTARIA', 'nature' => 'C', 'pdu' => 2],
            ['code' => '238595', 'name' => 'SANCIONES', 'nature' => 'C', 'pdu' => 2],

            // RETENCIONES
            ['code'=>'2365','name'=>'RETENCIONES EN LA FUENTE','nature'=>'C','pdu'=> 2],
            ['code'=>'236505','name'=>'RETENCION RENTA','nature'=>'C','pdu'=> 2],
            ['code'=>'236510','name'=>'RETENCION IVA','nature'=>'C','pdu'=> 2],
            ['code'=>'236540','name'=>'RETENCION ICA','nature'=>'C','pdu'=> 2],

            // IVA GENERADO
            ['code'=>'2408','name'=>'IVA','nature'=>'C','pdu'=> 2],
            ['code'=>'240810','name'=>'IVA GENERADO 19%','nature'=>'C','pdu'=> 2],
            ['code'=>'240815','name'=>'IVA GENERADO 5%','nature'=>'C','pdu'=> 2],

            // OTROS PASIVOS
            ['code'=>'25','name'=>'OBLIGACIONES LABORALES','nature'=>'C','pdu'=> 2],
            ['code'=>'2505','name'=>'SALARIOS POR PAGAR','nature'=>'C','pdu'=> 2],

            // =========================
            // PATRIMONIO (3)
            // =========================
            ['code'=>'3','name'=>'PATRIMONIO','nature'=>'C','pdu'=> 3],
            ['code'=>'31','name'=>'CAPITAL SOCIAL','nature'=>'C','pdu'=> 3],
            ['code'=>'3105','name'=>'CAPITAL SUSCRITO','nature'=>'C','pdu'=> 3],
            ['code'=>'36','name'=>'RESULTADOS','nature'=>'C','pdu'=> 3],
            ['code'=>'3605','name'=>'UTILIDAD DEL EJERCICIO','nature'=>'C','pdu'=> 3],

            // =========================
            // INGRESOS (4)
            // =========================
            ['code'=>'4','name'=>'INGRESOS','nature'=>'C','pdu'=> 4],
            ['code'=>'41','name'=>'OPERACIONALES','nature'=>'C','pdu'=> 4],
            ['code'=>'4135','name'=>'VENTA DE MERCANCIAS','nature'=>'C','pdu'=> 4],
            ['code'=>'413505','name'=>'VENTAS 19%','nature'=>'C','pdu'=> 4],
            ['code'=>'413510','name'=>'VENTAS 5%','nature'=>'C','pdu'=> 4],
            ['code'=>'413515','name'=>'VENTAS EXENTO','nature'=>'C','pdu'=> 4],
            ['code'=>'4175','name'=>'DEVOLUCIONES EN VENTAS','nature'=>'D','pdu'=> 4],
            ['code'=>'417501','name'=>'DESCUENTOS EN VENTAS','nature'=>'D','pdu'=> 4],

            // =========================
            // GASTOS (5)
            // =========================
            ['code'=>'5','name'=>'GASTOS','nature'=>'D','pdu'=> 5],
            ['code'=>'51','name'=>'GASTOS OPERACIONALES','nature'=>'D','pdu'=> 5],
            ['code'=>'5105','name'=>'GASTOS DE PERSONAL','nature'=>'D','pdu'=> 5],
            ['code'=>'510505','name'=>'SUELDOS','nature'=>'D','pdu'=> 5],
            ['code'=>'510506', 'name' => 'AUXILIO DE TRANSPORTE', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510507', 'name' => 'HORAS EXTRAS Y RECARGOS', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510508', 'name' => 'BONIFICACIONES NO SALARIALES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510509', 'name' => 'VACACIONES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510510', 'name' => 'PRIMA DE SERVICIOS', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510513', 'name' => 'BONIFICACIONES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510514', 'name' => 'AUXILIOS', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510515', 'name' => 'HUELGA LEGAL', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510516', 'name' => 'COMPENSACIONES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510517', 'name' => 'BONOS DE ALIMENTACIÓN', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510518', 'name' => 'COMISIONES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510519', 'name' => 'APOYO DE SOSTENIMIENTO', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510520', 'name' => 'AUXILIO DE CONECTIVIDAD', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510522', 'name' => 'BONIFICACIÓN POR RETIRO', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510525', 'name' => 'INDEMNIZACIONES LABORALES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510599', 'name' => 'OTROS CONCEPTOS SALARIALES Y NO SALARIALES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510568', 'name' => 'APORTES SALUD EPS EMPLEADOR',        'nature' => 'D', 'pdu' => 5],
            ['code'=>'510569', 'name' => 'APORTES FONDO DE PENSIONES EMPLEADOR','nature' => 'D', 'pdu' => 5],
            ['code'=>'510570', 'name' => 'APORTES ARL',                        'nature' => 'D', 'pdu' => 5],
            ['code'=>'5108', 'name' => 'DOTACIÓN Y SUMINISTRO A TRABAJADORES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'510807', 'name' => 'DOTACIÓN Y SUMINISTRO A TRABAJADORES', 'nature' => 'D', 'pdu' => 5],
            ['code'=>'5110','name'=>'HONORARIOS','nature'=>'D','pdu'=> 5],
            ['code'=>'5120','name'=>'ARRENDAMIENTOS','nature'=>'D','pdu'=> 5],
            ['code'=>'5135','name'=>'SERVICIOS PUBLICOS','nature'=>'D','pdu'=> 5],
            ['code'=>'513520','name'=>'FLETES Y ACARREOS','nature'=>'D','pdu'=> 5],
            ['code'=>'5140','name'=>'GASTOS LEGALES','nature'=>'D','pdu'=> 5],
            ['code'=>'5155','name'=>'GASTOS DE VIAJE','nature'=>'D','pdu'=> 5],
            ['code'=>'5160','name'=>'DEPRECIACIONES','nature'=>'D','pdu'=> 5],
            ['code'=>'5175','name'=>'OTROS GASTOS','nature'=>'D','pdu'=> 5],
            ['code'=>'517501','name'=>'OTROS GASTOS 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'517510','name'=>'OTROS GASTOS 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'517515','name'=>'OTROS GASTOS EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'5180','name'=>'GASTOS FINANCIEROS','nature'=>'D','pdu'=> 5],
            ['code'=>'518005','name'=>'GASTOS BANCARIOS','nature'=>'D','pdu'=> 5],
            ['code'=>'518010','name'=>'INTERESES PAGADOS','nature'=>'D','pdu'=> 5],
            ['code'=>'518015','name'=>'OTROS GASTOS FINANCIEROS','nature'=>'D','pdu'=> 5],
            ['code'=>'5190','name'=>'GASTOS NO OPERACIONALES','nature'=>'D','pdu'=> 5],
            ['code'=>'519005','name'=>'MULTAS Y SANCIONES','nature'=>'D','pdu'=> 5],
            ['code'=>'519010','name'=>'PÉRDIDAS EN VENTAS DE ACTIVOS','nature'=>'D','pdu'=> 5],
            ['code'=>'519015','name'=>'OTROS GASTOS NO OPERACIONALES','nature'=>'D','pdu'=> 5],
            ['code'=>'5195','name'=>'GASTOS EXCEPCIONALES','nature'=>'D','pdu'=> 5],
            ['code'=>'519505','name'=>'GASTOS POR DESASTRES NATURALES','nature'=>'D','pdu'=> 5],
            ['code'=>'519510','name'=>'GASTOS POR DEMANDAS JUDICIALES','nature'=>'D','pdu'=> 5],
            ['code'=>'519515','name'=>'OTROS GASTOS EXCEPCIONALES','nature'=>'D','pdu'=> 5],
            ['code'=>'5199','name'=>'GASTOS VARIOS','nature'=>'D','pdu'=> 5],
            ['code'=>'519905','name'=>'GASTOS VARIOS 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'519910','name'=>'GASTOS VARIOS 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'519915','name'=>'GASTOS VARIOS EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'5200','name'=>'COSTOS DE VENTAS','nature'=>'D','pdu'=> 5],
            ['code'=>'520005','name'=>'COSTO DE MERCANCIAS VENDIDAS 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'520010','name'=>'COSTO DE MERCANCIAS VENDIDAS 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'520015','name'=>'COSTO DE MERCANCIAS VENDIDAS EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'5210','name'=>'GASTOS DE COMPRA','nature'=>'D','pdu'=> 5],
            ['code'=>'521005','name'=>'GASTOS DE COMPRA 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'521010','name'=>'GASTOS DE COMPRA 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'521015','name'=>'GASTOS DE COMPRA EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'5220','name'=>'GASTOS DE VENTA','nature'=>'D','pdu'=> 5],
            ['code'=>'522005','name'=>'GASTOS DE VENTA 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'522010','name'=>'GASTOS DE VENTA 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'522015','name'=>'GASTOS DE VENTA EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'5230','name'=>'DESCUENTOS EN VENTAS','nature'=>'D','pdu'=> 5],
            ['code'=>'523005','name'=>'DESCUENTOS EN VENTAS 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'523010','name'=>'DESCUENTOS EN VENTAS 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'523015','name'=>'DESCUENTOS EN VENTAS EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'5235','name'=>'DESCUENTOS EN COMPRAS','nature'=>'D','pdu'=> 5],
            ['code'=>'523505','name'=>'DESCUENTOS EN COMPRAS 19%','nature'=>'D','pdu'=> 5],
            ['code'=>'523510','name'=>'DESCUENTOS EN COMPRAS 5%','nature'=>'D','pdu'=> 5],
            ['code'=>'523515','name'=>'DESCUENTOS EN COMPRAS EXENTO','nature'=>'D','pdu'=> 5],
            ['code'=>'523520','name'=>'DESCUENTOS EN COMPRAS OTROS','nature'=>'D','pdu'=> 5],
            // =========================
            // COSTOS (6)
            // =========================
            ['code'=>'6','name'=>'COSTOS','nature'=>'D','pdu'=> 6],
            ['code'=>'61','name'=>'COSTO DE VENTAS','nature'=>'D','pdu'=> 6],
            ['code'=>'6135','name'=>'COSTO DE MERCANCIAS','nature'=>'D','pdu'=> 6],
            ['code'=>'613505','name'=>'COSTO 19%','nature'=>'D','pdu'=> 6],
            ['code'=>'613510','name'=>'COSTO 5%','nature'=>'D','pdu'=> 6],
            ['code'=>'613515','name'=>'COSTO EXENTO','nature'=>'D','pdu'=> 6],

            // COMPRAS
            ['code'=>'62','name'=>'COMPRAS','nature'=>'D','pdu'=> 6],
            ['code'=>'6205','name'=>'COMPRAS','nature'=>'D','pdu'=> 6],
            ['code'=>'620505','name'=>'COMPRAS 19%','nature'=>'D','pdu'=> 6],
            ['code'=>'620510','name'=>'COMPRAS 5%','nature'=>'D','pdu'=> 6],
            ['code'=>'620515','name'=>'COMPRAS EXENTO','nature'=>'D','pdu'=> 6],
        ];

        $created = [];
        $countaccount = 0;

        foreach ($accounts as $account) {

            $code = $account['code'];
            $length = strlen($code);

            $parent_id = null;

            // Nivel 2: 11, 13, 14, 22, 41, etc.
            if ($length == 2) {

                $parent_code = substr($code, 0, 1);

                $parent_id = $created[$parent_code] ?? null;

            }
            // Nivel 3 y superiores: 1105, 110505, 413505, etc.
            elseif ($length > 2) {

                $parent_code = substr($code, 0, -2);

                $parent_id = $created[$parent_code] ?? null;

            }

            $id = DB::connection('mysql')->table('accounts')->updateOrInsert([
                'code'       => $code,
                'name'       => $account['name'],
                'level'      => $length <= 2 ? 1 : ($length <= 4 ? 2 : ($length <= 6 ? 3 : 4)),
                'codcta'     => $parent_id,
                'codpdu'     => $account['pdu'],
                'nature'     => $account['nature'],
                'regter'     => 0,
                'regcos'     => 0,
                'regrte'     => 0,
                'regiva'     => 0,
                'regica'     => 0,
                'state'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'host'  => 'SYSTEM',
                'codope'=> 'SOPORTE',
                'basdat'=> 'chellnova',
            ]);

            $created[$code] = $id;
            $countaccount++;

            Soflog::create([
                'tipeve'     => 'CREATE',
                'detail'     => 'Se ha registrado la cuenta: ' . $account['name'],
                'codmod'     => 'SYSTEM',
                'period'     => date('Y-m'),
                'anopro'     => date('Y'),
                'mespro'     => date('m'),
                'diapro'     => date('d'),
                'fecope'     => date('Y-m-d H:i:s'),
                'old_values' => null,
                'new_values' => json_encode($account),
                'host'  => 'SYSTEM',
                'codope'=> 'SOPORTE',
                'basdat'=> 'chellnova',
            ]);
        }

        $this->command->info("✅ Cuentas contables {$countaccount} creadas correctamente.");
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Inserta si no existe; si existe, sincroniza solo las columnas de $sync.
     * Devuelve: created | updated | unchanged
     */
    private function upsert(string $table, array $key, array $values, array $sync, string $label, bool $log = true): string
    {
        $db = DB::connection('mysql');

        $existing = $db->table($table)->where($key)->whereNull('deleted_at')->first();

        if (!$existing) {
            $db->table($table)->insert(
                $key + $values + $this->audit + ['created_at' => now(), 'updated_at' => now()]
            );
            if ($log) {
                $this->log('CREATE', "Se ha registrado la {$label}", null, $key + $values);
            }

            return 'created';
        }

        $changes = [];
        foreach (array_intersect_key($values, array_flip($sync)) as $col => $val) {
            if ((string) ($existing->{$col} ?? '') !== (string) ($val ?? '')) {
                $changes[$col] = $val;
            }
        }

        if (!$changes) {
            return 'unchanged';
        }

        $old = array_intersect_key((array) $existing, $changes);
        $db->table($table)->where('codmov', $existing->codmov)->update($changes + ['updated_at' => now()]);
        $this->log('UPDATE', "Se ha actualizado la {$label}", $old, $changes);

        return 'updated';
    }

    private function log(string $event, string $detail, ?array $old, array $new): void
    {
        Soflog::create([
            'tipeve'     => $event,
            'detail'     => $detail,
            'codmod'     => 'SYSTEM',
            'period'     => date('Y-m'),
            'anopro'     => date('Y'),
            'mespro'     => date('m'),
            'diapro'     => date('d'),
            'fecope'     => date('Y-m-d H:i:s'),
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => json_encode($new),
            'host'       => $this->audit['host'],
            'codope'     => $this->audit['codope'],
            'basdat'     => $this->audit['basdat'],
        ]);
    }
}
