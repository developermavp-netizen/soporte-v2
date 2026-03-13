<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Repair;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RepairController extends Controller
{
    public function store(Request $request, $orderId)
    {
        try {
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'No tienes permiso'], 403);
            }

            $order = Order::find($orderId);
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $validated = $request->validate([
                'description'  => 'required|string',
                'part_model'   => 'nullable|string',
                'supplier'     => 'nullable|string',
                'quantity'     => 'nullable|integer|min:1',
                'cost'         => 'required|numeric|min:0',
                'price'        => 'nullable|numeric|min:0',
                'type'         => 'nullable|in:ORIGINAL,GENERICO',
                'warranty_days'=> 'nullable|integer|min:0',
            ]);

            $validated['order_id']    = $order->id;
            $validated['created_by']  = auth()->user()->name ?? 'Sistema';
            $validated['quantity']    = $validated['quantity'] ?? 1;
            $validated['type']        = $validated['type'] ?? 'ORIGINAL';

            $repair = Repair::create($validated);

            return response()->json($repair, Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($orderId, $repairId)
    {
        try {
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'No tienes permiso'], 403);
            }

            $repair = Repair::where('order_id', $orderId)->find($repairId);
            if (!$repair) {
                return response()->json(['error' => 'Componente no encontrado'], 404);
            }

            $repair->delete();

            return response()->json(['message' => 'Componente eliminado'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}