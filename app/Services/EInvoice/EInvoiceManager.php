<?php

namespace App\Services\EInvoice;

use App\Models\Tenant\Companie;
use App\Services\EInvoice\Contracts\EInvoiceProvider;
use App\Services\EInvoice\DTO\ElectronicDocument;
use App\Support\CompanyCountry;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Punto único de entrada para emitir documentos electrónicos.
 *
 * El proveedor se resuelve automáticamente: país de la empresa activa
 * (companies.codcoi -> countries.iso2) + companie.proele si existe y es
 * válido para ese país; si no, el proveedor por defecto del país.
 */
class EInvoiceManager
{
    public function issue(ElectronicDocument $document): array
    {
        $provider = $this->providerFor();

        if (! $provider->supports($document->kind)) {
            throw new RuntimeException(sprintf(
                'El proveedor "%s" (%s) no soporta el documento "%s".',
                $provider->key(),
                $provider->country(),
                $document->kind->label()
            ));
        }

        return $provider->issue($document);
    }

    /**
     * Proveedor de la empresa activa. Si se pasa $key se usa exactamente ese
     * (sin fallback); si no, se resuelve automáticamente.
     */
    public function providerFor(?string $country = null, ?string $key = null): EInvoiceProvider
    {
        $country = strtoupper($country ?: $this->currentCountry());
        $key = $key ?: $this->resolveKey($country, $this->configuredKey());

        $registered = $this->providersFor($country);
        $class = $registered[$key] ?? null;

        if ($class === null) {
            throw new RuntimeException(sprintf(
                'El proveedor "%s" no está registrado para %s (disponibles: %s).',
                $key,
                $country,
                implode(', ', array_keys($registered)) ?: 'ninguno'
            ));
        }

        return app($class);
    }

    /**
     * Elige la clave del proveedor para un país. No toca BD ni sesión.
     *
     * @param string|null $configured valor de companie.proele (puede ser nulo o de otro país)
     */
    public function resolveKey(string $country, ?string $configured = null): string
    {
        $country = strtoupper($country);
        $registered = $this->providersFor($country);

        if ($registered === []) {
            throw new RuntimeException("No hay proveedores de facturación electrónica registrados para el país {$country}.");
        }

        if ($configured && isset($registered[$configured])) {
            return $configured;
        }

        if ($configured) {
            Log::warning('companie.proele no corresponde al país de la empresa; se usa el proveedor por defecto', [
                'country' => $country,
                'proele' => $configured,
            ]);
        }

        $default = config("einvoice.defaults.{$country}");
        if ($default && isset($registered[$default])) {
            return $default;
        }

        if (count($registered) === 1) {
            return (string) array_key_first($registered);
        }

        throw new RuntimeException(sprintf(
            'El país %s tiene varios proveedores (%s) y ninguno por defecto. Defina einvoice.defaults.%s.',
            $country,
            implode(', ', array_keys($registered)),
            $country
        ));
    }

    /** ¿Hay al menos un proveedor registrado para este país? */
    public function supportsCountry(string $country): bool
    {
        return $this->providersFor($country) !== [];
    }

    /** Claves de proveedores disponibles para un país (para selects en la UI). */
    public function providerKeysFor(string $country): array
    {
        return array_keys($this->providersFor($country));
    }

    /** ISO2 del país de la empresa activa. */
    public function currentCountry(): string
    {
        return CompanyCountry::iso2();
    }

    /** Valor crudo de companie.proele de la empresa activa (puede ser nulo). */
    public function configuredKey(): ?string
    {
        $key = Companie::query()->value('proele');

        return $key ? (string) $key : null;
    }

    /** @return array<string,string> clave => clase */
    private function providersFor(string $country): array
    {
        return config('einvoice.providers.' . strtoupper($country), []);
    }
}
