<?php

namespace App\Http\Controllers;

use App\Models\Tenant\FactusPayCollection;
use App\Models\Tenant\Order;
use App\Services\Factus\FactusPayCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/** Cobro por QR / Bre-B desde la orden. El botón de la interfaz de usuario consume estos dos endpoints. */
class FactusPayController extends Controller
{
    public function create(Request $request, FactusPayCollectionService $collections): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'in:QR,BREB'],
        ]);

        try {
            $order = Order::query()->findOrFail($data['order_id']);
            $collection = $collections->create($order, $this->companyId(), $data['method'] ?? 'QR');

            return response()->json(['reference_code' => $collection->reference_code, 'collection' => $collection], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function status(string $reference, FactusPayCollectionService $collections): JsonResponse
    {
        try {
            $collection = FactusPayCollection::query()->where('reference_code', $reference)->firstOrFail();

            return response()->json($collections->refresh($collection, $this->companyId()));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    private function companyId(): int
    {
        return (int) session('company_id', session('codemp', 0));
    }
}
