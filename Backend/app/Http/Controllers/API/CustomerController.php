<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Customer::query();
            
            // Búsqueda (equivalente a tu /customers/search?q=)
            if ($request->has('q')) {
                $search = $request->q;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $customers = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json($customers, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'required|string',
                'alternative_phone' => 'nullable|string',
                'email' => 'nullable|email|unique:customers,email',
                'address' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);
            
            $customer = Customer::create($validated);
            
            return response()->json($customer, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            if ($error->getCode() == 23000) {
                return response()->json([
                    'error' => 'El cliente ya existe (email duplicado)'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $customer = Customer::with('orders')->find($id);
            
            if (!$customer) {
                return response()->json([
                    'error' => 'Cliente no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json($customer, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::find($id);
            
            if (!$customer) {
                return response()->json([
                    'error' => 'Cliente no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'required|string',
                'alternative_phone' => 'nullable|string',
                'email' => 'nullable|email|unique:customers,email,' . $id,
                'address' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);
            
            $customer->update($validated);
            
            return response()->json($customer, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customer::find($id);
            
            if (!$customer) {
                return response()->json([
                    'error' => 'Cliente no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Verificar si tiene órdenes
            if ($customer->orders()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar: el cliente tiene órdenes asociadas'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $customer->delete();
            
            return response()->json([
                'message' => 'Cliente eliminado correctamente'
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}