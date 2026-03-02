<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatusController extends Controller
{
    public function index(Request $request)
    {
        try {
            $statuses = Status::orderBy('sort_order')->get();
            
            return response()->json($statuses, Response::HTTP_OK);
            
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
                'code' => 'required|string|unique:statuses',
                'name' => 'required|string',
                'color' => 'nullable|string',
                'sort_order' => 'nullable|integer'
            ]);
            
            $status = Status::create($validated);
            
            return response()->json($status, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $status = Status::find($id);
            
            if (!$status) {
                return response()->json([
                    'error' => 'Estado no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json($status, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $status = Status::find($id);
            
            if (!$status) {
                return response()->json([
                    'error' => 'Estado no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $validated = $request->validate([
                'code' => 'required|string|unique:statuses,code,' . $id,
                'name' => 'required|string',
                'color' => 'nullable|string',
                'sort_order' => 'nullable|integer'
            ]);
            
            $status->update($validated);
            
            return response()->json($status, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $status = Status::find($id);
            
            if (!$status) {
                return response()->json([
                    'error' => 'Estado no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Verificar si tiene órdenes asociadas
            if ($status->orders()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar: hay órdenes con este estado'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $status->delete();
            
            return response()->json([
                'message' => 'Estado eliminado correctamente'
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}