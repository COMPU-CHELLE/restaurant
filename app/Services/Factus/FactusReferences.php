<?php

/**
 * REFERENCIA: Tablas de datos de Factus para Factura Electrónica Colombia
 *
 * Este archivo documenta todos los IDs y códigos que Factus utiliza
 * para permitir sincronización y validación de datos en Chellnova
 */

namespace App\Services\Factus;

class FactusReferences
{
    /**
     * Tipos de documentos electrónicos
     */
    public const DOCUMENT_TYPES = [
        '01' => 'Factura electrónica de venta',
        '03' => 'Instrumento electrónico de transmisión - tipo 03',
    ];

    /**
     * Tipos de operación (venta/remisión/factura)
     */
    public const OPERATION_TYPES = [
        '10' => 'Estándar',
        '11' => 'Mandatos',
        '12' => 'Transporte',
    ];

    /**
     * Códigos de corrección para notas crédito/débito
     */
    public const CORRECTION_CODES = [
        '1' => 'Devolución parcial de los bienes y/o no aceptación parcial del servicio',
        '2' => 'Anulación de factura electrónica',
        '3' => 'Rebaja o descuento parcial o total',
        '4' => 'Ajuste de precio',
        '5' => 'Descuento comercial por pronto pago',
        '6' => 'Descuento comercial por volumen de ventas',
    ];

    /**
     * Tipos de operación para notas crédito
     */
    public const CREDIT_NOTE_TYPES = [
        '20' => 'Nota Crédito que referencia una factura electrónica',
        '22' => 'Nota Crédito sin referencia a una factura electrónica',
    ];

    /**
     * Estándares de identificación de producto
     * ID para usar al enviar items/líneas a Factus
     */
    public const PRODUCT_IDENTIFICATION_STANDARDS = [
        1 => 'Estándar de adopción del contribuyente',
        2 => 'UNSPSC',
        3 => 'Partida Arancelaria',
        4 => 'GTIN',
    ];

    /**
     * Conceptos de reclamo en eventos
     */
    public const CLAIM_CONCEPTS = [
        '01' => 'Documento con inconsistencias',
        '02' => 'Mercancía no entregada totalmente',
        '03' => 'Mercancía no entregada parcialmente',
        '04' => 'Servicio no prestado',
    ];

    /**
     * Eventos posibles en ciclo de vida de factura
     */
    public const DOCUMENT_EVENTS = [
        '030' => 'Acuse de recibo de Factura Electrónica de Venta',
        '031' => 'Reclamo de la Factura Electrónica de Venta',
        '032' => 'Recibo del bien y/o prestación del servicio',
        '033' => 'Aceptación expresa',
        '034' => 'Aceptación tácita',
    ];

    /**
     * Tipos de documentos de identidad
     * ⚠️ CRÍTICO: Estos IDs deben usarse en integración con Factus
     */
    public const DOCUMENT_IDENTITY_TYPES = [
        1  => 'Registro civil',
        2  => 'Tarjeta de identidad',
        3  => 'Cédula de ciudadanía',
        4  => 'Tarjeta de extranjería',
        5  => 'Cédula de extranjería',
        6  => 'NIT',
        7  => 'Pasaporte',
        8  => 'Documento de identificación extranjero',
        9  => 'PEP',
        10 => 'NIT otro país',
        11 => 'NUIP',
    ];

    /**
     * Tipos de identidad válidos SOLO para Documentos Soporte
     * (Subconjunto de DOCUMENT_IDENTITY_TYPES)
     */
    public const SUPPORT_DOCUMENT_IDENTITY_TYPES = [
        4  => 'Tarjeta de extranjería',
        5  => 'Cédula de extranjería',
        6  => 'NIT',
        7  => 'Pasaporte',
        8  => 'Documento de identificación extranjero',
        9  => 'PEP',
        10 => 'NIT otro país',
    ];

    /**
     * Tributos clientes (impuestos aplicables)
     */
    public const CLIENT_TAXES = [
        18 => ['code' => '01', 'name' => 'IVA'],
        21 => ['code' => 'ZZ', 'name' => 'No aplica'],
    ];

