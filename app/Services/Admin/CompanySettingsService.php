<?php

namespace App\Services\Admin;

use App\Models\Tenant\Companie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/** Datos del restaurante (tabla companie del tenant: un solo registro) y su logo. */
class CompanySettingsService
{
    private const FIELDS = [
        'restaurant_name', 'business_name', 'tax_id', 'invoice_prefix', 'phone', 'email',
        'address', 'city', 'country', 'currency', 'timezone', 'tax_percentage',
    ];

    public function current(): Companie
    {
        return Companie::query()->first()
            ?? Companie::query()->create([
                'restaurant_name' => (string) (session('company_name') ?: 'Mi restaurante'),
                'state' => 1,
            ]);
    }

    public function toArray(Companie $company): array
    {
        return [
            'restaurant_name' => $company->restaurant_name,
            'business_name' => $company->business_name,
            'tax_id' => $company->tax_id,
            'invoice_prefix' => $company->invoice_prefix,
            'phone' => $company->phone,
            'email' => $company->email,
            'address' => $company->address,
            'city' => $company->city,
            'country' => $company->country,
            'currency' => $company->currency,
            'timezone' => $company->timezone,
            'tax_percentage' => (float) $company->tax_percentage,
            'logo_url' => $company->logo
                ? Storage::disk('public')->url($company->logo) . '?v=' . $company->updated_at?->timestamp
                : null,
        ];
    }

    public function update(Companie $company, array $data, ?UploadedFile $logo, bool $removeLogo): array
    {
        $fields = Arr::only($data, self::FIELDS);
        $disk = Storage::disk('public');

        if ($removeLogo && $company->logo) {
            $disk->delete($company->logo);
            $fields['logo'] = null;
        }

        if ($logo) {
            // Una carpeta por empresa; la extensión sale del contenido real (ya validado como imagen).
            $path = $logo->storeAs('tenants/' . (int) session('company_id'), 'logo.' . $logo->extension(), 'public');

            if ($company->logo && $company->logo !== $path) {
                $disk->delete($company->logo);
            }

            $fields['logo'] = $path;
        }

        $company->forceFill($fields + ['host' => request()->ip(), 'codope' => auth()->user()?->code])->save();

        return ['data' => $company, 'flash' => ['toast' => 'Datos de la empresa actualizados.', 'type' => 'success']];
    }
}
