<?php

namespace App\Services\Third;

use App\Models\Tenant\ClientSupplier;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Clientes y proveedores de la empresa activa. */
class ContactService extends BaseService
{
    protected string $model = ClientSupplier::class;

    protected string $label = 'Tercero';

    protected array $searchable = ['code', 'business_name', 'first_name', 'last_name', 'document_number', 'email', 'phone', 'mobile'];

    protected array $sortable = ['codmov', 'code', 'business_name', 'document_number', 'state'];

    protected array $fields = [
        'document_type', 'document_number', 'business_name', 'first_name', 'last_name',
        'email', 'phone', 'mobile', 'address', 'customer', 'supplier',
    ];

    protected bool $audited = true;

    public function find(int $id): ClientSupplier
    {
        return ClientSupplier::query()->findOrFail($id);
    }

    /**
     * Tipos de documento de la empresa (parámetro "Clases de documento" importado desde soporte).
     * El código es el código DIAN/Factus V2 (13 = cédula, 31 = NIT…). Vacío si aún no se importó.
     *
     * @return array<int, array{code:string, name:string}>
     */
    public static function documentTypes(): array
    {
        try {
            return DB::connection('tenant')->table('document_classes')->where('state', 1)->orderBy('code')->get(['code', 'name'])
                ->map(fn ($row) => ['code' => (string) $row->code, 'name' => $row->name])->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        match ($filters['kind'] ?? null) {
            'customer' => $query->where('customer', true),
            'supplier' => $query->where('supplier', true),
            default => null,
        };
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->paginate($filters, $perPage)->through(fn (ClientSupplier $contact) => [
            'id' => (int) $contact->codmov,
            'code' => $contact->code,
            'document_type' => $contact->document_type,
            'document_number' => $contact->document_number,
            'business_name' => $contact->business_name,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'full_name' => $contact->business_name ?: trim($contact->first_name . ' ' . $contact->last_name),
            'email' => $contact->email,
            'phone' => $contact->phone,
            'mobile' => $contact->mobile,
            'address' => $contact->address,
            'customer' => (bool) $contact->customer,
            'supplier' => (bool) $contact->supplier,
            'state' => (int) $contact->state,
        ]);
    }

    public function store(array $payload): array
    {
        return $this->transaction(function () use ($payload) {
            $contact = new ClientSupplier();
            $contact->forceFill($this->stamp($this->onlyFields($payload), true) + [
                'code' => $this->nextCode(), 'state' => 1,
            ])->save();

            return ['data' => $contact, 'flash' => $this->toast('Tercero creado correctamente.')];
        });
    }

    public function destroy(ClientSupplier $contact): array
    {
        if ($contact->getConnection()->table('orders')->where('contact_id', $contact->codmov)->exists()) {
            return ['data' => $contact, 'flash' => $this->toast('El tercero tiene ventas registradas: no se puede eliminar, solo inactivar.', 'error')];
        }

        $contact->delete();

        return ['data' => $contact, 'flash' => $this->toast('Tercero eliminado.')];
    }

    /** T000001, T000002… (código interno; el documento es el dato de negocio). */
    protected function nextCode(): string
    {
        $next = (int) ClientSupplier::withTrashed()->max('codmov') + 1;

        return 'T' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
