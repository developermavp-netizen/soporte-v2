<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Brand::with('deviceType');

            if ($request->has('device_type_id')) {
                $query->where('device_type_id', $request->device_type_id);
            }

            $brands = $query->orderBy('name')->get();

            return response()->json($brands, Response::HTTP_OK);

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
                'name' => [
                    'required',
                    'string',
                    Rule::unique('brands')->where(function ($query) use ($request) {
                        return $query->where('device_type_id', $request->device_type_id);
                    }),
                ],
                'device_type_id' => 'required|exists:device_types,id',
            ]);

            $brand = Brand::create($validated);

            return response()->json($brand->load('deviceType'), Response::HTTP_CREATED);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $brand = Brand::with('deviceType')->find($id);

            if (!$brand) {
                return response()->json([
                    'error' => 'Marca no encontrada'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($brand, Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'error' => 'Marca no encontrada'
                ], Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    Rule::unique('brands')
                        ->where(function ($query) use ($request) {
                            return $query->where('device_type_id', $request->device_type_id);
                        })
                        ->ignore($id),
                ],
                'device_type_id' => 'required|exists:device_types,id',
            ]);

            $brand->update($validated);

            return response()->json($brand->load('deviceType'), Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'error' => 'Marca no encontrada'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($brand->devices()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar: tiene dispositivos asociados'
                ], Response::HTTP_BAD_REQUEST);
            }

            $brand->delete();

            return response()->json([
                'message' => 'Marca eliminada correctamente'
            ], Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}