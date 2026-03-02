<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeviceType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceTypeController extends Controller
{
    /**
     * GET /api/device-types
     * Listar todos los tipos de dispositivo (como en tu Express)
     */
    public function index(Request $request)
    {
        try {
            // Equivalente a: db.query('SELECT * FROM device_types ORDER BY name')
            $deviceTypes = DeviceType::orderBy('name')->get();
            
            return response()->json($deviceTypes, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            // Equivalente a: res.status(500).json({ error: error.message })
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/device-types
     * Crear un nuevo tipo de dispositivo
     */
    public function store(Request $request)
    {
        try {
            // Validación (como express-validator)
            $validated = $request->validate([
                'name' => 'required|string|unique:device_types,name'
            ]);
            
            // Equivalente a: INSERT INTO device_types (name) VALUES ($1) RETURNING *
            $deviceType = DeviceType::create($validated);
            
            return response()->json($deviceType, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            // Manejo de error unique (código 23505 en PostgreSQL)
            if ($error->getCode() == 23000) { // MySQL error code for duplicate
                return response()->json([
                    'error' => 'El tipo de dispositivo ya existe'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/device-types/{id}
     * Obtener un tipo de dispositivo por ID
     */
    public function show($id)
    {
        try {
            // Equivalente a: db.query('SELECT * FROM device_types WHERE id = $1', [id])
            $deviceType = DeviceType::find($id);
            
            if (!$deviceType) {
                return response()->json([
                    'error' => 'Tipo de dispositivo no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json($deviceType, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PUT /api/device-types/{id}
     * Actualizar un tipo de dispositivo
     */
    public function update(Request $request, $id)
    {
        try {
            $deviceType = DeviceType::find($id);
            
            if (!$deviceType) {
                return response()->json([
                    'error' => 'Tipo de dispositivo no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Validación (unique excepto este ID)
            $validated = $request->validate([
                'name' => 'required|string|unique:device_types,name,' . $id
            ]);
            
            // Equivalente a: UPDATE device_types SET name = $1 WHERE id = $2
            $deviceType->update($validated);
            
            return response()->json($deviceType, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            if ($error->getCode() == 23000) {
                return response()->json([
                    'error' => 'El tipo de dispositivo ya existe'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * DELETE /api/device-types/{id}
     * Eliminar un tipo de dispositivo
     */
    public function destroy($id)
    {
        try {
            $deviceType = DeviceType::find($id);
            
            if (!$deviceType) {
                return response()->json([
                    'error' => 'Tipo de dispositivo no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Verificar si tiene marcas asociadas (integridad referencial)
            if ($deviceType->brands()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar: tiene marcas asociadas'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $deviceType->delete();
            
            return response()->json([
                'message' => 'Tipo de dispositivo eliminado correctamente'
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}