    /**
     * Tipos de organizaciones
     */
    public const ORGANIZATION_TYPES = [
        1 => 'Persona Jurídica',
        2 => 'Persona Natural',
    ];

    /**
     * Códigos de métodos de pago
     * Se usa para especificar cómo se realizará el pago
     */
    public const PAYMENT_METHODS = [
        '10'  => 'Efectivo',
        '42'  => 'Consignación',
        '20'  => 'Cheque',
        '47'  => 'Transferencia',
        '71'  => 'Bonos',
        '72'  => 'Vales',
        '1'   => 'Medio de pago no definido',
        '49'  => 'Tarjeta Débito',
        '48'  => 'Tarjeta Crédito',
        'ZZZ' => 'Otro',
    ];

    /**
     * Códigos de formas de pago
     * Especifica si es contado o a crédito
     */
    public const PAYMENT_TERMS = [
        '1' => 'Pago de contado',
        '2' => 'Pago a crédito',
    ];

    /**
     * Tipos de documento para rangos de numeración
     * Se usa al crear prefijos/resoluciones en Factus
     */
    public const NUMBERING_RANGE_TYPES = [
        '21' => 'Factura de Venta',
        '22' => 'Nota Crédito',
        '23' => 'Nota Débito',
        '24' => 'Documento Soporte',
        '25' => 'Nota de Ajuste Documento Soporte',
        '26' => 'Nómina',
        '27' => 'Nota de Ajuste Nómina',
        '28' => 'Nota de eliminación de nómina',
        '30' => 'Factura de talonario y de papel',
    ];

    /**
     * Motivos para notas de ajuste de documento soporte
     */
    public const SUPPORT_DOC_ADJUSTMENT_REASONS = [
        '1' => 'Devolución parcial de los bienes y/o no aceptación parcial del servicio',
        '2' => 'Anulación del documento soporte en adquisiciones efectuadas a sujetos no obligados a expedir factura',
        '3' => 'Rebaja o descuento parcial o total',
        '4' => 'Ajuste de precio',
        '5' => 'Otros',
    ];

    /**
     * Códigos para recargos y descuentos
     */
    public const SURCHARGE_DISCOUNT_CODES = [
        '00' => 'Descuento no condicionado (disponible próximamente)',
        '01' => 'Descuento condicionado (disponible próximamente)',
        '02' => 'Recargo no condicionado (disponible próximamente)',
        '03' => 'Recargo condicionado',
    ];

    /**
     * Responsabilidades fiscales del contribuyente
     */
    public const FISCAL_RESPONSIBILITIES = [
        'O-13'     => 'Gran contribuyente',
        '0-15'     => 'Autorretenedor',
        '0-23'     => 'Agente de retención de IVA',
        '0-47'     => 'Régimen simple de tributación',
        'R-99-PN'  => 'No responsable',
    ];

    /** Alias usados en contactos (clients_suppliers.document_type) -> código DIAN V2. */
    public const DOCUMENT_ALIASES = [
        'RC' => '11', 'TI' => '12', 'CC' => '13', 'TE' => '21', 'CE' => '22', 'NIT' => '31',
        'PA' => '41', 'PASAPORTE' => '41', 'PP' => '41', 'DIE' => '42', 'PEP' => '47', 'PPT' => '48',
        'NITX' => '50', 'NUIP' => '91',
    ];

    /** Código DIAN V2 de un tipo de documento a partir del alias del contacto (CC, NIT, ...). */
    public static function documentCode(?string $alias, string $default = '13'): string
    {
        return self::DOCUMENT_ALIASES[strtoupper(trim((string) $alias))] ?? $default;
    }

    /**
     * Obtener nombre amigable de un valor
     *
     * @param string $constant Nombre de la constante (ej: 'DOCUMENT_IDENTITY_TYPES')
     * @param mixed $value Valor a buscar
     * @return string|null
     */
    public static function getLabel(string $constant, mixed $value): ?string
    {
        if (!defined("self::$constant")) {
            return null;
        }

        $array = constant("self::$constant");
        return $array[$value] ?? null;
    }
}
