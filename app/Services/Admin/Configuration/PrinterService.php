<?php

namespace App\Services\Admin\Configuration;

use App\Models\Tenant\CashRegister;
use App\Models\Tenant\Printer;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Impresoras de la empresa (comandas de cocina/barra y recibos de caja). */
class PrinterService extends BaseService
{
    public const TYPES = ['THERMAL' => 'Térmica', 'MATRIX' => 'Matricial', 'LASER' => 'Láser / inyección'];

    public const CONNECTIONS = ['NETWORK' => 'Red (IP)', 'USB' => 'USB', 'BLUETOOTH' => 'Bluetooth'];

    public const PAPER = ['MM58' => '58 mm', 'MM80' => '80 mm', 'A4' => 'A4'];

    public const PURPOSES = ['KITCHEN' => 'Cocina', 'BAR' => 'Barra', 'CASHIER' => 'Caja / recibos', 'OTHER' => 'Otro'];

    protected string $model = Printer::class;

    protected string $label = 'Impresora';

    protected array $searchable = ['code', 'name', 'detail', 'ip_address'];

    protected array $sortable = ['codmov', 'code', 'name', 'purpose', 'state'];

    protected array $fields = [
        'code', 'name', 'detail', 'printer_type', 'connection_type', 'ip_address', 'port', 'paper_width', 'purpose',
    ];

    protected bool $audited = true;

    public function find(int $id): Printer
    {
        return Printer::query()->findOrFail($id);
    }

    public static function options(): array
    {
        $list = fn (array $items) => collect($items)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();

        return [
            'types' => $list(self::TYPES), 'connections' => $list(self::CONNECTIONS),
            'paper' => $list(self::PAPER), 'purposes' => $list(self::PURPOSES),
        ];
    }

    public function paginateRows(array $filters, int $perPage): LengthAwarePaginator
    {
        $paginator = $this->paginate($filters, $perPage);

        $registers = CashRegister::query()
            ->whereIn('printer_id', $paginator->getCollection()->pluck('codmov'))
            ->selectRaw('printer_id, count(*) as total')->groupBy('printer_id')->pluck('total', 'printer_id');

        return $paginator->through(fn (Printer $printer) => [
            'id' => (int) $printer->codmov,
            'code' => $printer->code,
            'name' => $printer->name,
            'detail' => $printer->detail,
            'printer_type' => $printer->printer_type,
            'connection_type' => $printer->connection_type,
            'ip_address' => $printer->ip_address,
            'port' => $printer->port,
            'paper_width' => $printer->paper_width,
            'purpose' => $printer->purpose,
            'state' => (int) $printer->state,
            'registers' => (int) ($registers[$printer->codmov] ?? 0),
        ]);
    }

    public function destroy(Printer $printer): array
    {
        if (CashRegister::query()->where('printer_id', $printer->codmov)->exists()) {
            return ['data' => $printer, 'flash' => $this->toast('La impresora está asignada a una caja: quítala de la caja o inactívala.', 'error')];
        }

        $printer->delete();

        return ['data' => $printer, 'flash' => $this->toast('Impresora eliminada.')];
    }
}
