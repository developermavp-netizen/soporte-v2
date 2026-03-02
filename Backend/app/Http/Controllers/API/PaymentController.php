<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function index(Request $request, $orderId = null)
    {
        try {
            if ($orderId) {
                $payments = Payment::where('order_id', $orderId)->orderBy('created_at', 'desc')->get();
            } else {
                $payments = Payment::with('order')->get();
            }
            return response()->json($payments, Response::HTTP_OK);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'reference' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);
            
            $validated['received_by'] = auth()->user()->name ?? 'Sistema';
            
            $payment = Payment::create($validated);
            return response()->json($payment, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $payment = Payment::with('order')->find($id);
            if (!$payment) return response()->json(['error' => 'Pago no encontrado'], 404);
            return response()->json($payment, Response::HTTP_OK);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = Payment::find($id);
            if (!$payment) return response()->json(['error' => 'Pago no encontrado'], 404);
            $payment->delete();
            return response()->json(['message' => 'Pago eliminado'], Response::HTTP_OK);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}