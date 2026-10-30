<?php

namespace App\Services\EInvoice\DTO;

use App\Services\EInvoice\DocumentKind;

/**
 * Documento a emitir, independiente del proveedor y del país.
 *
 * Fase 1: lleva el encabezado neutro (tipo, id, moneda) y los mismos
 * insumos que hoy reciben los builders de Colombia (documento, detalle,
 * prefijo, tercero, sede), para no cambiar su lógica. En las fases 2 a 5
 * (impuestos, ID fiscal, multimoneda) este DTO pasa a llevar líneas e
 * impuestos normalizados y los builders dejan de leer los modelos.
 */
final class ElectronicDocument
{
    public function __construct(
        public readonly DocumentKind $kind,
        public readonly int $documentId,
        public readonly array $document,
        public readonly mixed $details,
        public readonly mixed $prefix,
        public readonly mixed $customer,
        public readonly mixed $branch,
        public readonly string $currency = 'COP',
        public readonly float $exchangeRate = 1.0,
    ) {
    }

    public function documentType(): string
    {
        return $this->kind->label();
    }

    public function documentTable(): string
    {
        return $this->kind->table();
    }
}
