<?php

namespace App\Services\EInvoice;

/**
 * Tipos de documento electrónico que el ERP puede emitir.
 * Es neutro respecto al país: cada proveedor decide cómo traducirlo.
 */
enum DocumentKind: string
{
    case SalesInvoice = 'sales_invoice';
    case SalesCreditNote = 'sales_credit_note';
    case PurchaseSupportDocument = 'purchase_support_document';
    case PurchaseAdjustmentNote = 'purchase_adjustment_note';

    /** Etiqueta que se guarda hoy en factus_documents_requests.document_type */
    public function label(): string
    {
        return match ($this) {
            self::SalesInvoice => 'Factura electrónica de Venta',
            self::SalesCreditNote => 'Nota de crédito factura electrónica',
            self::PurchaseSupportDocument => 'Documento Soporte',
            self::PurchaseAdjustmentNote => 'Nota de Ajuste a Documento Soporte',
        };
    }

    /** Tabla del ERP donde vive el documento origen */
    public function table(): string
    {
        return match ($this) {
            self::SalesInvoice => 'sales_invoices',
            self::SalesCreditNote => 'sales_notes',
            self::PurchaseSupportDocument => 'purchase_invoices',
            self::PurchaseAdjustmentNote => 'purchase_notes',
        };
    }
}
