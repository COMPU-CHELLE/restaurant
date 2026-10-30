<?php

/*
|--------------------------------------------------------------------------
| Catálogos de parámetros
|--------------------------------------------------------------------------
| Un mismo motor sirve para todos, pero CADA CATÁLOGO TIENE SU PROPIA TABLA (igual que Chellnova):
| el nombre de la tabla es la clave del catálogo (units, document_classes, ...). Soporte los define por
| defecto y cada empresa los importa o ajusta (misma tabla en la base de la empresa).
|
| Columnas comunes: codmov, code, name, detail, state, host, codope, basdat, timestamps.
| Si el catálogo se usa con Factus (por defecto sí; 'factus' => false lo excluye) la tabla suma:
|   code_fe   = código que se envía a la factura electrónica V2
|   factus_id = identificador que se envía a V1 (ID numérico o código)
|
| fields: columnas propias del catálogo.
|   type: text | number | select (options) | catalog (source = código de otro catálogo)
| scope: both (predeterminado) | tenant (solo de la empresa: no lo define ni importa soporte)
|
| Si cambias fields hay que crear una migración que agregue/modifique la columna.
*/
return [
    'types' => [
        // Parámetros de seguridad globales: solo se administran desde soporte.
        'roles' => ['label' => 'Roles', 'scope' => 'support', 'factus' => false, 'fields' => []],
        'modules' => ['label' => 'Módulos', 'scope' => 'support', 'factus' => false, 'fields' => [
            ['key' => 'url', 'label' => 'URL', 'type' => 'text', 'max' => 255, 'unique' => true],
            ['key' => 'icon', 'label' => 'Icono', 'type' => 'text', 'max' => 80],
        ]],
        'levels' => ['label' => 'Submódulos y opciones de menú', 'scope' => 'support', 'factus' => false, 'fields' => [
            ['key' => 'icon', 'label' => 'Icono', 'type' => 'text', 'required' => true, 'max' => 80],
            ['key' => 'url', 'label' => 'URL', 'type' => 'text', 'required' => true, 'max' => 255, 'unique' => true],
            ['key' => 'parent', 'label' => 'Código del módulo o nivel padre', 'type' => 'text', 'required' => true, 'max' => 255],
            ['key' => 'type', 'label' => 'Tipo', 'type' => 'select', 'required' => true,
                'options' => ['LEVEL' => 'Submódulo', 'ITEM' => 'Opción']],
        ]],
        'currencies' => ['label' => 'Monedas', 'factus' => false, 'fields' => [
            ['key' => 'symbol', 'label' => 'Símbolo', 'type' => 'text', 'required' => true, 'max' => 5],
            ['key' => 'decimals', 'label' => 'Decimales', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 4, 'step' => 1],
        ]],
        'client_tax_types' => ['label' => 'Tributos de clientes (DIAN)', 'fields' => [
            ['key' => 'factus_id_v1', 'label' => 'ID Factus V1 (compatibilidad)', 'type' => 'number', 'min' => 1, 'step' => 1],
        ]],
        'tax_types' => ['label' => 'Impuestos de productos y servicios', 'fields' => [
        ]],
        'tax_rates' => ['label' => 'Tarifas de impuestos', 'factus' => false, 'fields' => [
            ['key' => 'tax_type', 'label' => 'Impuesto', 'type' => 'catalog', 'source' => 'tax_types', 'required' => true],
            ['key' => 'percentage', 'label' => 'Porcentaje', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100, 'step' => 0.01],
        ]],
        'units' => ['label' => 'Unidades de medida', 'fields' => [
            ['key' => 'abbreviation', 'label' => 'Abreviatura', 'type' => 'text', 'required' => true, 'max' => 10],
            ['key' => 'parent_unit', 'label' => 'Unidad base', 'type' => 'catalog', 'source' => 'units', 'hint' => 'Unidad de la que depende (ej.: g depende de kg).'],
            ['key' => 'factor', 'label' => 'Factor', 'type' => 'number', 'min' => 0, 'max' => 999999, 'step' => 0.0001, 'hint' => 'Equivalencia respecto a la unidad base.'],
        ]],
        'document_classes' => ['label' => 'Tipos de documento', 'fields' => [
            ['key' => 'factus_id_v1', 'label' => 'ID Factus V1 (compatibilidad)', 'type' => 'number', 'min' => 1, 'step' => 1],
        ]],
        'support_document_classes' => ['label' => 'Tipos de identificación para documento soporte', 'fields' => [
            ['key' => 'factus_id_v1', 'label' => 'ID Factus V1', 'type' => 'number', 'min' => 1, 'step' => 1],
        ]],
        'organization_types' => ['label' => 'Tipos de organización', 'fields' => []],
        'electronic_document_types' => ['label' => 'Tipos de documentos electrónicos', 'fields' => []],
        'operation_types' => ['label' => 'Tipos de operación de factura', 'fields' => []],
        'credit_note_operation_types' => ['label' => 'Tipos de operación de nota crédito', 'fields' => []],
        'debit_note_operation_types' => ['label' => 'Tipos de operación de nota débito', 'fields' => []],
        'credit_note_correction_codes' => ['label' => 'Motivos de corrección de nota crédito', 'fields' => []],
        'debit_note_correction_codes' => ['label' => 'Motivos de corrección de nota débito', 'fields' => []],
        'product_identification_standards' => ['label' => 'Estándares de identificación de productos', 'fields' => [
            ['key' => 'legacy_id_v1', 'label' => 'ID legado V1', 'type' => 'number', 'min' => 1, 'step' => 1],
        ]],
        'claim_concepts' => ['label' => 'Conceptos de reclamo', 'fields' => []],
        'document_events' => ['label' => 'Eventos de factura electrónica', 'fields' => []],
        'numbering_range_types' => ['label' => 'Tipos de documento para rangos de numeración', 'fields' => []],
        'support_document_adjustment_reasons' => ['label' => 'Motivos de nota de ajuste de documento soporte', 'fields' => []],
        'surcharge_discount_codes' => ['label' => 'Códigos de recargos y descuentos', 'fields' => []],
        'health_coverage_plans' => ['label' => 'Sector salud: planes de cobertura', 'fields' => []],
        'health_document_classes' => ['label' => 'Sector salud: documentos de identificación', 'fields' => []],
        'health_payment_methods' => ['label' => 'Sector salud: métodos de pago', 'fields' => []],
        'health_advance_concepts' => ['label' => 'Sector salud: conceptos de anticipo o recaudo', 'fields' => []],
        'health_no_contract_reasons' => ['label' => 'Sector salud: causales de factura sin contrato', 'fields' => []],
        'payment_forms' => ['label' => 'Formas de pago', 'fields' => []],
        'payment_means' => ['label' => 'Medios de pago (DIAN)', 'fields' => []],
        'account_types' => ['label' => 'Tipos de cuentas', 'factus' => false, 'fields' => [
            ['key' => 'nature', 'label' => 'Naturaleza', 'type' => 'select', 'required' => true,
                'options' => ['D' => 'Débito', 'C' => 'Crédito']],
        ]],
        'retention_types' => ['label' => 'Tipos de retenciones', 'fields' => [
            ['key' => 'percentage', 'label' => 'Porcentaje', 'type' => 'number', 'min' => 0, 'max' => 100, 'step' => 0.01],
        ]],
        'movement_types' => ['label' => 'Tipos de movimiento de inventario', 'factus' => false, 'fields' => [
            ['key' => 'direction', 'label' => 'Sentido', 'type' => 'select', 'required' => true,
                'options' => ['IN' => 'Entrada', 'OUT' => 'Salida']],
        ]],
        'economic_activities' => ['label' => 'Actividades económicas (CIIU)', 'factus' => false, 'fields' => []],
        // Clases del plan único de cuentas (1 Activo, 2 Pasivo…).
        'pdu_types' => ['label' => 'Tipos de PDU', 'factus' => false, 'fields' => [
            ['key' => 'nature', 'label' => 'Naturaleza', 'type' => 'select', 'required' => true,
                'options' => ['D' => 'Débito', 'C' => 'Crédito']],
        ]],
        'accounts' => ['label' => 'Cuentas', 'factus' => false, 'fields' => [
            ['key' => 'pdu_type', 'label' => 'Clase (PDU)', 'type' => 'catalog', 'source' => 'pdu_types', 'required' => true],
            ['key' => 'level', 'label' => 'Nivel', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 6, 'step' => 1,
                'hint' => '1 clase, 2 grupo, 3 cuenta, 4 subcuenta…'],
            ['key' => 'nature', 'label' => 'Naturaleza', 'type' => 'select',
                'options' => ['D' => 'Débito', 'C' => 'Crédito']],
            ['key' => 'parent', 'label' => 'Código de la cuenta padre', 'type' => 'text', 'max' => 20],
        ]],
        'fiscal_responsibilities' => ['label' => 'Responsabilidades fiscales', 'fields' => []],
        // Propios del restaurante: solo de cada empresa; el administrador los crea (no hay nada que importar).
        'order_statuses' => ['label' => 'Estados de pedido', 'scope' => 'tenant', 'fields' => [
            ['key' => 'color', 'label' => 'Color', 'type' => 'text', 'max' => 7, 'hint' => 'Hexadecimal. Ej.: #16a34a'],
            ['key' => 'is_final', 'label' => 'Estado final', 'type' => 'select', 'options' => ['Y' => 'Sí', 'N' => 'No']],
        ]],
        'cancellation_reasons' => ['label' => 'Motivos de anulación', 'scope' => 'tenant', 'fields' => [
            ['key' => 'requires_note', 'label' => 'Exige observación', 'type' => 'select', 'options' => ['Y' => 'Sí', 'N' => 'No']],
        ]],
        'inventory_reasons' => ['label' => 'Motivos de inventario', 'scope' => 'tenant', 'fields' => [
            ['key' => 'movement_type', 'label' => 'Tipo de movimiento', 'type' => 'catalog', 'source' => 'movement_types'],
        ]],
        'preparation_areas' => ['label' => 'Áreas de preparación', 'scope' => 'tenant', 'fields' => [
            ['key' => 'printer', 'label' => 'Impresora', 'type' => 'text', 'max' => 30, 'hint' => 'Código de la impresora de comandas'],
        ]],
        // Solo de cada empresa: el administrador las crea (no hay nada que importar).
        'branches' => ['label' => 'Sedes', 'scope' => 'tenant', 'fields' => [
            ['key' => 'address', 'label' => 'Dirección', 'type' => 'text', 'max' => 255],
            ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'text', 'max' => 30],
            ['key' => 'city', 'label' => 'Ciudad', 'type' => 'text', 'max' => 100],
        ]],
    ],
];
