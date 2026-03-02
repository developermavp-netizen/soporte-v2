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

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'note' => 'required|string',
                'is_internal' => 'boolean',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);
            
            // Verificar permisos para notas internas
            $isInternal = $validated['is_internal'] ?? false;
            if ($isInternal && !auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'No tienes permiso para crear notas internas'], 403);
            }

            // Si es VENTAS, solo notas públicas
            if (auth()->user()->isVentas()) {
                $validated['is_internal'] = false;
            }
            
            $validated['created_by'] = auth()->user()->name ?? 'Sistema';

            // Subir imágenes si existen
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    $imageData = $this->uploadImage($image, 'notes');
                    $images[] = $imageData;
                }
                $validated['images'] = json_encode($images);
            }
            
            $note = OrderNote::create($validated);
            
            return response()->json($note, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
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