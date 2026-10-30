<?php

namespace App\Services\EInvoice\Contracts;

use App\Services\EInvoice\DTO\ElectronicDocument;
use App\Services\EInvoice\DocumentKind;

interface EInvoiceProvider
{
    /** Clave del proveedor tal como se guarda en companie.proele (ej. 'factus', 'dian'). */
    public function key(): string;

    /** Código ISO2 del país que atiende este proveedor. */
    public function country(): string;

    public function supports(DocumentKind $kind): bool;

    /** Nombre para mostrar en las pantallas de soporte. */
    public function label(): string;

    /** Nombre de la ruta de su pantalla de configuración de credenciales (null si no tiene). */
    public function configRoute(): ?string;

    /**
     * ¿La empresa (companies.codmov) tiene credenciales activas para este proveedor?
     * Devuelve el ambiente (sandbox | production) si está configurada, o null si no.
     */
    public function configuredEnvironment(int $companyId): ?string;

    /**
     * Construye el payload del país y lo envía a la autoridad o al proveedor tecnológico.
     * Debe lanzar excepción si el documento es rechazado.
     * El registro en factus_documents_requests lo sigue creando el servicio interno de cada proveedor.
     */
    public function issue(ElectronicDocument $document): array;
}
