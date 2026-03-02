<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Device::with(['deviceType', 'brand']);
            
            // Búsqueda por serial (equivalente a /devices/search?serial=)
            if ($request->has('serial')) {
                $query->where('serial_number', 'like', "%{$request->serial}%");
            }
            
            // Filtro por tipo
            if ($request->has('device_type_id')) {
                $query->where('device_type_id', $request->device_type_id);
            }
            
            // Filtro por marca
            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }
            
            $devices = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json($devices, Response::HTTP_OK);
            
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
                'device_type_id' => 'required|exists:device_types,id',
                'brand_id' => 'required|exists:brands,id',
                'model' => 'required|string',
                'serial_number' => 'nullable|string|unique:devices',
                'password' => 'nullable|string',
                'accessories' => 'nullable|string',
                'physical_condition' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);
            
            $device = Device::create($validated);
            
            return response()->json($device->load(['deviceType', 'brand']), Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            if ($error->getCode() == 23000) {
                return response()->json([
                    'error' => 'El número de serie ya existe'
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
            $device = Device::with(['deviceType', 'brand', 'orders'])->find($id);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Dispositivo no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json($device, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $device = Device::find($id);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Dispositivo no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $validated = $request->validate([
                'device_type_id' => 'required|exists:device_types,id',
                'brand_id' => 'required|exists:brands,id',
                'model' => 'required|string',
                'serial_number' => 'nullable|string|unique:devices,serial_number,' . $id,
                'password' => 'nullable|string',
                'accessories' => 'nullable|string',
                'physical_condition' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);
            
            $device->update($validated);
            
            return response()->json($device->load(['deviceType', 'brand']), Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $device = Device::find($id);
            
            if (!$device) {
                return response()->json([
                    'error' => 'Dispositivo no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Verificar si tiene órdenes
            if ($device->orders()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar: el dispositivo tiene órdenes asociadas'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $device->delete();
            
            return response()->json([
                'message' => 'Dispositivo eliminado correctamente'
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}