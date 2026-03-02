<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeliveryController extends Controller
{
    public function index(Request $request, $orderId = null)
    {
        try {
            if ($orderId) {
                $deliveries = Delivery::where('order_id', $orderId)->orderBy('delivered_at', 'desc')->get();
            } else {
                $deliveries = Delivery::with('order')->get();
            }
            return response()->json($deliveries, Response::HTTP_OK);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'received_by' => 'required|string',
                'relationship' => 'nullable|string',
                'identification' => 'nullable|string',
                'notes' => 'nullable|string',
                'delivered_at' => 'nullable|date'
            ]);
            
            $delivery = Delivery::create($validated);
            return response()->json($delivery, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $delivery = Delivery::with('order')->find($id);
            if (!$delivery) return response()->json(['error' => 'Entrega no encontrada'], 404);
            return response()->json($delivery, Response::HTTP_OK);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $delivery = Delivery::find($id);
            if (!$delivery) return response()->json(['error' => 'Entrega no encontrada'], 404);
            $delivery->delete();
            return response()->json(['message' => 'Entrega eliminada'], Response::HTTP_OK);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}