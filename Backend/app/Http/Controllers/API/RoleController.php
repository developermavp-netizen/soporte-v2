<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    /**
     * GET /api/roles
     * Listar todos los roles (solo para admin)
     */
    public function index(Request $request)
    {
        try {
            // Solo ADMIN puede ver roles
            if (!auth()->user()->isAdmin()) {
                return response()->json([
                    'error' => 'No autorizado'
                ], Response::HTTP_FORBIDDEN);
            }

            $roles = Role::orderBy('name')->get();
            
            return response()->json($roles, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/roles/{id}
     * Ver un rol específico
     */
    public function show($id)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
            }

            $role = Role::find($id);
            
            if (!$role) {
                return response()->json([
                    'error' => 'Rol no encontrado'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json($role, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/roles
     * Crear un nuevo rol (solo admin)
     */
    public function store(Request $request)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|unique:roles',
                'description' => 'nullable|string'
            ]);

            $role = Role::create($validated);
            
            return response()->json($role, Response::HTTP_CREATED);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PUT /api/roles/{id}
     * Actualizar un rol
     */
    public function update(Request $request, $id)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
            }

            $role = Role::find($id);
            
            if (!$role) {
                return response()->json(['error' => 'Rol no encontrado'], Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name,' . $id,
                'description' => 'nullable|string'
            ]);

            $role->update($validated);
            
            return response()->json($role, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * DELETE /api/roles/{id}
     * Eliminar un rol (solo admin)
     */
    public function destroy($id)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
            }

            $role = Role::find($id);
            
            if (!$role) {
                return response()->json(['error' => 'Rol no encontrado'], Response::HTTP_NOT_FOUND);
            }

            // No permitir eliminar roles por defecto
            if (in_array($role->name, ['ADMIN', 'TECNICO', 'VENTAS'])) {
                return response()->json([
                    'error' => 'No se pueden eliminar los roles por defecto'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Verificar si tiene usuarios asociados
            if ($role->users()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar: hay usuarios con este rol'
                ], Response::HTTP_BAD_REQUEST);
            }

            $role->delete();
            
            return response()->json([
                'message' => 'Rol eliminado correctamente'
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}