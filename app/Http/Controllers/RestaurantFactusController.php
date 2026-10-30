<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Order;
use App\Services\Factus\RestaurantOrderFactusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RestaurantFactusController extends Controller
{
    public function emit(Request $request, Order $order, RestaurantOrderFactusService $service): JsonResponse
    {
        try {
            return response()->json($service->emit($order, (int) $request->session()->get('company_id')));
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
