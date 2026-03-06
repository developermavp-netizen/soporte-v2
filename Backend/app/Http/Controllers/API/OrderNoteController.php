<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrderNote;
use App\Models\Order;
use App\Traits\HandlesImages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderNoteController extends Controller
{
    use HandlesImages;

    public function index(Request $request, $orderId = null)
    {
        try {
            if ($orderId) {
                $query = OrderNote::where('order_id', $orderId);
                
                // Si es VENTAS, solo ver notas no internas
                if (auth()->user()->isVentas()) {
                    $query->where('is_internal', false);
                }
                
                $notes = $query->orderBy('created_at', 'asc')->get();
            } else {
                $notes = OrderNote::with('order')->get();
            }
            
            return response()->json($notes, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function store(Request $request, $orderId = null)
{
    try {
        // Convertir is_internal de string a boolean si viene de form-data
        if ($request->has('is_internal')) {
            $isInternal = filter_var($request->is_internal, FILTER_VALIDATE_BOOLEAN);
            $request->merge(['is_internal' => $isInternal]);
        }

        $orderId = $orderId ?? $request->order_id;
        
        if (!$orderId) {
            return response()->json([
                'error' => 'Se requiere el ID de la orden'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validación: aceptar tanto array de imágenes como archivo individual
        $rules = [
            'order_id' => 'sometimes|exists:orders,id',
            'note' => 'required|string',
            'is_internal' => 'boolean'
        ];

        // Si viene como archivo individual (desde Postman)
        if ($request->hasFile('images')) {
            $rules['images'] = 'image|mimes:jpeg,png,jpg|max:5120';
        } else {
            // Si viene como array (desde frontend con múltiples archivos)
            $rules['images'] = 'nullable|array';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg|max:5120';
        }

        $validated = $request->validate($rules);
        
        // Verificar permisos para notas internas
        $isInternal = $validated['is_internal'] ?? false;
        if ($isInternal && !auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
            return response()->json(['error' => 'No tienes permiso para crear notas internas'], 403);
        }

        // Si es VENTAS, solo notas públicas
        if (auth()->user()->isVentas()) {
            $validated['is_internal'] = false;
        }
        
        $validated['order_id'] = $orderId;
        $validated['created_by'] = auth()->user()->name ?? 'Sistema';

        // Subir imágenes
        $images = [];
        
        // Caso 1: Un solo archivo
        if ($request->hasFile('images')) {
            $imageData = $this->uploadImage($request->file('images'), 'notes');
            $images[] = $imageData;
        }
        
        // Caso 2: Múltiples archivos
        if ($request->hasFile('images.*')) {
            foreach ($request->file('images') as $image) {
                $imageData = $this->uploadImage($image, 'notes');
                $images[] = $imageData;
            }
        }
        
        if (!empty($images)) {
            $validated['images'] = $images;
        }
        
        $note = OrderNote::create($validated);
        
        return response()->json($note, Response::HTTP_CREATED);
        
    } catch (\Exception $error) {
        return response()->json([
            'error' => $error->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
    public function show($id)
    {
        try {
            $note = OrderNote::find($id);
            if (!$note) {
                return response()->json(['error' => 'Nota no encontrada'], 404);
            }

            // Verificar permisos para notas internas
            if ($note->is_internal && !auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'No tienes permiso para ver esta nota'], 403);
            }
            
            return response()->json($note, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Solo ADMIN y TECNICO pueden editar notas
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'No tienes permiso para editar notas'], 403);
            }

            $note = OrderNote::find($id);
            if (!$note) {
                return response()->json(['error' => 'Nota no encontrada'], 404);
            }
            
            $validated = $request->validate([
                'note' => 'sometimes|string',
                'is_internal' => 'sometimes|boolean'
            ]);
            
            $note->update($validated);
            
            return response()->json($note, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Solo ADMIN puede eliminar notas
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'Solo administradores pueden eliminar notas'], 403);
            }

            $note = OrderNote::find($id);
            if (!$note) {
                return response()->json(['error' => 'Nota no encontrada'], 404);
            }

            // Eliminar imágenes de Cloudinary si existen
            if ($note->images) {
                $images = json_decode($note->images, true);
                foreach ($images as $image) {
                    if (isset($image['public_id'])) {
                        $this->deleteImage($image['public_id']);
                    }
                }
            }
            
            $note->delete();
            
            return response()->json(['message' => 'Nota eliminada'], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}